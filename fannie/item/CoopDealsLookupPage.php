<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class CoopDealsLookupPage extends FannieRESTfulPage
{
    protected $header = 'Coop Deals Item Lookup';
    protected $title = 'Coop Deals Item Lookup';
    protected $enable_linea = true;
    public $themed = true;
    public $description = '[Coop Deals Item Lookup] Scans Co-op Deals
        commitment file for sales informatino on item scanned, allows
        for one-click access to add item to batch at the price given in
        the price file.';


    function preprocess()
    {
        if (php_sapi_name() !== 'cli') {
            if (session_id() == '') {
                session_start();
            }
        }

       $this->__routes[] = 'get<upc>';
       $this->__routes[] = 'get<insert>';
       $this->__routes[] = 'get<month>';
       $this->__routes[] = 'get<cycle>';

       return parent::preprocess();
    }

    function get_cycle_handler() {

        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = FormLib::get('upc');

        if ($this->session->cycleDate) {
            unset($this->session->cycleDate);
            return header('location: CoopDealsLookupPage.php?upc='.$upc);
        }

        $prep = $dbc->prepare('
            SELECT
                batchID,
                batchName,
                owner,
                batchType,
                startDate,
                endDate
            FROM is4c_op.batches
            WHERE CURDATE() between startDate and endDate
                AND batchType = 1
                AND (batchName like "%Deals A%"
                    OR batchName like "%Deals B%")
            LIMIT 1;
        ');
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            if (strpos($row['batchName'],"Co-op Deals A")) {
                $date = new DateTime($row['endDate']);
                date_add($date, date_interval_create_from_date_string('2 days'));
                $this->session->cycleDate = sprintf("%s",$date->format('Y-m-d'));
            } elseif (strpos($row['batchName'],"Co-op Deals B")) {
                $date = new DateTime($row['startDate']);
                date_add($date, date_interval_create_from_date_string('-2 days'));
                $this->session->cycleDate = sprintf("%s",$date->format('Y-m-d'));
            }
        }

        return header('location: CoopDealsLookupPage.php?upc='.$upc);

    }

    function get_insert_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $batchID = FormLib::get('batches');
        $upc = FormLib::get('upc');
        $salePrice = FormLib::get('salePrice');

        $prep = $dbc->prepare('SELECT * FROM batches WHERE batchID = ?');
        $res = $dbc->execute($prep, $batchID);
        $row = $dbc->fetch_row($res);
        $batchID = $row['batchID'];
        $batchName = $row['batchName'];
        $args = array($upc, $batchID, $salePrice, $salePrice);

        $prep = $dbc->prepare('
            INSERT INTO batchList
            (upc, batchID, salePrice, groupSalePrice, active) VALUES (?,?,?,?,"1")
        ');
        $dbc->execute($prep,$args);

        if ($er = $dbc->error()) {
            return <<<HTML
<div class="row">
    <div class="col-md-6">
        <div class="alert alert-danger">$msg</div>
    </div>
</div>
<div class="row">
    <div class="col-md-2">
        <div class="form-group">
            <a class="btn btn-default form-control" href="CoopDealsLookupPage.php?upc={$upc}">Scan Another</a>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <a class="btn btn-default form-control" href="CoopDealsLookupPage.php">Start Over</a>
        </div>
    </div>
</div>
HTML;
        } else {
            $msg = "Item Added to Batch #$batchID";
            $b = new BatchesModel($dbc);
            if ($this->forceBatchOkay($batchID,$b)) {
                $b->forceStartBatch($batchID);
                $msg .= " & Batch #{$batchID} forced.";
            }

            return <<<HTML
<div class="row">
    <div class="col-md-6">
        <div class="alert alert-success">$msg</div>
    </div>
</div>
<div class="row">
    <div class="col-md-2">
        <div class="form-group">
            <a class="btn btn-default form-control" href="CoopDealsLookupPage.php?upc={$upc}">Scan Another</a>
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <a class="btn btn-default form-control" href="CoopDealsLookupPage.php">Start Over</a>
        </div>
    </div>
</div>
HTML;
        }

    }

    private function forceBatchOkay($id,$b)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $b->batchID($id);
        $b->load();
        $start = new DateTime($b->startDate());
        $end = new DateTime($b->endDate());
        $now = new DateTime(date('Y-m-d'));
        if ($now >= $start && $now <= $end) {
            return true;
        } else {
            return false;
        }
    }

    function get_upc_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (FormLib::get('linea') != 1) {
            $this->add_onload_command("\$('#upc').focus();\n");
        }
        $this->addOnloadCommand("enableLinea('#upc', function(){ \$('#upc-form').append('<input type=hidden name=linea value=1 />').submit(); });\n");
        $upc = $this->upc;
        $upc = str_pad($upc, 13, "0", STR_PAD_LEFT);
        $heading = '';

        $ret = '';
        $heading .= '
            <form id="upc-form" action="' . $_SERVER['PHP_SELF'] . '"  method="get" name="id" class="form-inline">
                <div class="form-group">
                    <input type="text" class="form-control" name="upc" id="upc" placeholder="Scan Barcode" autofocus>
                </div>
                <div class="form-group">
                    <input type="submit" class="btn btn-default" value="go"/>
                </div>
            </form>
        ';
        if ($this->session->cycleDate) {
            $heading .= "<b>Switch_Cycle</b>: Alternate cycle batches loaded.<br/>";
            $cycleB = true;
        } else {
            $cycleB = false;
        }
        $heading .= '<strong>Month</strong>: ' . $this->session->month . '<br>';
        $heading .= 'UPC: ' . $upc;

        //Check if product exists
        $args = array($upc);
        $prep = $dbc->prepare("SELECT * FROM products WHERE upc = ?");
        $res = $dbc->execute($prep, $args);
        if ($dbc->numRows($res) == 0) {
            $heading .= "<div class='alert alert-danger' align='center'>Product does not exist in POS</div>";
        }

        $month = $this->session->month;
        $mono = new DateTime($month);
        $mono = $mono->format('m');
        $args = array($upc, $month);
        $prep = $dbc->prepare('
            SELECT
                c.upc,
                c.abtpr AS flyerPeriod,
                p.brand,
                v.sku,
                p.description,
                c.price AS srp,
                m.super_name
            FROM CoopDealsItems AS c
                LEFT JOIN products AS p ON c.upc=p.upc
                LEFT JOIN vendorItems AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE c.upc = ?
                AND c.dealSet = ?
            LIMIT 1;
        ');
        $pPrep = $dbc->prepare('
            SELECT
                c.abtpr AS flyerPeriod,
                c.price AS srp
            FROM CoopDealsItems AS c
            WHERE c.upc = ?
                AND c.dealSet = ?
        ');
        $pRes = $dbc->execute($pPrep, $args);
        $flyerPeriod = ' ';
        while ($row = $dbc->fetchRow($pRes)) {
            $flyerPeriod .= $row['flyerPeriod'].",";
        }
        $flyerPeriod = substr_replace($flyerPeriod, "", -1);

        $res = $dbc->execute($prep, $args);
        $ret .=  "<table class='table table-bordered'  align='center' width='100%'>";
        $check = '';
        while ($row = $dbc->fetch_row($res)) {
            $upc = $row['upc'];
            $description = $row['description'];
            $brand = $row['brand'];
            $sku = $row['sku'];
            $srp = $row['srp'];
            $superName = $row['super_name'];
            $ret .=  '<tr><td><b>upc</td><td>' . $row['upc'] . '</tr>';
            $ret .=  '<td><b>Desc</b></td><td>' . $row['description'] . '</tr>';
            $ret .=  '<td><b>Brand</b></td><td>' . $row['brand'] . '</tr>';
            $ret .=  '<td><b>Flyer Period</b></td><td>' . $flyerPeriod . '</tr>';
            $ret .=  '<td><b>Sku</b></td><td>' . $row['sku'] . '</tr>';
            $srp = $row['srp'];
            $ret .= '<td><b>Sale Price</b></td><td>' . $srp . '</td></tr>';
            $ret .=  '<td><b>Department</b></td><td>' . $superName .'</td></tr>';
            $check = $row['upc'];
        }
        $ret .= '</table>';

        if ($dbc->error()) {
            $ret .= '<div class="alert alert-warning">' . $dbc->error() . '</div>';
        }

        $months = array();
        for ($i=1; $i<13; $i++) {
            $months[date('M')] = $i;
        }

        $year = date('Y');
        if (!isset($months[$this->session->month])) {
            $months[$this->session->month] = date('m');
        }
        $tmo = $months[$this->session->month] + 1;
        $checkMoStart = $year . '-' .$tmo . '-01 00:00:00';
        $checkMoEnd = $year . '-' .$tmo . '-31 00:00:00';

        if ($check == '') {
            $heading .= '<div class="alert alert-danger">Product not found in ' . $month . '.</div>';
        } else {
            if ($date = $this->session->cycleDate) {
                $datePicker = '"'.$date.'"';
            } else {
                $datePicker = "CURDATE()";
            }
            $curMonthQueryStr = "
                select
                    batchID,
                    batchName,
                    owner,
                    batchType
                from is4c_op.batches
                where {$datePicker} between startDate and endDate
                and batchType = 1;
            ";
            $curMonthQ = $dbc->prepare($curMonthQueryStr);

            $selMonthA = array($checkMoStart,$checkMoEnd);
            $cycleStr = ($cycleB === false) ? " AND (batchName LIKE '% A %'
                OR batchName LIKE '% TPR %')" : " AND batchName LIKE '% B %' ";
            $q = "
                SELECT
                    batchID,
                    batchName,
                    owner,
                    batchType
                FROM is4c_op.batches
                WHERE batchName like '%$month%'
                    AND startDate LIKE '$year%'
                        $cycleStr
                    AND batchType = 1;
            ";
            $selMonthQ = $dbc->prepare($q);
            $curMonth = date('F');
            if ($curMonth == $this->session->month) {
                $result = $dbc->execute($curMonthQ);
            } else {
                $result = $dbc->execute($selMonthQ,$selMonthA);
            }

            $batchIDs = array();
            while ($row = $dbc->fetchRow($result)) {
                $batchIDs[] = $row['batchID'];
            }
            list($inStr, $prodInBatchA) = $dbc->safeInClause($batchIDs);
            $prodInBatchA[] = $upc;
            $prodInBatchQ = 'SELECT bl.batchID, batchName from batchList AS bl 
                LEFT JOIN batches AS b ON b.batchID=bl.batchID WHERE bl.batchID IN ('.$inStr.') AND upc = ?';
            $prodInBatchP = $dbc->prepare($prodInBatchQ);
            $prodInBatchR = $dbc->execute($prodInBatchP,$prodInBatchA);
            $foundIn = array();
            while ($row = $dbc->fetchRow($prodInBatchR)) {
                $heading .= "<br/><span class='alert-success'>Item found in batch {$row['batchID']} : {$row['batchName']}</span>";
                $foundIn[] = $row['batchID'];
            }

            $ret .=  '
                <form method="get" class="">
                    <label>Sales Batches</label>
                    <div class="form-group">
                        <select class="form-control" name="batches">
            ';
            if ($curMonth == $this->session->month) {
                $result = $dbc->execute($curMonthQ);
            } else {
                $result = $dbc->execute($selMonthQ,$selMonthA);
            }
            $sel = "";
            while ($row = $dbc->fetchRow($result)) {
                $option = "option";
                $add = "";
                $batchID = $row['batchID'];
                if (in_array($batchID,$foundIn)) {
                    $option = "option style='background-color: tomato; color: white' ";
                    $add = "# ";
                }
                if (strpos(strtolower($row['batchName']), strtolower($superName)) !== false) {
                    if ($sel == "") {
                        $sel = "selected";
                    } else {
                        $sel = " ";
                    }
                }
                $ret .= "<$option value='$batchID' $sel>$add {$row['batchName']}</option>";
            }
            $ret .=  '
                    </select>
                    </div>
                    <div class="form-group">
                        <input type="submit" name="cycle" value="Switch_Cycle" class="btn btn-default form-control">
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-danger form-control" value="Add this item to batch">
                    </div>
                    <input type="hidden" name="insert" value="1">
                    <input type="hidden" name="upc" value="' . $upc . '">
                    <input type="hidden" name="salePrice" value="' . $srp . '">
                </form>
            ';
        }

        $ret .= $this->navBtns();

        return <<<HTML
<div class="row">
    <div class="col-md-6">
        $heading$ret
    </div>
</div>
HTML;
    }

    function get_month_view()
    {
        $this->session->month = FormLib::get('month');
        //$this->addScript('../autocomplete.js');
        //$this->addOnloadCommand("bindAutoComplete('#upc', '../../ws/', 'item');\n");
        if (FormLib::get('linea') != 1) {
            $this->addOnloadCommand("\$('#upc').focus();\n");
        }
        $this->addOnloadCommand("enableLinea('#upc', function(){ \$('#upc-form').append('<input type=hidden name=linea value=1 />').submit(); });\n");

        $ret = '';
        echo '<strong>Month</strong>: ' . $this->session->month . '<br>';

        $ret .= '
            <form id="upc-form" action="' . $_SERVER['PHP_SELF'] . '"  method="get" name="upc-form" class="">
                <input type="text" class="form-control" name="upc" id="upc" placeholder="Scan Barcode" autofocus>
                <input type="submit" class="btn btn-default" value="go">
            </form>
            '.$this->navBtns().'
        ';

        return $ret;
    }

    function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $curMonth = date('F');
        $dealSets = "";
        $prep = $dbc->prepare("SELECT dealSet FROM CoopDealsItems
            GROUP BY dealSet");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $dealSets .= "<option value='{$row['dealSet']}'>{$row['dealSet']}</option>";
        }

        return <<<HTML
