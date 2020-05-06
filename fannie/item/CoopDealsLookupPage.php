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

       $this->__routes[] = 'get<cycle>';
       $this->__routes[] = 'get<month><upc>';
       $this->__routes[] = 'get<insert>';
       $this->__routes[] = 'get<month>';

       return parent::preprocess();
    }


    function get_insert_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $batchID = FormLib::get('batchID');
        $upc = FormLib::get('upc');
        $salePrice = FormLib::get('salePrice');
        $alertType = null;

        $prep = $dbc->prepare('SELECT * FROM batchList WHERE batchID = ? AND upc = ?');
        $res = $dbc->execute($prep, array($batchID, $upc));
        $rows = $dbc->numRows($res);
        if ($rows > 0) {
            $alertType = 'info'; 
            $msg = "Item already found in batch #$batchID";
        } else {
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
                $alertType = 'danger';
                $msg = $er;
            } else {
                $alertType = 'success';
                $msg = "Item Added to Batch #$batchID";
                $b = new BatchesModel($dbc);
                if ($this->forceBatchOkay($batchID,$b)) {
                    $b->forceStartBatch($batchID);
                    $msg .= " & Batch #{$batchID} forced.";
                }
            }
        }
        

        return <<<HTML
<div class="row">
    <div class="col-md-6">
        <div class="alert alert-$alertType">$msg</div>
    </div>
</div>
<div class="row">
    <div class="col-md-2">
        <div class="form-group">
            <a class="btn btn-default form-control" onclick="window.history.back(); return false;">Back</a>
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

    public function get_month_upc_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (FormLib::get('linea') != 1) {
            $this->add_onload_command("\$('#upc').focus();\n");
        }
        $this->addOnloadCommand("enableLinea('#upc', function(){ \$('#upc-form').append('<input type=hidden name=linea value=1 />').submit(); });\n");
        $upc = trim(FormLib::get('upc'));
        $upc = str_pad($upc, 13, "0", STR_PAD_LEFT);
        $cycle = FormLib::get('cycle');
        $heading = '';

        $ret = '';

        //Check if product exists
        $args = array($upc);
        $prep = $dbc->prepare("SELECT * FROM products WHERE upc = ?");
        $res = $dbc->execute($prep, $args);
        if ($dbc->numRows($res) == 0) {
            $heading .= "<div class='alert alert-danger' align='center'>Product does not exist in POS</div>";
        }

        //$month = $this->session->month;
        $month = FormLib::get('month');
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
        $ret .=  "<div class='table-responsive'>
            <table class='table table-bordered'  align='center' width='100%'>";
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
        $ret .= '</table></div>';

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

            $selMonthA = array($checkMoStart,$checkMoEnd);
            $cycleStr = ($cycle != 'B') ? " AND (batchName LIKE '% A %'
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
            $result = $dbc->execute($selMonthQ,$selMonthA);

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
                <form method="get" class="" id="upc-form">
                    <label>Sales Batches</label>
                    <div class="form-group">
                        <select class="form-control select-batch" id="select-batch" name="batches">
            ';
            $result = $dbc->execute($selMonthQ,$selMonthA);
            while ($row = $dbc->fetchRow($result)) {
                $sel = "";
                $option = "option";
                $add = "";
                $batchID = $row['batchID'];
                $condensed = $row['batchName'];
                $condensed = str_replace("Co-op Deals", '', $condensed);
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
                $ret .= "<$option value='$batchID' $sel>$add $condensed</option>";
            }
            $cycleA = ($cycle != 'B') ? 'checked' : '';
            $cycleB = ($cycle == 'B') ? 'checked' : '';
            $ret .=  '
                    </select>
                    </div>
                    <label for="radioGroup">Cycle</label>
                    <div class="form-group" id="radioGroup">
                        <input type="radio" id="A" name="cycle" value="A" '.$cycleA.'>
                        <label for="A">A</label>
                    </div>
                    <div class="form-group">
                        <input type="radio" id="B" name="cycle" value="B" '.$cycleB.'>
                        <label for="B">B</label>
                    </div>
            ';
        }

        return <<<HTML
<div class="row">
    <div class="col-lg-3"></div>
    <div class="col-lg-6">
        <form id="upc-form" action="{$_SERVER['PHP_SELF']}"  method="get" name="upc-form">
            {$this->monthOptions()}
            {$this->upcInput()}
            $heading
            $ret
        </form>
        <form id="insert-form" action="{$_SERVER['PHP_SELF']}"  method="get" name="insert-form">
            <div class="form-group">
                <input type="submit" class="btn btn-danger form-control" value="Add this item to batch">
            </div>
            <input type="hidden" name="insert" value="1">
            <input type="hidden" name="salePrice" value="$srp">
            <input type="hidden" name="batchID" id="batchID" >
            <input type="hidden" name="upc" value="$upc">
        </form>
    </div>
    <div class="col-lg-3"></div>
