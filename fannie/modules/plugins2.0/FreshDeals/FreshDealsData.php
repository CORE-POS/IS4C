<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class FreshDealsData extends FannieRESTfulPage
{

    protected $header = 'Fresh Deals Data';
    protected $title = 'Fresh Deals Data';

    public function preprocess()
    {
        $this->addRoute('get<batchID>');

        return parent::preprocess();
    }

    protected function get_batchID_view()
    {
        $batchP = $this->connection->prepare("SELECT upc FROM batchList WHERE batchID=?");
        $batchR = $this->connection->execute($batchP, array($this->batchID));
        $itemP = $this->connection->prepare("SELECT brand, description FROM products WHERE upc=?");
        $lcP = $this->connection->prepare("SELECT likeCodeDesc FROM likeCodes WHERE likeCode=?");
        $ret = '';
        $store = FormLib::get('store');
        while ($row = $this->connection->fetchRow($batchR)) {
            if (substr($row['upc'], 0, 2) == 'LC') {
                $lc = substr($row['upc'], 2);
                $lcName = $this->connection->getValue($lcP, array($lc));
                $ret .= '<b>' . $lcName . '</b></br />';
            } else {
                $itemW = $this->connection->getRow($itemP, array($row['upc']));
                $ret .= '<b>' . $itemW['brand'] . ' ' . $itemW['description'] . '</b><br />';
            }
            $ret .= '<table class="table table-bordered" style="width: 500px;">';
            $ret .= '<tr><th>Start</th><th>End</th><th>Sales $</th><th>Sales Units</th><th>Retail Price</th></tr>';
            $ret .= '<tr>';
            $promoStart = FormLib::get('promoStart');
            $promoEnd = FormLib::get('promoEnd');
            $ret .= '<td>' . date('n/j/Y', strtotime($promoStart)) . '</td>';
            $ret .= '<td>' . date('n/j/Y', strtotime($promoEnd)) . '</td>';
            $sales = $this->getSales($row['upc'], $store, $promoStart, $promoEnd);
            $ret .= '<td>' . sprintf('%.2f', $sales['ttl']) . '</td>';
            $ret .= '<td>' . sprintf('%.2f', $sales['qty']) . '</td>';
            $ret .= '<td>' . sprintf('%.2f', $sales['retail']) . '</td>';
            $ret .= '</tr>';
            $ret .= '<tr>';
            $prevStart = FormLib::get('prevStart');
            $prevEnd = FormLib::get('prevEnd');
            $ret .= '<td>' . date('n/j/Y', strtotime($prevStart)) . '</td>';
            $ret .= '<td>' . date('n/j/Y', strtotime($prevEnd)) . '</td>';
            $sales = $this->getSales($row['upc'], $store, $prevStart, $prevEnd);
            $ret .= '<td>' . sprintf('%.2f', $sales['ttl']) . '</td>';
            $ret .= '<td>' . sprintf('%.2f', $sales['qty']) . '</td>';
            $ret .= '<td>' . sprintf('%.2f', $sales['retail']) . '</td>';
            $ret .= '</tr>';
            $ret .= '<tr>';
            $yearStart = FormLib::get('yearStart');
            $yearEnd = FormLib::get('yearEnd');
            $ret .= '<td>' . date('n/j/Y', strtotime($yearStart)) . '</td>';
            $ret .= '<td>' . date('n/j/Y', strtotime($yearEnd)) . '</td>';
            $sales = $this->getSales($row['upc'], $store, $yearStart, $yearEnd);
            $ret .= '<td>' . sprintf('%.2f', $sales['ttl']) . '</td>';
            $ret .= '<td>' . sprintf('%.2f', $sales['qty']) . '</td>';
            $ret .= '<td>' . sprintf('%.2f', $sales['retail']) . '</td>';
            $ret .= '</tr>';
            $ret .= '</table>';
        }

        return $ret;
    }

    private function getSales($upc, $store, $start, $end)
    {
        $dlog = DTransactionsModel::selectDlog($start, $end);
        $upcs = array();
        if (substr($upc, 0, 2) == 'LC') {
            $lcP = $this->connection->prepare("SELECT upc FROM upcLike WHERE likeCode=?");
            $lcR = $this->connection->execute($lcP, array(substr($upc,2)));
            while ($lcW = $this->connection->fetchRow($lcR)) {
                $upcs[] = $lcW['upc'];
            }
        } else {
            $upcs[] = $upc;
        }

        $args = array($store, $start, $end . ' 23:59:59');
        list($inStr, $args) = $this->connection->safeInClause($upcs, $args);
        $query = "SELECT SUM(quantity) AS qty, SUM(total) AS ttl, 
            AVG(CASE WHEN register_no=40 THEN null ELSE unitPrice END) AS retail
            FROM {$dlog}
            WHERE " . DTrans::isStoreID($store) . "
                AND tdate BETWEEN ? AND ?
                AND memType NOT IN " . DTrans::memTypeIgnore($this->connection) . "
                AND upc IN ({$inStr})";
        $prep = $this->connection->prepare($query);
        $ret = array(
            'qty' => 0,
            'ttl' => 0,
            'retail' => 0,
        );
        $row = $this->connection->getRow($prep, $args);
        if (is_array($row)) {
            $ret = $row;
        }

        return $ret;
    }

    protected function post_id_handler()
    {
        $batchP = $this->connection->prepare("SELECT startDate, endDate FROM batches WHERE batchID=?");
        $batch = $this->connection->getRow($batchP, array($this->id));
        $json = array(
            'promo1' => str_replace(' 00:00:00', '', $batch['startDate']),
            'promo2' => str_replace(' 00:00:00', '', $batch['endDate']),
        );
        $ts = strtotime($batch['startDate']);
        $json['prev1'] = date('Y-m-d', mktime(0,0,0,date('n',$ts),date('j',$ts)-7,date('Y',$ts)));
        $json['prev2'] = date('Y-m-d', mktime(0,0,0,date('n',$ts),date('j',$ts)-1,date('Y',$ts)));

        $ly = mktime(0,0,0,date('n',$ts),date('j',$ts),date('Y',$ts)-1);
        while (date('N',$ly) != 3) {
            if (date('N',$ly) < 3) {
                $ly = mktime(0,0,0,date('n',$ly),date('j',$ly)+1,date('Y',$ly));
            } else {
                $ly = mktime(0,0,0,date('n',$ly),date('j',$ly)-1,date('Y',$ly));
            }
        }
        $json['year1'] = date('Y-m-d', mktime(0,0,0,date('n',$ly),date('j',$ly),date('Y',$ly)));
        $json['year2'] = date('Y-m-d', mktime(0,0,0,date('n',$ly),date('j',$ly)+6,date('Y',$ly)));

        echo json_encode($json);

        return false;
    }

    protected function get_view()
    {
        $batchR = $this->connection->query("SELECT batchID, batchName FROM batches WHERE batchName LIKE '%FRESH DEAL%' ORDER BY batchID DESC");
        $opts = '<option value="">Select batch</option>';
        while ($row = $this->connection->fetchRow($batchR)) {
            $opts .= sprintf('<option value="%d">%s</option>', $row['batchID'], $row['batchName']);
        }
        $stores = FormLib::storePicker();
        return <<<HTML
<script>
function getDates(id) {
    $.ajax({
        type: 'post',
        dataType: 'json',
        data: 'id='+id,
        success: function(resp) {
            $('#promoStart').val(resp['promo1']);
            $('#promoEnd').val(resp['promo2']);
            $('#prevStart').val(resp['prev1']);
            $('#prevEnd').val(resp['prev2']);
            $('#yearStart').val(resp['year1']);
            $('#yearEnd').val(resp['year2']);
        }
    });
}
</script>
<form method="get" action="FreshDealsData.php">
    <div class="form-group">
        <label>Batch</label>
        <select name="batchID" class="form-control" onchange="getDates(this.value);">
            {$opts}
        </select>
    </div>
    <table class="table">
        <tr>
            <td>Promo Week Start</td>
            <td><input type="text" id="promoStart" name="promoStart" class="form-control date-field" /></td>
            <td>Promo Week End</td>
            <td><input type="text" id="promoEnd" name="promoEnd" class="form-control date-field" /></td>
        </tr>
        <tr>
            <td>Prev. Week Start</td>
            <td><input type="text" id="prevStart" name="prevStart" class="form-control date-field" /></td>
            <td>Prev. Week End</td>
            <td><input type="text" id="prevEnd" name="prevEnd" class="form-control date-field" /></td>
        </tr>
        <tr>
            <td>Last Year Start</td>
            <td><input type="text" id="yearStart" name="yearStart" class="form-control date-field" /></td>
            <td>Last Year End</td>
            <td><input type="text" id="yearEnd" name="yearEnd" class="form-control date-field" /></td>
        </tr>
    </table>
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
