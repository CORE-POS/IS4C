<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

/**
  @class SaPriceChangePage
  Scan an item and display price
  If a price change batch record is present for the item,
  provide a button to apply that change
*/
class SaPriceChangePage extends FannieRESTfulPage {
    protected $window_dressing = False;
    private $section=0;
    private $current_item_data=array();
    private $linea_ios_mode = False;
    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Price Change] checks for available price change batches
    on a given item and applies them.';

    private function linea_support_available(){
        global $FANNIE_ROOT;
        if (file_exists($FANNIE_ROOT.'src/javascript/linea/cordova-2.2.0.js')
        && file_exists($FANNIE_ROOT.'src/javascript/linea/ScannerLib-Linea-2.0.0.js'))
            return True;
        else
            return False;
    }

    function preprocess(){
        global $FANNIE_URL;

        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');

        $this->linea_ios_mode = $this->linea_support_available();
        if ($this->linea_ios_mode){
            $this->add_script($FANNIE_URL.'src/javascript/linea/cordova-2.2.0.js');
            $this->add_script($FANNIE_URL.'src/javascript/linea/ScannerLib-Linea-2.0.0.js');
        }

        $this->__routes[] = 'post<upc><price>';
        
        return parent::preprocess();
    }

    function post_upc_price_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $prod = new ProductsModel($dbc);
        $prod->upc(BarcodeLib::padUPC($this->upc));
        $prod->normal_price($this->price);
        $prod->save();
        $prod->pushToLanes();

        $this->id = $this->upc;
        return $this->get_id_handler();
    }

    function get_id_handler(){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $prodQ = $dbc->prepare_statement('SELECT upc, description, normal_price
                        FROM products WHERE upc=?');
        $upc = BarcodeLib::padUPC($this->id);
        $prodR = $dbc->exec_statement($prodQ, array($upc));

        if ($dbc->num_rows($prodR) == 0){
            echo '<span class="error">No item found for: '.$upc.'</span>';
            return False;
        }

        $prodW = $dbc->fetch_row($prodR);

        echo '<span class="o_upc">'.$prodW['upc'].'</span> ';
        echo '<span class="o_desc">'.$prodW['description'].'</span> ';
        echo '<span class="o_price">'.sprintf('$%.2f',$prodW['normal_price']).'</span>';
        
        $pendR = 0;
        if ($dbc->table_exists('batchListTest')){
            $pendQ = $dbc->prepare_statement('SELECT salePrice FROM batchListTest as l
                            LEFT JOIN batchTest AS b ON l.batchID=b.batchID WHERE
                            b.discountType=0 AND l.upc=? ORDER BY l.batchID DESC');
            $pendR = $dbc->exec_statement($pendQ, array($upc));
        }

        if ($pendR === 0 || $dbc->num_rows($pendR) == 0){
            $pendQ = $dbc->prepare_statement('SELECT salePrice FROM batchList as l
                            LEFT JOIN batches AS b ON l.batchID=b.batchID WHERE
                            b.discountType=0 AND l.upc=? ORDER BY l.batchID DESC');
            $pendR = $dbc->exec_statement($pendQ, array($upc));
        }

        // no pending price change batch
        if ($dbc->num_rows($pendR) == 0)
            return False;

        $pendW = $dbc->fetch_row($pendR);
        // latest price change already applied
        if ($pendW['salePrice'] == $prodW['normal_price'])
            return False;

        echo '<div style="padding-left:25px;">';
        printf('<input class="pending" onclick="do_pricechange(\'%s\',%f); return false;"
                type="submit" value="Update Price to %.2f" />',$upc,
                $pendW['salePrice'],$pendW['salePrice']);
        echo '</div>';
        return False;
    }

    function css_content(){
        ob_start();
        ?>
input.addButton {
    width: 60px;
    height: 50px;
    background-color: #090;
    color: #fff;
    font-weight: bold;
    font-size: 135%;
}
input.focused {
    background: #ffeebb;
}
input.pending {
    font-weight: bold;
    background-color: #090;
    font-size: 135%;
    color: #fff;
}
        <?php
        return ob_get_clean();
    }

    function javascript_content(){
        ob_start();
        if ($this->linea_ios_mode){ 
        ?>
Device = new ScannerDevice({
    barcodeData: function (data, type){
        var upc = data.substring(0,data.length-1);
        if ($('#upc_in').length > 0){
            $('#upc_in').val(upc);
            $('#goBtn').click();
        }
    },
    magneticCardData: function (track1, track2, track3){
    },
    magneticCardRawData: function (data){
    },
    buttonPressed: function (){
    },
    buttonReleased: function (){
    },
    connectionState: function (state){
    }
});
ScannerDevice.registerListener(Device);
        <?php
        }
        ?>
function lookupItem(){
    var upc = $('#upc_in').val();
    if (upc == '') return false;
    $('#upc_in').val('');
    $.ajax({
        url: 'SaPriceChangePage.php',
        type: 'get',
        data: 'id='+upc,
        success: function(data){
            $('#output_area').html(data);
        }
    });
}
function do_pricechange(upc, newprice){
    $.ajax({
        url: 'SaPriceChangePage.php',
        type: 'post',
        data: 'upc='+upc+'&price='+newprice,
        success: function(data){
            $('#output_area').html(data);
            $('#upc_in').focus();
        }
    });
}
        <?php
        return ob_get_clean();
    }

    function get_view(){
        ob_start();
        $elem = '#upc_in';
        if (isset($this->current_item_data['upc']) && isset($this->current_item_data['desc'])) $elem = '#cur_qty';
        ?>
<html>
<head><title>Price Check</title></head>
<body onload="$('<?php echo $elem; ?>').focus();">
<form onsubmit="lookupItem(); return false;" method="get" id="upcScanForm">
<div style="float: left;">
<a href="SaMenuPage.php">Menu</a><br />
<b>UPC</b>: <input type="number" size="10" name="upc_in" id="upc_in" 
    class="focused" />
</div>
<div style="float: left;">
<input type="submit" value="Go" class="addButton" id="goBtn" />
</div>
<div style="clear:left;"></div>
</form>
<hr />
<div id="output_area"></div>
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

?>