<div class="row">
    <div class="col-md-3">
        <form method="get" name="useCurMo" class="">
            <div class="form-group">
                <input type="hidden" name="month" value="{$curMonth}">
            </div>
            <div class="form-group">
                <button type="submit" class="form-control btn btn-default" >Use Current Month</button>
            </div>
        </form>
        <form method="get" name="id-form" class="">
            or <label>Select a Month</label><br>
            <div class="form-group">
                <select name="month" id="month" class="form-control" style="text-align: center">
                {$dealSets}
                </select>&nbsp;
            </div>
            <div class="form-group">
                <button type="submit" class="form-control btn btn-default">Submit</button><br>
            </div>
        </form>
    </div>
</div>
{$this->navBtns()}
HTML;

    }

    private function navBtns()
    {
        $ret = '';
        $ret .= '
            <div class="row"><div class="col-md-2">
                <table class="table"><tbody>
                    <tr><td><a class="btn btn-default btn-xs wide" href="CoopDealsLookupPage.php">Select Month</a></td></tr>
                    <tr><td><a class="btn btn-default btn-xs wide" href="../../../../scancoord/ScannieV2/content/Scanning/BatchCheck/SCS.php">Batch Check</a></td></tr>
                    <tr><td><a class="btn btn-default btn-xs wide" href="../modules/plugins2.0/ShelfAudit/SaMenuPage.php">Exit</a></td></tr>
                </tbody></table>
            </div></div>
        ';
        return $ret;
    }

    public function javascript_content()
    {
        return <<<JAVASCRIPT
$('#month').on('change', function(){
    document.forms['id-form'].submit();
});
JAVASCRIPT;
    }

    public function css_content()
    {
        return <<<CSS
.wide {
    width: 150px;
}
CSS;
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('string', $this->get_view());
        $phpunit->assertInternalType('string', $this->get_month_view());
        $this->upc = '0000000000111';
        $phpunit->assertInternalType('string', $this->get_upc_view());
    }
}

FannieDispatch::conditionalExec();
