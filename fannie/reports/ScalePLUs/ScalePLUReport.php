<?php

use COREPOS\Fannie\API\item\ItemText;

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class ScalePLUReport extends FannieReportPage
{
    protected $header = 'Scale PLU List';
    protected $title = 'Scale PLU List';
    public $report_set = 'Service Scales';
    public $description = '[Scale PLU List] produces a report of service scale PLU items';
    protected $required_fields = array('submit');
    protected $report_headers = array(array('Brand', 'Description', 'PLU'));

    function fetch_report_data()
    {
        $super = FormLib::get('super', null);
        $dept1 = FormLib::get('deptStart', null);
        $dept2 = FormLib::get('deptEnd', null);
        $depts = FormLib::get('departments', array());
        $subs = FormLib::get('subdepts', array());
        $lastSold = FormLib::get('lastSold', null);
        $showUPCs = FormLib::get('showUPCs', 0);
        if ($showUPCs) {
            $this->report_headers[0][] = 'UPC';
        }

        $joins = '';
        $where = '1=1';
        $args = array();
        if ($super !== null) {
            if ($super == -2) {
                $joins .= " INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID ";
                $where .= " AND m.superID <> 0 ";
            } elseif ($super >= 0) {
                $joins .= " INNER JOIN superdepts AS m ON p.department=m.dept_ID ";
                $where .= " AND m.superID=? ";
                $args[] = $super;
            }
        }
        if ($dept1 !== null && $dept2 !== null) {
            $where .= " AND p.department BETWEEN ? AND ? ";
            $args[] = ($dept1 <= $dept2) ? $dept1 : $dept2;
            $args[] = ($dept1 <= $dept2) ? $dept2 : $dept1;
        } elseif (count($depts) > 0) {
            list($inStr, $args) = $this->connection->safeInClause($depts, $args);
            $where .= " AND p.department IN ({$inStr}) ";
        }
        if (count($subs) > 0) {
            list($inStr, $args) = $this->connection->safeInClause($subs, $args);
            $where .= " AND p.subdept IN ({$inStr}) ";
        }
        $args[] = $this->config->get('STORE_ID');

        $query = $this->connection->prepare("
            SELECT p.upc,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL() . ",
                CASE WHEN s.subdept_name IS NULL OR s.subdept_name='' THEN 'Unknown' ELSE s.subdept_name END AS subdept_name
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN subdepts AS s ON p.subdept=s.subdept_no
                {$joins}
            WHERE {$where}
                AND store_id=?
                AND p.upc LIKE '002%'
            ORDER BY subdept_name,
                brand,
                description");
        $res = $this->connection->execute($query, $args);
        $results = array();
        $upcs = array();
        while ($row = $this->connection->fetchRow($res)) {
            $results[] = $row;
            $upcs[] = $row['upc'];
        }
        $keep = array();
        if ($lastSold) {
            $dlog = DTransactionsModel::selectDlog($lastSold, date('Y-m-d', strtotime('yesterday')));
            list($upcIn, $upcArgs) = $this->connection->safeInClause($upcs);
            $query = $this->connection->prepare("
                SELECT upc
                FROM {$dlog}
                WHERE upc IN ({$upcIn})
                    AND tdate >= ?
                GROUP BY upc
                HAVING SUM(total) <> 0");
            $upcArgs[] = $lastSold;
            $res = $this->connection->execute($query, $upcArgs);
            while ($row = $this->connection->fetchRow($res)) {
                $keep[] = $row['upc'];
            }
        }

        $data[] = array();
        $currentSub = null;
        foreach ($results as $row) {
            if (count($keep) > 0 && !in_array($row['upc'], $keep)) {
                continue;
            }
            if ($row['subdept_name'] != $currentSub) {
                $currentSub = $row['subdept_name'];
                $this->report_headers[] = $showUPCs ? array($currentSub, null, null, null) : array($currentSub, null, null);
                $data[] = array('meta' => FannieReportPage::META_REPEAT_HEADERS);
            }
            $record = array(
                $row['brand'],
                $row['description'],
                substr($row['upc'], 3, 4),
            );
            if ($showUPCs) {
                $record[] = $row['upc'];
            }
            $data[] = $record;
        }

        return $data;
    }

    function form_content()
    {
        $depts = FormLib::standardDepartmentFields();
        $cutoff = date('Y-m-d', strtotime('61 days ago'));

        return <<<HTML
<form method="get" class="form-horizontal">
<div class="row">
    <div class="col-sm-6">
        {$depts}
    </div>
    <div class="col-sm-6">
        <label>Sold since</label>
        <input type="text" name="lastSold" class="form-control date-field" 
            value="{$cutoff}" placeholder="optional" />
        <label>Show UPCs
            <input type="checkbox" name="showUPCs" value="1" />
        </label>
    </div>
</div>
<p>
    <button type="submit" name="submit" value="1" class="btn btn-default btn-core">Get List</button>
</p>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

