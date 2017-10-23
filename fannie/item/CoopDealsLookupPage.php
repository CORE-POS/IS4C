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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
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
            select
                batchID,
                batchName,
                owner,
                batchType
            from is4c_op.batches
            where CURDATE() between startDate and endDate
                and batchType = 1;
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
            return '<div class="alert alert-danger">' . $er . "</div>"
                . '<a class="btn btn-default" href="CoopDealsLookupPage.php">Return</a>';
        } else {
            $msg = "Item Added to Batch";
            $b = new BatchesModel($dbc);
            if ($this->forceBatchOkay($batchID,$b)) {
                $b->forceStartBatch($batchID);
                $msg .= " & Batch #{$batchID} forced.";
            }
            return '<div class="alert alert-success">'.$msg.'</div>'
                . '<a class="btn btn-default" href="CoopDealsLookupPage.php">Return</a>';
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

        $ret = '';
        echo 'Month: ' . strtoupper($this->session->month) . '<br>';
        if (FormLib::get('linea') != 1) {
            $this->add_onload_command("\$('#upc').focus();\n");
        }
        $this->addOnloadCommand("enableLinea('#upc', function(){ \$('#upc-form').append('<input type=hidden name=linea value=1 />').submit(); });\n");

        $ret .= '
            <form id="upc-form" action="' . $_SERVER['PHP_SELF'] . '"  method="get" name="id" class="form-inline">
                <input type="text" class="form-control" name="upc" id="upc" placeholder="Scan Barcode" autofocus>
                <input type="submit" class="btn btn-default" value="go">
            </form>
        ';
        $dbc = FannieDB::get('woodshed_no_replicate');
        $upc = FormLib::get('upc');
        $upc = str_pad($upc, 13, "0", STR_PAD_LEFT);
        echo 'UPC: ' . $upc;

        $month = 'CoopDeals' . $this->session->month;
        $args = array($month, $upc);
        $prep = $dbc->prepare('
            SELECT
                upc,
                flyerPeriod,
                brand,
                sku,
                description,
                srp
            FROM ' . $month . '
            WHERE upc = ?;
        ');
        $res = $dbc->execute($prep, $upc);
        $ret .=  "<table class='table'  align='center' width='100%'>";
        $check = '';
        while ($row = $dbc->fetch_row($res)) {
            $upc = $row['upc'];
            $description = $row['description'];
            $brand = $row['brand'];
            $flyerPeriod = $row['flyerPeriod'];
            $sku = $row['sku'];
            $srp = $row['srp'];
            $ret .=  '<tr><td><b>upc</td><td>' . $row['upc'] . '</tr>';
            $ret .=  '<td><b>Desc</b></td><td>' . $row['description'] . '</tr>';
            $ret .=  '<td><b>Brand</b></td><td>' . $row['brand'] . '</tr>';
            $ret .=  '<td><b>Flyer Period</b></td><td>' . $row['flyerPeriod'] . '</tr>';
            $ret .=  '<td><b>Sku</b></td><td>' . $row['sku'] . '</tr>';
            $srp = $row['srp'];
            $ret .= '<td><b>Sale Price</b></td><td>' . $srp . '</td></tr>';
            $ret .=  '<td><b>Sale Period</b></td><td>' . substr($month, 9) . '</td></tr>';
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
        $checkMoStart = $year . '-' .$months[$this->session->month] . '-01 00:00:00';
        $checkMoEnd = $year . '-' .$months[$this->session->month] . '-31 00:00:00';

        if ($check == '') {
            echo '<div class="alert alert-danger">Product not found in ' . $month . '.</div>';
        } else {
            if ($date = $this->session->cycleDate) {
                $datePicker = '"'.$date.'"';
                //echo "<h1>".$date."</h1>";
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
            $selMonthQ = $dbc->prepare('
                select
                    batchID,
                    batchName,
                    owner,
                    batchType
                from is4c_op.batches
                where startDate between ? and ?
                    and batchType = 1;
            ');

            $curMonth = date('M');
            if ($curMonth == $this->session->month) {
                $result = $dbc->execute($curMonthQ);
            } else {
                $result = $dbc->execute($selMonthQ,$selMonthA);
            }

            $ret .=  '
                <form method="get" class="form-inline">
                    Sales Batches<br>
                    <select class="form-control" name="batches">
            ';
            while ($row = $dbc->fetchRow($result)) {
                $ret .=  '<option value="' . $row['batchID'] . '">' . $row['batchName'] . '</option>';
            }
            $ret .=  '
                <input type="submit" name="cycle" value="Switch_Cycle" class="btn btn-default">
                </select><br>
                    <input type="submit" class="btn btn-danger" value="Add this item to batch">
                    <input type="hidden" name="insert" value="1">
                    <input type="hidden" name="upc" value="' . $upc . '">
                    <input type="hidden" name="salePrice" value="' . $srp . '">
                </form>
            ';
        }

        $ret .= $this->navBtns();

        return $ret;

    }

    function get_month_view()
    {
        $this->session->month = FormLib::get('month');
        //$this->add_script('../autocomplete.js');
        //$this->add_onload_command("bindAutoComplete('#upc', '../../ws/', 'item');\n");
        if (FormLib::get('linea') != 1) {
            $this->add_onload_command("\$('#upc').focus();\n");
        }
        $this->addOnloadCommand("enableLinea('#upc', function(){ \$('#upc-form').append('<input type=hidden name=linea value=1 />').submit(); });\n");

        $ret = '';
        echo 'Month: ' . strtoupper($this->session->month) . '<br>';

        $ret .= '
            <form id="upc-form" action="' . $_SERVER['PHP_SELF'] . '"  method="get" name="upc-form" class="form-inline">
                <input type="text" class="form-control" name="upc" id="upc" placeholder="Scan Barcode" autofocus>
                <input type="submit" class="btn btn-default" value="go">
            </form>
            '.$this->navBtns().'
        ';

        return $ret;
    }

    function get_view()
    {
        $curMonth = date('M');

        return '
            <form method="get" name="useCurMo" class="form-inline">
                <input type="hidden" name="month" value="' . $curMonth . '">
                <button type="submit" class="form-control btn btn-default" >Use Current Month</button>
            </form><br>

            <form method="get" name="id-form" class="form-inline">
                or <label>Select a Month</label><br>
                <select name="month" class="form-control" style="text-align: center;">
                    <option value="Jan">January</option>
                    <option value="Feb">February</option>
                    <option value="Mar">March</option>
                    <option value="Apr">April</option>
                    <option value="May">May</option>
                    <option value="Jun">June</option>
                    <option value="Jul">July</option>
                    <option value="Aug">August</option>
                    <option value="Sep">September</option>
                    <option value="Oct">October</option>
                    <option value="Nov">November</option>
                    <option value="Dec">December</option>
                </select>&nbsp;
                <button type="submit" class="form-control btn btn-default">Submit</button><br>
            </form>
            '.$this->navBtns().'
        ';

    }

    private function navBtns()
    {
        $ret .= '';
        $ret .= '
            <br />
            <ul>
                <li><a class="" href="CoopDealsLookupPage.php">Select Month</a></li>
                <li><a class="" href="../../../scancoord/item/SalesChange/SCScanner.php">Batch Check</a></li>
                <li><a class="" href="../modules/plugins2.0/ShelfAudit/SaMenuPage.php">Exit</a></li>
            </ul>
        ';
        return $ret;
    }

}

FannieDispatch::conditionalExec();
