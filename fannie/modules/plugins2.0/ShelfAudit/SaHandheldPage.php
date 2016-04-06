<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
  @class SaHandheldPage
*/
class SaHandheldPage extends FanniePage {
    private $section=0;
    protected $current_item_data=array();
    private $linea_ios_mode = False;

    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Handheld] is an interface for scanning and entering quantities on
    hand using a handheld device.';
    public $themed = true;
    protected $title = 'ShelfAudit Inventory';
    protected $header = '';

    private function linea_support_available(){
        global $FANNIE_ROOT;
        if (file_exists($FANNIE_ROOT.'src/javascript/linea/cordova-2.2.0.js')
        && file_exists($FANNIE_ROOT.'src/javascript/linea/ScannerLib-Linea-2.0.0.js'))
            return True;
        else
            return False;
    }

    function preprocess(){
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB, $FANNIE_URL;

        /**
          Store session in browser section.
        */
        if (ini_get('session.auto_start')==0 && !headers_sent() && php_sapi_name() != 'cli' && session_id() == '') {
            @session_start();
        }
        if (!isset($_SESSION['SaPluginSection']))
            $_SESSION['SaPluginSection'] = 0;
        $this->section = $_SESSION['SaPluginSection'];

        /* ajax callbacks */
        $ajax = FormLib::get_form_value('action','');
        /* save new quantity */
        if ($ajax === 'save'){
            $upc = FormLib::get_form_value('upc','');
            $qty = FormLib::get_form_value('qty',0);
            $store = FormLib::get('store', 0);

            $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ShelfAuditDB']);
            $delP = $dbc->prepare('DELETE FROM sa_inventory
                    WHERE upc=? AND clear=0 AND section=? AND storeID=?');
            $insP = $dbc->prepare('INSERT INTO sa_inventory (datetime,upc,clear,quantity,section,storeID)
                    VALUES ('.$dbc->now().',?,0,?,?,?)');
            $dbc->execute($delP, array($upc, $this->section, $store));
            if ($qty > 0){
                $dbc->execute($insP, array($upc, $qty, $this->section, $store));
            }
            echo $qty;
            echo 'quantity updated';
            return False;
        }

        /* upc scan; lookup item */
        $upc = FormLib::get_form_value('upc_in','');
        if ($upc !== ''){
            $dbc = FannieDB::getReadOnly($FANNIE_OP_DB);
            $upc = BarcodeLib::padUPC($upc);
            $this->current_item_data['upc'] = $upc;     
            $store = FormLib::get('store', 0);
            $q = 'SELECT p.description,v.brand,s.quantity,v.units FROM
                products AS p LEFT JOIN vendorItems AS v ON p.upc=v.upc
                LEFT JOIN '.$FANNIE_PLUGIN_SETTINGS['ShelfAuditDB'].$dbc->sep().
                'sa_inventory AS s ON p.upc=s.upc AND s.clear=0 AND s.storeID=?
                WHERE p.upc=? 
                ORDER BY v.vendorID';
            $p = $dbc->prepare($q);
            $r = $dbc->execute($p,array($store, $upc));
            if($dbc->num_rows($r)==0){
                // try again; item on-hand but not in products
                $q = 'SELECT v.description,v.brand,s.quantity,v.units FROM
                    vendorItems AS v 
                    LEFT JOIN '.$FANNIE_PLUGIN_SETTINGS['ShelfAuditDB'].$dbc->sep().
                    'sa_inventory AS s ON s.upc=v.upc AND s.clear=0 AND s.storeID=?
                    WHERE v.upc=? 
                    ORDER BY v.vendorID';
                $p = $dbc->prepare($q);
                $r = $dbc->execute($p,array($store, $upc));
            }

            
            while($w = $dbc->fetch_row($r)){
                if (!isset($this->current_item_data['desc'])){
                    $this->current_item_data['desc'] = $w['brand'].' '.$w['description'];
                }
                if (!isset($this->current_item_data['qty'])){
                    $this->current_item_data['qty'] = is_numeric($w['quantity']) ? $w['quantity'] : 0;
                }
                if (!isset($this->current_item_data['case_sizes'])){
                    $this->current_item_data['case_sizes'] = array();
                }
                if ($w['units'] > 0)
                    $this->current_item_data['case_sizes'][] = $w['units'];
            }
        }

        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');

        $this->linea_ios_mode = $this->linea_support_available();
        if ($this->linea_ios_mode){
            $this->add_script($FANNIE_URL.'src/javascript/linea/cordova-2.2.0.js');
            $this->add_script($FANNIE_URL.'src/javascript/linea/ScannerLib-Linea-2.0.0.js');
        }
        
        return True;
    }

    function css_content(){
        ob_start();
        ?>
input#cur_qty {
    font-size: 135%;
    font-weight: bold;
}
input.focused {
    background: #ffeebb;
}
        <?php
        return ob_get_clean();
    }

    function javascript_content(){
        ob_start();
        ?>
function paint_focus(elem){
    if (elem == 'upc_in'){
        $('#upc_in').addClass('focused');
        $('#cur_qty').removeClass('focused');
    }
    else {
        $('#cur_qty').addClass('focused');
        $('#upc_in').removeClass('focused');
    }
}
function update_qty(amt){
    var cur = Number($('#cur_qty').val());
    if (cur + amt < 0)
        cur = 0;
    else
        cur = cur+amt;
    $('#cur_qty').val(cur);

    cur += Number($('#old-qty').html());
    $('#live-qty').html(cur);

    // save new quantity, return cursor to upc input
    var args = 'action=save&upc='+$('#cur_upc').val()+'&qty='+cur+'&store='+$('#store').val();
    $.ajax({
        data: args,
        cache: false,
        error: function(){
            $('#upc_in').focus();
            paint_focus('upc_in');
        },
        success: function(){
            $('#upc_in').focus();
            paint_focus('upc_in');
        }
    });
}

function qty_typed(ev){
    var cur = Number($('#cur_qty').val()) + Number($('#old-qty').html());
    $('#live-qty').html(cur);
    // save new quantity, return cursor to upc input
    var args = 'action=save&upc='+$('#cur_upc').val()+'&qty='+cur+'&store='+$('#store').val();
    $.ajax({
        data: args,
        cache: false,
        error: function(){
        },
        success: function(){
        }
    });
    if (ev.keyCode==13){
        $('#upc_in').focus();
        paint_focus('upc_in');
    }
    else if (ev.keyCode >= 37 && ev.keyCode <= 40){
        $('#upc_in').focus();
        paint_focus('upc_in');
    }
}

function doubleBeep()
{
    if (typeof cordova.exec != 'function') {
        setTimeout('doubleBeep()', 500);
    } else {
        if (Device) {
            Device.playSound([500, 100, 0, 100, 1000, 100, 0, 100, 500, 100]);
        }
    }
}

        <?php if ($this->linea_ios_mode){ ?>
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
        <?php } ?>

        <?php
        return ob_get_clean();
    }

    protected function upcForm($elem, $store)
    {
        ?>
<form method="get" id="upcScanForm">
<a href="SaMenuPage.php">Menu</a>
 - Store # <?php echo $store; ?>
<input type="hidden" name="store" id="store" value="<?php echo ((int)$store); ?>" />
<br />
<div class="form-group form-inline">
    <div class="input-group">
        <label class="input-group-addon">UPC</label>
        <input type="number" size="10" name="upc_in" id="upc_in" 
            onfocus="paint_focus('upc_in');"
            <?php echo ($elem=='#upc_in')?'class="focused form-control"':'class="form-control"'; ?> 
        />
    </div>
    <button type="submit" class="btn btn-success" id="goBtn">Go</button>
</div>
</form>
        <?php
    }

    protected function qtyForm($elem)
    {
        echo '<p>';
        echo $this->current_item_data['upc'];
        echo ' ';
        echo $this->current_item_data['desc'];
        echo '<br />';
        echo '<strong>Current Qty</strong>: '
            . '<span id="old-qty" class="collapse">' . $this->current_item_data['qty'] . '</span>'
            . '<span id="live-qty">' . $this->current_item_data['qty'] . '</span>';
        echo '</p>';
        echo '<div class="form-group form-inline">';
        printf('<input type="number" min="-99999" max="99999" step="0.01" %s
            onfocus="paint_focus(\'cur_qty\');$(this).select();" 
            onkeyup="qty_typed(event);" id="cur_qty" />
            <input type="hidden" id="cur_upc" value="%s" />',
            (($elem=='#cur_qty')?'class="focused form-control input-lg"':'class="form-control input-lg"'),
            $this->current_item_data['upc']
        );
    }

    function body_content()
    {
        ob_start();
        $elem = '#upc_in';
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        if (isset($this->current_item_data['upc']) && isset($this->current_item_data['desc'])) $elem = '#cur_qty';
        $this->add_onload_command('$(\'' . $elem . '\').focus();');
        $this->upcForm($elem, $store);
if (isset($this->current_item_data['upc'])){
    if (!isset($this->current_item_data['desc'])){
        echo '<div class="alert alert-danger">Item not found (';
        echo $this->current_item_data['upc'];
        echo ')</div>';
        $this->add_onload_command('doubleBeep();');
    } else {
        $this->qtyForm($elem);
        printf('<button type="button" onclick="update_qty(%d);" class="btn btn-success btn-lg">+%d</button>
            <button type="button" onclick="update_qty(%d);" class="btn btn-danger btn-lg">-%d</button>',
            1,1,-1,1);
        $used = array(1=>true);
        foreach($this->current_item_data['case_sizes'] as $s){
            if (isset($used[$s])) continue;
            printf('<button type="button" onclick="update_qty(%d)" class="btn btn-success btn-lg">+%d</button>
                <button type="button" onclick="update_qty(%d)" class="btn btn-danger btn-lg">-%d</button>',
                $s,$s,-1*$s,$s);
        }
        echo '</div>';
    }
}
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

