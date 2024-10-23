<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class WfcCoopDeals extends FannieReportPage
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Co-op Deals Report";
    protected $header = "Fresh Deals Movement";
    public $description = '[Co-op Deals Report] displays information about Co-op Deals\' share of sales';

    protected $report_headers = array('Date', 'Co-op Deals Sales', 'Owners', 'Non-Owners', '% Total', '% Owners', '% Non-Owners');
    protected $new_tablesorter = true;
    protected $required_fields = array('batchSet');

    public function fetch_report_data()
    {
        $batchP = $this->connection->prepare("SELECT batchName, startDate, endDate FROM batches WHERE batchID=?");
        $batchW = $this->connection->getRow($batchP, array($this->form->batchSet));
        $dlog = DTransactionsModel::selectDlog($batchW['startDate'], $batchW['endDate']);
        list($super, $rest) = explode(' ', $batchW['batchName'], 2);
        $upcP = $this->connection->prepare("SELECT upc
            FROM batchList AS l
                INNER JOIN batches AS b ON l.batchID=b.batchID
            WHERE batchName LIKE ?");
        $upcs = $this->connection->getAllValues($upcP, array('%' . $rest));

        $super = FormLib::get('super', -1);
        $superTable = $super == -1 ? 'MasterSuperDepts' : 'superdepts';

        list($inStr,$args) = $this->connection->safeInClause($upcs);
        $store = FormLib::get('store');
        $args[] = $store;
        $args[] = $batchW['startDate'];
        $args[] = $batchW['endDate'] . ' 23:59:59';
        if ($super != -1) {
            $args[] = $super;
        }
        $saleP = $this->connection->prepare("SELECT YEAR(tdate), MONTH(tdate), DAY(tdate), SUM(total) AS ttl,
                SUM(CASE WHEN memType IN (1,3,5) THEN total ELSE 0 END) AS memTTL
            FROM {$dlog} AS t
                INNER JOIN {$superTable} AS m ON t.department=m.dept_ID
            WHERE upc IN ({$inStr})
                AND " . DTrans::isStoreID($store) . "
                AND memType NOT IN " . DTrans::memTypeIgnore($this->connection) . "
                AND tdate BETWEEN ? AND ?
                " . ($super != -1 ? ' AND m.superID=? ' : '') . "
            GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)");

        $allP = $this->connection->prepare("SELECT YEAR(tdate), MONTH(tdate), DAY(tdate), SUM(total) AS ttl,
                SUM(CASE WHEN memType IN (1,3,5) THEN total ELSE 0 END) AS memTTL
            FROM {$dlog} AS t
                INNER JOIN {$superTable} AS m ON t.department=m.dept_ID
            WHERE m.superID <> 0
                AND trans_type IN ('I','D')
                AND " . DTrans::isStoreID($store) . "
                AND memType NOT IN " . DTrans::memTypeIgnore($this->connection) . "
                AND tdate BETWEEN ? AND ?
                " . ($super != -1 ? ' AND m.superID=? ' : '') . "
            GROUP BY YEAR(tdate), MONTH(tdate), DAY(tdate)");

        $data = array();
        $saleR = $this->connection->execute($saleP, $args);
        while ($row = $this->connection->fetchRow($saleR)) {
            $key = date('Y-m-d', mktime(0,0,0,$row[1],$row[2],$row[0]));
            $data[$key] = array(
                $key,
                sprintf('%.2f', $row['ttl']),
                sprintf('%.2f', $row['memTTL']),
                sprintf('%.2f', $row['ttl'] - $row['memTTL']),
            );
        }

        $args = array($store, $batchW['startDate'], $batchW['endDate'] . ' 23:59:59');
        if ($super != -1) {
            $args[] = $super;
        }
        $allR = $this->connection->execute($allP, $args);
        while ($row = $this->connection->fetchRow($allR)) {
            $key = date('Y-m-d', mktime(0,0,0,$row[1],$row[2],$row[0]));
            $data[$key][] = sprintf('%.2f%%', $data[$key][1] / $row['ttl'] * 100);
            $data[$key][] = sprintf('%.2f%%', $data[$key][2] / $row['memTTL'] * 100);
            $data[$key][] = sprintf('%.2f%%', $data[$key][3] / ($row['ttl'] - $row['memTTL']) * 100);
        }

        return $this->dekey_array($data);
    }

    public function calculate_footers($data)
    {
        $ret = array('Average', 0, 0, 0, 0, 0, 0);
        foreach ($data as $row) {
            for ($i=1;$i<7;$i++) {
                $ret[$i] += $row[$i];
            }
        }
        for ($i=1;$i<7;$i++) {
            $ret[$i] = sprintf('%.2f', $ret[$i] / count($data));
        }

        return $ret;
    }

    public function form_content()
    {
        $batchR = $this->connection->query("SELECT batchID, batchName
            FROM batches
            WHERE (batchName LIKE '%Co-op Deals A%' OR batchName LIKE '%Co-op Deals B%')
                AND batchName NOT LIKE '%GEN MERCH%'
            ORDER BY batchID DESC");
        $bOpts = '';
        $seen = array();
        while ($batchW = $this->connection->fetchRow($batchR)) {
            list($super,$rest) = explode(' ', $batchW['batchName'], 2);
            if (!isset($seen[$rest])) {
                $bOpts .= sprintf('<option value="%d">%s</option>', $batchW['batchID'], $rest);
                $seen[$rest] = true;
            }
        }
        $stores = FormLib::storePicker();
        $superR = $this->connection->query("SELECT superID, super_name FROM superDeptNames
            GROUP BY superID, super_name");
        $sOpts = '<option value="-1"></option>';
        while ($superW = $this->connection->fetchRow($superR)) {
            $sOpts .= sprintf('<option value="%d">%s</option>', $superW['superID'], $superW['super_name']);
        }

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Batch Set</label>
        <select class="form-control" name="batchSet">{$bOpts}</select> 
    </div>
    <div class="form-group">
        <label>Super Department</label>
        <select class="form-control" name="super">{$sOpts}</select> 
    </div>
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Get Report</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

