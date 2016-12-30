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

       return parent::preprocess();
    }
    
    function get_insert_view()
    {
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $batchID = FormLib::get('batches');
        $upc = FormLib::get('upc');
        $salePrice = FormLib::get('salePrice');
        
        $prep = $dbc->prepare('
            SELECT * FROM batches WHERE batchID = ?
        ');
        $res = $dbc->execute($prep, $batchID);
        $row = $dbc->fetch_row($res);
        $batchID = $row['batchID'];
        $batchName = $row['batchName'];
        $args = array($upc, $batchID, $salePrice, $salePrice);
        
        $prep = $dbc->prepare('
            INSERT INTO batchList
            (upc, batchID, salePrice, groupSalePrice, active)
            VALUES (
                ?,
                ?,
                ?,
                ?,
                "1"
            )
        ');
        $dbc->execute($prep, $args);
        if ($dbc->error()) {
            return '<div class="alert alert-danger">' . $dbc->error(). "</div>"
                . '<a class="btn btn-default" href="http://192.168.1.2/git/fannie/item/CoopDealsLookupPage.php">Return</a>';
        } else {
            return '<div class="alert alert-success">Item Added to Batch</div>'
                . '<a class="btn btn-default" href="http://192.168.1.2/git/fannie/item/CoopDealsLookupPage.php">Return</a>';
        }
        
        //  Update 'Missing Sign' queue in SaleChangeQueues. 
        if (isset($_SESSION['session'])) $session = $_SESSION['session'];
        if (isset($_SESSION['store_id'])) $store_id = $_SESSION['store_id'];
        
        if (isset($session) && isset($store_id)) {
            $dbc = FannieDB::get('woodshed_no_replicate');
            $argsB = array($upc, $store_id, $session);
            $prepB = $dbc->prepare('
                INSERT INTO woodshed_no_replicate.SaleChangeQueues
                (queue, upc, store_id, session)
                VALUES (
                    8,
                    ?,
                    ?,
                    ?
                )
            ');
            $dbc->execute($prepB, $argsB);    
        }        
    }
    
    function get_upc_view()
    {
        
        $ret = '';
        echo 'Month: ' . strtoupper($_SESSION['month']) . '<br>';
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

        $month = 'CoopDeals' . $_SESSION['month'];
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

        if ($check == '') {
            echo '<div class="alert alert-danger">Product not found in ' . $month . '.</div>';
        } else {
            $query = '
                select 
                    batchID, 
                    batchName, 
                    owner,
                    batchType
                from is4c_op.batches 
                where CURDATE() between startDate and endDate
                    and batchType = 1;
            ';
            $result = $dbc->query($query);
            $ret .=  '
                <form method="get" class="form-inline">
                    Current Sales Batches<br>
                    <select class="form-control" name="batches">
            ';
            while ($row = $dbc->fetchRow($result)) {
                $ret .=  '<option value="' . $row['batchID'] . '">' . $row['batchName'] . '</option>';
            }
            $ret .=  '
                </select><br>
                    <input type="submit" class="btn btn-danger" value="Add this item to batch">
                    <input type="hidden" name="insert" value="1">
                    <input type="hidden" name="upc" value="' . $upc . '">
                    <input type="hidden" name="salePrice" value="' . $srp . '">
                </form>
            ';   
        }
       
        $ret .= '<br><a class="btn btn-default" href="http://key/scancoord/item/SalesChange/SaleChangeScanner.php">
            Back to Sign info<br>Scanner</a><br><br>';
        
        return $ret;
        
    }
    
    function get_month_view() 
    {
        $_SESSION['month'] = FormLib::get('month');
        //$this->add_script('../autocomplete.js');
        //$this->add_onload_command("bindAutoComplete('#upc', '../../ws/', 'item');\n");
        if (FormLib::get('linea') != 1) {
            $this->add_onload_command("\$('#upc').focus();\n");
        }
        $this->addOnloadCommand("enableLinea('#upc', function(){ \$('#upc-form').append('<input type=hidden name=linea value=1 />').submit(); });\n");
        
        $ret = '';  
        echo 'Month: ' . strtoupper($_SESSION['month']) . '<br>';
        $ret .= '
            <form id="upc-form" action="' . $_SERVER['PHP_SELF'] . '"  method="get" name="upc-form" class="form-inline">
                <input type="text" class="form-control" name="upc" id="upc" placeholder="Scan Barcode" autofocus>
                <input type="submit" class="btn btn-default" value="go">
            </form>
            <a class="btn btn-default" href="http://key/scancoord/item/SalesChange/SaleChangeScanner.php">
            Back to Sign info<br>Scanner</a><br><br>
        ';

        return $ret;
    }

    function get_view() 
    {
        $curMonth = date('M');
        if($curMonth == 'Jul') {
            $curMonth = 'July';
        } elseif($curMonth == 'Jun') {
            $curMonth = 'June';
        } 
        
        return '
            <form method="get" name="useCurMo" class="form-inline">
                <input type="hidden" name="month" value="' . $curMonth . '">
                <input type="submit" class="btn btn-default" value="Use Current Month">
            </form><br>

            <form method="get" name="id-form" class="form-inline">
                or <label>Select a Month</label><br>
                <select name="month" class="form-control">
                    <option value="Jan">January</option>
                    <option value="Feb">February</option>
                    <option value="Mar">March</option>
                    <option value="Apr">April</option>
                    <option value="May">May</option>
                    <option value="June">June</option>
                    <option value="July">July</option>
                    <option value="Aug">August</option>
                    <option value="Sep">September</option>
                    <option value="Oct">October</option>
                    <option value="Nov">November</option>
                    <option value="Dec">December</option>
                </select>&nbsp;
                <input type="submit" class="form-control"><br>
            </form>
        ';

    }
    
}
    
FannieDispatch::conditionalExec();