</div>
<div class="row">
    <div class="col-lg-4"></div>
    <div class="col-lg-4">
        <div class="row">
            <div class="col-lg-3"></div>
            <div class="col-lg-3">{$this->navBtns()}</div>
            <div class="col-lg-3"></div>
        </div>
    </div>
    <div class="col-lg-4"></div>
</div>
HTML;
    }

    function get_month_view()
    {
        $this->session->month = FormLib::get('month');
        if (FormLib::get('linea') != 1) {
            $this->addOnloadCommand("\$('#upc').focus();\n");
        }
        $this->addOnloadCommand("enableLinea('#upc', function(){ \$('#upc-form').append('<input type=hidden name=linea value=1 />').submit(); });\n");

        return <<<HTML
<div class="row">
    <div class="col-lg-4"></div>
    <div class="col-lg-4">
        <form id="upc-form" action="{$_SERVER['PHP_SELF']}"  method="get" name="upc-form" class="">
            {$this->monthOptions()}
            <div class="form-group">
                <input type="text" class="form-control" name="upc" id="upc" placeholder="Scan Barcode" autofocus>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-default" value="Submit UPC">
            </div>
        </form>
    </div>
    <div class="col-lg-4"></div>
</div>
<div class="row">
    <div class="col-lg-4"></div>
    <div class="col-lg-4">
        <div class="row">
            <div class="col-lg-3"></div>
            <div class="col-lg-3">
                {$this->navBtns()}
            </div>
            <div class="col-lg-3"></div>
        </div>
    </div>
    <div class="col-lg-4"></div>
</div>
HTML;
    }

    public function monthOptions()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $prep = $dbc->prepare("SELECT dealSet FROM CoopDealsItems GROUP BY dealSet");
        $res = $dbc->execute($prep);
        $options = array();
        while ($row = $dbc->fetchRow($res)) {
            try {
                $date = new DateTime($row['dealSet']);
                $options[$date->format('m')] = $row['dealSet'];
            } catch (Exception $ex) {
                $options[$row['dealSet']] = $row['dealSet'];
            }
        }
        ksort($options);
        $opts = '';
        foreach ($options as $option) {
            $sel = (FormLib::get('month') == $option) ? 'selected' : '';
            $opts .= "<option value='$option' $sel>$option</option>";
        }

        return <<<HTML
<label for="month">Month</label><br>
<select name="month" id="month" class="form-control" style="text-align: center">
    <option value="false">Select a Month</option>
    {$opts}
</select>&nbsp;
HTML;
    }

    public function upcInput()
    {
        $upc = FormLib::get('upc');

        return <<<HTML
<div class="form-group">
    <input type="text" class="form-control" name="upc" id="upc" value="$upc"
        placeholder="Scan Barcode" autofocus>
</div>
<div class="form-group" align="center">
    <button class="btn btn-default">Submit</button>
</div>
HTML;
    }

    function get_view()
    {

        $monthOpts = $this->monthOptions();

        return <<<HTML
<div class="row">
    <div class="col-lg-4"></div>
    <div class="col-lg-4">
        <form method="get" name="upc-form" class="">
            <div class="form-group">
                {$monthOpts}
            </div>
            <div class="form-group">
                <button type="submit" class="form-control btn btn-default">Submit</button><br>
            </div>
        </form>
    </div>
    <div class="col-lg-4"></div>
</div>
<div class="row">
    <div class="col-lg-4"></div>
    <div class="col-lg-4">
        <div class="row">
            <div class="col-lg-3"></div>
            <div class="col-lg-3">{$this->navBtns()}</div>
            <div class="col-lg-3"></div>
        </div>
    </div>
    <div class="col-lg-4"></div>
</div>
HTML;

    }

    private function navBtns()
    {
        $ret = '';
        $ret .= '
            <div class="row"><div class="col-md-2">
                <table class="table"><tbody>
                    <tr><td><a class="btn btn-default btn-xs wide" href="../../../../Scannie/content/Scanning/BatchCheck/SCS.php">Batch Check</a></td></tr>
                    <tr><td><a class="btn btn-default btn-xs wide" href="../modules/plugins2.0/ShelfAudit/SaMenuPage.php">Menu</a></td></tr>
                </tbody></table>
            </div></div>
        ';
        return $ret;
    }

    public function javascript_content()
    {
        return <<<JAVASCRIPT
$('#month').on('change', function(){
    document.forms['upc-form'].submit();
});
$('input:radio').change(function(){
    document.forms['upc-form'].submit();
});

// this also needs to be done on-load, as select may not be changed
$('select.select-batch').change(function(){
    var batchID = $(this).find('option:selected').val();
    $('#batchID').val(batchID); 
});
$(document).ready(function(){
    var batchID = $('#select-batch').find('option:selected').val(); 
    $('#batchID').val(batchID); 
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
    }
}

FannieDispatch::conditionalExec();
