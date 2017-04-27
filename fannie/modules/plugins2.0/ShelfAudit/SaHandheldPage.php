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
class SaHandheldPage extends FannieRESTfulPage 
{
    private $section=0;
    protected $current_item_data=array();
    private $linea_ios_mode = false;

    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Handheld] is an interface for scanning and entering quantities on
    hand using a handheld device.';
    protected $title = 'ShelfAudit Inventory';
    protected $header = '';

    private function linea_support_available()
    {
        if (file_exists($this->config->get('ROOT') . 'src/javascript/linea/cordova-2.2.0.js')
        && file_exists($this->config->get('ROOT') . 'src/javascript/linea/ScannerLib-Linea-2.0.0.js')) {
            return true;
        }

        return false;
    }

    protected function get_id_handler()
    {
        $ret = array();
        $settings = $this->config->get('PLUGIN_SETTINGS');
        if ($this->id !== '') {
            $dbc = FannieDB::getReadOnly($this->config->get('OP_DB'));
            $upc = BarcodeLib::padUPC($this->id);
            $ret['upc'] = $upc;     
            $store = FormLib::get('store', 0);
            $q = 'SELECT p.description,v.brand,s.quantity,v.units FROM
                products AS p LEFT JOIN vendorItems AS v ON p.upc=v.upc
                LEFT JOIN '.$settings['ShelfAuditDB'].$dbc->sep().
                'sa_inventory AS s ON p.upc=s.upc AND s.clear=0 AND s.storeID=?
                    AND s.section=?
                WHERE p.upc=? 
                ORDER BY v.vendorID';
            $p = $dbc->prepare($q);
            $r = $dbc->execute($p,array($store, $this->section, $upc));
            if ($dbc->numRows($r) == 0 && substr($upc, 0, 5) == '00454') {
                // look up special order
                $orderID = (int)substr($upc, 5, 6);
                $transID = (int)substr($upc, -2); 
                $q = "SELECT o.description, '' AS brand, s.quantity, o.quantity AS units
                    FROM " . $this->config->get('TRANS_DB') . $dbc->sep() . "PendingSpecialOrder AS o
                    LEFT JOIN ".$settings['ShelfAuditDB'].$dbc->sep().
                    "sa_inventory AS s ON s.upc=? AND s.clear=0 AND s.storeID=?
                        AND s.section=?
                    WHERE o.order_id=? AND o.trans_id=?";
                $args = array($upc, $store, $this->section, $orderID, $transID);
                $p = $dbc->prepare($q);
                $r = $dbc->execute($p, $args);
            } elseif ($dbc->numRows($r)==0) {
                // try again; item on-hand but not in products
                $q = 'SELECT v.description,v.brand,s.quantity,v.units FROM
                    vendorItems AS v 
                    LEFT JOIN '.$settings['ShelfAuditDB'].$dbc->sep().
                    'sa_inventory AS s ON s.upc=v.upc AND s.clear=0 AND s.storeID=?
                        AND s.section=?
                    WHERE v.upc=? 
                    ORDER BY v.vendorID';
                $p = $dbc->prepare($q);
                $r = $dbc->execute($p,array($store, $this->section, $upc));
            }

            while ($w = $dbc->fetchRow($r)) {
                if (!isset($this->current_item_data['desc'])){
                    $ret['desc'] = $w['brand'].' '.$w['description'];
                }
                if (!isset($this->current_item_data['qty'])){
                    $ret['qty'] = is_numeric($w['quantity']) ? $w['quantity'] : 0;
                }
                if (!isset($this->current_item_data['case_sizes'])){
                    $ret['case_sizes'] = array();
                }
                if ($w['units'] > 0) {
                    $ret['case_sizes'][] = $w['units'];
                }
            }
        }

        ob_start();
        if (isset($ret['upc']) && !isset($ret['desc'])) {
            echo '<div class="alert alert-danger">Item not found (';
            echo $ret['upc'];
            echo ')</div>';
        } elseif (isset($ret['upc'])) {
            echo $this->qtyForm($ret);
            $this->hasQty = true;
        }
        $this->qtyArea = ob_get_clean();

        return true;
    }

    protected function get_id_view()
    {
        return $this->get_view() . '<div id="qtyArea">' . $this->qtyArea . '</div>';
    }

    protected function post_id_handler()
    {
        $ret = array();
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $upc = BarcodeLib::padUPC($this->id);
        $qty = FormLib::get('qty',0);
        $store = FormLib::get('store', 0);

        $dbc = FannieDB::get($settings['ShelfAuditDB']);
        $delP = $dbc->prepare('DELETE FROM sa_inventory
                WHERE upc=? AND clear=0 AND section=? AND storeID=?');
        $insP = $dbc->prepare('INSERT INTO sa_inventory (datetime,upc,clear,quantity,section,storeID)
                VALUES ('.$dbc->now().',?,0,?,?,?)');
        $dbc->execute($delP, array($upc, $this->section, $store));
        if ($qty > 0){
            $dbc->execute($insP, array($upc, $qty, $this->section, $store));
        }
        $ret['qty'] = $qty;
        
        echo json_encode($ret);

        return false;
    }

    protected function setSection()
    {
        if (!isset($this->session->SaPluginSection)) {
            $this->session->SaPluginSection = 0;
        }
        $section = FormLib::get('section', false);
        if ($section !== false) {
            $this->session->SaPluginSection = $section;
        } else {
            $section = $this->session->SaPluginSection;
        }

        return $section;
    }

    function preprocess()
    {
        /**
          Store session in browser section.
        */
        if (ini_get('session.auto_start')==0 && !headers_sent() && php_sapi_name() != 'cli' && session_id() == '') {
            @session_start();
        }
        $this->section = $this->setSection();

        $this->linea_ios_mode = $this->linea_support_available();
        if ($this->linea_ios_mode){
            $this->add_script($this->config->get('URL').'src/javascript/linea/cordova-2.2.0.js');
            $this->add_script($this->config->get('URL').'src/javascript/linea/ScannerLib-Linea-2.0.0.js');
        }
        
        return parent::preprocess();
    }

    function css_content()
    {
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

    function javascript_content()
    {
        ob_start();
        ?>
function doubleBeep() {
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

if (typeof WebBarcode == 'object') {
    WebBarcode.onBarcodeScan(function(ev) {
        var data = ev.value;
        var upc = data.substring(0,data.length-1);
        $('#upc_in').val(upc);
        $('#goBtn').click();
    });
}
        <?php } ?>

        <?php
        return ob_get_clean();
    }

    protected function upcForm($store)
    {
        ?>
<form method="get" id="upcScanForm">
<a href="SaMenuPage.php">Menu</a>
 - Store # <?php echo $store; ?>
<input type="hidden" name="store" id="store" value="<?php echo ((int)$store); ?>" />
<label>
    <input tabindex="-1" type="radio" name="section" value=0 <?php echo $this->session->SaPluginSection==0 ? 'checked' : ''; ?>/> Backstock
</label>
<label>
    <input tabindex="-1" type="radio" name="section" value=1 <?php echo $this->session->SaPluginSection==1 ? 'checked' : ''; ?>/> Floor
</label>
<br />
<div class="form-group form-inline">
    <div class="input-group">
        <label class="input-group-addon">UPC</label>
        <input type="number" size="10" name="id" id="upc_in" 
            onfocus="handheld.paintFocus('upc_in');"
            class="focused form-control" tabindex="1"
        />
    </div>
    <button type="submit" class="btn btn-success" tabindex="-1" id="goBtn">Go</button>
</div>
</form>
        <?php
    }

    protected function qtyForm($data)
    {
        $used = array(1=>true);
        $cases = '';
        foreach($data['case_sizes'] as $s){
            if (isset($used[$s])) continue;
            $cases.= sprintf('<button type="button" tabindex="-1" onclick="handheld.updateQty(%d)" class="btn btn-success btn-lg">+%d</button>
                <button type="button" tabindex="-1" onclick="handheld.updateQty(%d)" class="btn btn-danger btn-lg">-%d</button>',
                $s,$s,-1*$s,$s);
        }
        echo <<<HTML
<p>
    {$data['upc']} {$data['desc']}<br />
    <strong>Current Qty</strong>: 
    <span id="old-qty" class="collapse">{$data['qty']}</span>
    <span id="live-qty">{$data['qty']}</span>
</p>
<div class="form-group form-inline">
    <input type="number" min="-99999" max="99999" step="0.01" 
        class="focused form-control input-lg" tabindex="2"
        onfocus="handheld.paintFocus('cur_qty');$(this).select();" 
        onkeyup="handheld.qtyTyped(event);" id="cur_qty" 
        onkeydown="handheld.catchTab(event);" />
    <input type="hidden" id="cur_upc" value="{$data['upc']}" />
    <button tabindex="-1" type="button" onclick="handheld.updateQty(1);" class="btn btn-success btn-lg">+1</button>
    <button tabindex="-1" type="button" onclick="handheld.updateQty(-1);" class="btn btn-danger btn-lg">-1</button>
    {$cases}
</div>
HTML;
    }

    function get_view()
    {
        ob_start();
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        if (isset($this->hasQty)) {
            $this->addOnloadCommand("\$('#cur_qty').focus();\n");
        } else {
            $this->addOnloadCommand("\$('#upc_in').focus();\n");
        }
        $this->upcForm($store);
        $this->addScript('js/handheld.js');

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

