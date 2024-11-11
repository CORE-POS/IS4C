<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

use COREPOS\Fannie\API\lib\Store;

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LessSignFromSearch extends \COREPOS\Fannie\API\FannieReadOnlyPage
{

    protected $title = 'Fannie - Signage';
    protected $header = 'Print To 1/4 Size Paper Signage';

    public $description = '[Signage] is a tool to create sale signs or shelf tags
    for a set of advanced search items. Must be accessed via Advanced Search.';
    public $themed = true;

    protected $signage_mod;
    protected $selected_mod;
    protected $signage_obj;

    public function preprocess()
    {
       $this->__routes[] = 'post<u>';
       $this->__routes[] = 'post<batch>';
       $this->__routes[] = 'get<batch>';
       $this->__routes[] = 'get<queueID>';
       return parent::preprocess();
    }

    protected function get_queueID_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $tags = new ShelftagsModel($dbc);
        $tags->id($this->queueID);
        $this->u = array();
        foreach ($tags->find() as $tag) {
            $this->u[] = $tag->upc();
        }

        return $this->post_u_handler();
    }

    protected function post_u_handler()
    {
        if (!is_array($this->u)) {
            $this->u = array($this->u);
        }
        $this->upcs = array();
        foreach($this->u as $postdata) {
            if (is_numeric($postdata)) {
                $this->upcs[] = BarcodeLib::padUPC($postdata);
            }
        }

        $dbc = $this->connection;
        $store = Store::getIdByIp();
        list($inStr, $args) = $dbc->safeInClause($this->upcs);
        $args[] = $store;
        $query = "SELECT upc, fs.name FROM FloorSectionProductMap AS f
            LEFT JOIN FloorSections AS fs ON f.floorSectionID=fs.floorSectionID
            WHERE f.upc IN ({$inStr}) AND fs.storeID = ? ORDER BY fs.name;";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep,$args);
        $locations = array();
        while ($row = $dbc->fetchRow($res)) {
            $locations[$row['upc']] = $row['name'];
        }
        usort($this->upcs, function ($a, $b) use ($locations) {
            if (!isset($locations[$a]) || !isset($locations[$b])) return 0;
            if ($locations[$a] == $locations[$b]) return 0;
            return $locations[$a] < $locations[$b] ? -1 : 1;
        });

        if (!$this->initModule()) {
            echo 'Error: no layouts available';
            return false;
        }

        $class_name = $this->signage_mod;
        $item_mode = FormLib::get('item_mode', 0);

        if (empty($this->upcs)) {
            echo 'Error: no valid data';
            return false;
        }

        $this->signage_obj = new $class_name($this->upcs, '', $item_mode);

        /**
          On item text update, kick out a mini form
          to re-POST the correct items to this page

          Need to prevent page refresh from re-updating
          items. That causes issues if jumping back
          and forth between this editor and the
          normal item editor.
        */
        if (FormLib::get('update') == 'Save Text') {
            $this->signage_obj->saveItems();
            echo '<html><head></head>
                  <body onload="document.forms[0].submit();">
                  <form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">';
            foreach ($this->upcs as $u) {
                printf('<input type="hidden" name="u[]" value="%s" />', $u);
            }
            echo '</form></body></html>';
            return false;
        } elseif (is_array(FormLib::get('update_upc'))) {
            $upc = FormLib::get('update_upc');
            $brand = FormLib::get('update_brand', array());
            $desc = FormLib::get('update_desc', array());
            $origin = FormLib::get('update_origin', array());
            $custom = FormLib::get('custom_origin', array());
            $repeats = FormLib::get('update_repeat', array());
            $knownOrigins = $this->signage_obj->getOrigins();
            for ($i=0; $i<count($upc); $i++) {
                if (isset($brand[$i])) {
                    $this->signage_obj->addOverride($upc[$i], 'brand', $brand[$i]);
                }
                if (isset($desc[$i])) {
                    $this->signage_obj->addOverride($upc[$i], 'description', $desc[$i]);
                }
                if (isset($custom[$i]) && !empty($custom[$i])) {
                    $this->signage_obj->addOverride($upc[$i], 'originName', $custom[$i]);
                } elseif (isset($origin[$i]) && isset($knownOrigins[$origin[$i]])) {
                    $this->signage_obj->addOverride($upc[$i], 'originName', $knownOrigins[$origin[$i]]);
                }
                if (isset($repeats[$i]) && $repeats[$i] != 1) {
                    $this->signage_obj->addRepeat($upc[$i], $repeats[$i]);
                }
            }
            $this->signage_obj->setRepeats(FormLib::get('repeats', 1));
        }

        return $this->drawPdf();
    }

    private function drawPdf()
    {
        if (FormLib::get('pdf') == 'Print') {
            foreach (FormLib::get('exclude', array()) as $e) {
                $this->signage_obj->addExclude($e);
            }
            $this->signage_obj->setInUseFilter(FormLib::get('store', 0));
            $this->signage_obj->drawPDF();
            return false;
        } else {
            return true;
        }
    }

    protected function get_batch_handler()
    {
        return $this->post_batch_handler();
    }

    protected function post_batch_handler()
    {
        if (!is_array($this->batch)) {
            $this->batch = array($this->batch);
        }

        if (!$this->initModule()) {
            echo 'Error: no layouts available';
            return false;
        }

        $class_name = $this->signage_mod;

        if (empty($this->batch)) {
            echo 'Error: no valid data';
            return false;
        }

        $this->signage_obj = new $class_name(array(), 'batch', $this->batch);
        if (FormLib::get('update') == 'Save Text') {
            $this->signage_obj->saveItems();
            echo '<html><head></head>
                  <body onload="document.forms[0].submit();">
                  <form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">';
            foreach ($this->batch as $b) {
                printf('<input type="hidden" name="batch[]" value="%d" />', $b);
            }
            echo '</form></body></html>';
            return false;
        } elseif (is_array(FormLib::get('update_upc'))) {
            $upc = FormLib::get('update_upc');
            $brand = FormLib::get('update_brand', array());
            $desc = FormLib::get('update_desc', array());
            $origin = FormLib::get('update_origin', array());
            $custom = FormLib::get('custom_origin', array());
            $repeats = FormLib::get('update_repeat', array());
            $knownOrigins = $this->signage_obj->getOrigins();
            for ($i=0; $i<count($upc); $i++) {
                if (isset($brand[$i])) {
                    $this->signage_obj->addOverride($upc[$i], 'brand', $brand[$i]);
                }
                if (isset($desc[$i])) {
                    $this->signage_obj->addOverride($upc[$i], 'description', $desc[$i]);
                }
                if (isset($custom[$i]) && !empty($custom[$i])) {
                    $this->signage_obj->addOverride($upc[$i], 'originName', $custom[$i]);
                } elseif (isset($origin[$i]) && isset($knownOrigins[$origin[$i]])) {
                    $this->signage_obj->addOverride($upc[$i], 'originName', $knownOrigins[$origin[$i]]);
                }
                if (isset($repeats[$i]) && $repeats[$i] != 1) {
                    $this->signage_obj->addRepeat($upc[$i], $repeats[$i]);
                }
            }
            $this->signage_obj->setRepeats(FormLib::get('repeats', 1));
        }

        return $this->drawPdf();
    }

    /**
      Detect selected or default layout module
      @return [boolean] success/failure
    */
    protected function initModule()
    {
        $mod = FormLib::get('signmod', false);
        if ($mod !== false) {
            $this->selected_mod = $mod;
            if (substr($mod, 0, 7) == 'Legacy:') {
                $this->signage_mod = 'COREPOS\\Fannie\\API\\item\\signage\\LegacyWrapper';
                COREPOS\Fannie\API\item\signage\LegacyWrapper::setWrapped(substr($mod, 7));
            } else {
                $this->signage_mod = $mod;
            }
            return true;
        } else {
            $mods = FannieAPI::listModules('\COREPOS\Fannie\API\item\FannieSignage');
            $default = $this->config->get('DEFAULT_SIGNAGE');
            if (in_array($default, $mods)) {
                $this->signage_mod = $default;
                $this->selected_mod = $default;
                return true;
            } elseif (isset($mods[0])) {
                $this->signage_mod = $mods[0];
                $this->selected_mod = $mods[0];
                return true;
            } else {
                return false;
            }
        }
    }

    protected function get_batch_view()
    {
        return $this->post_batch_view();
    }

    protected function post_batch_view()
    {
        return $this->post_u_view();
    }

    protected function get_queueID_view()
    {
        return $this->post_u_view();
    }

    private function userCanSave()
    {
        $authorized = false;
        if (FannieAuth::validateUserQuiet('admin')) {
            $authorized = true;
        } elseif (FannieAuth::validateUserQuiet('signText')) {
            $authorized = true;
        }

        return $authorized;
    }

    protected function post_u_view()
    {
        $ret = '';
        $ret .= '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post" id="signform">';
        $mods = FannieAPI::listModules('\COREPOS\Fannie\API\item\FannieSignage');
        //$enabled = $this->config->get('ENABLED_SIGNAGE');
        $enabled = array(
            'COREPOS-Fannie-Plugin-CoopDealsSigns-SingleWfcSmartSigns12UpL',
            //'COREPOS-Fannie-API-item-signage-SingleCompact12UpL',
            'COREPOS-Fannie-Plugin-CoopDealsSigns-SingleWfcSmartSigns16UpL',
            //'COREPOS-Fannie-API-item-signage-SingleCompact16UpL',
            'COREPOS-Fannie-API-item-signage-SingleCompact4UpL',
        );
        if (count($enabled) > 0) {
            $mods = array_filter($mods, function ($i) use ($enabled) {
                return in_array($i, $enabled) || in_array(str_replace('\\', '-', $i), $enabled);
            });
        }
        sort($mods);
        //$tagEnabled = $this->config->get('ENABLED_TAGS');
        $tagEnabled = array(
            'WFC SingleHybrid',
            'WFC Hybrid Guidelines',
            'Single New WFC Deli Regular',
            'Single New WFC Deli Narrow',
            'Single New WFC Deli Short',
            'Single New WFC Deli SquareTags',
            'Single WFC Produce SmartSigns',
            'Single WFC Produce',
        );
        foreach (COREPOS\Fannie\API\item\signage\LegacyWrapper::getLayouts() as $l) {
            if (in_array($l, $tagEnabled) && count($tagEnabled) > 0) {
                $mods[] = 'Legacy:' . $l;
            }
        }

        if (FannieConfig::config('COOP_ID') == 'WFC_Duluth') {
            $ret .= SignsLib::visualSignSelectHTML();
            //$visualSelectJS = SignsLib::visualSignSelectJS();
            //$this->addOnloadCommand($visualSelectJS);
        }

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<label>Layout</label>:
            <select name="signmod" id="signmod" class="form-control" onchange="$(\'#signform\').submit()">';
        foreach ($mods as $m) {
            $name = $m;
            if (strstr($m, '\\')) {
                $pts = explode('\\', $m);
                $name = $pts[count($pts)-1];
            }
            if ($name === 'LegacyWrapper') continue;
            $ret .= sprintf('<option %s value="%s">%s</option>',
                    ($m == $this->selected_mod ? 'selected' : ''), $m, $name);
        }
        $ret .= '</select>';

        if (isset($this->upcs)) {
            foreach ($this->upcs as $u) {
                $ret .= sprintf('<input type="hidden" name="u[]" value="%s" />', $u);
            }
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            $item_mode = FormLib::get('item_mode', 0);
            $modes = array('Current Retail', 'Upcoming Retail', 'Current Sale', 'Upcoming Sale');
            $ret .= '<select name="item_mode" class="form-control"
                onchange="$(\'#signform\').submit()">';
            foreach ($modes as $id => $label) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            ($id == $item_mode ? 'selected' : ''),
                            $id, $label);
            }
            $ret .= '</select>';
        } else if (isset($this->batch)) {
            foreach ($this->batch as $b) {
                $ret .= sprintf('<input type="hidden" name="batch[]" value="%d" />', $b);
            }
        }
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';

        $stores = new StoresModel($this->connection);
        $stores->hasOwnItems(1);
        $ret .= '<select class="form-control" name="store">
                <option value="0">Any Store</option>';
        foreach ($stores->find() as $s) {
            $store_selected = (FormLib::get('store') == $s->storeID()) ? ' SELECTED ' : '';
            $ret .= sprintf('<option value="%d" %s>%s</option>',
                $s->storeID(), $store_selected, $s->description());
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="number" title="Number of copies" style="width: 6em;" name="repeats" class="form-control" value="1" />';

        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" name="pdf" value="Print"
                    class="btn btn-default">Print</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;<label title="If supported"><input type="checkbox" name="offset" value="1" /> Offset</label>';
        if (FormLib::get('batch', false) != false) {
            $ret .= '&nbsp;&nbsp;&nbsp;<label title="If supported"><input type="checkbox" name="altViewCheckbox" id="altViewCheckbox" value="1" /> Show Extended Info </label>';
        }

        $darkExtendOnly = '&nbsp;&nbsp;&nbsp;<label title="If supported"><input type="checkbox" name="showPrice" value="1" checked />Show Price</label>';
        $ShowBarcodeToggleHTML = '&nbsp;&nbsp;&nbsp;<label title="If supported"><input type="checkbox" name="showBarcode" value="1" checked />Show Barcode</label>';        
        $signmod = FormLib::get('signmod');
        //if (FormLib::get('signmod') == 'Legacy:WFC Dark Extended 24UP') 
        //    $ret .= $darkExtendOnly;
        //if (FormLib::get('signmod') == 'Legacy:WFC MEAT 14UP') 
        //    $ret .= $darkExtendOnly;
        //if (FormLib::get('signmod') == 'Legacy:WFC New MEAT 14UP') 
        //    $ret .= $darkExtendOnly;
        //if (FormLib::get('signmod') == 'Legacy:WFC MEAT SN 14UP') 
        //    $ret .= $darkExtendOnly;
        //if (FormLib::get('signmod') == 'Legacy:WFC Dark ServiceCase 12UP') 
        //    $ret .= $darkExtendOnly;
        //if (FormLib::get('signmod') == 'Legacy:WFC Deli Narrow 24UP') 
        //    $ret .= $darkExtendOnly;
        //if (FormLib::get('signmod') == 'Legacy:WFC Deli Short 24UP') 
        //    $ret .= $darkExtendOnly;
        if (FormLib::get('signmod') == 'Legacy:Single New WFC Deli Regular') {
            $ret .= $darkExtendOnly;
            $ret .= $ShowBarcodeToggleHTML;
        } else if (FormLib::get('signmod') == 'Legacy:Single New WFC Deli Narrow') {
            $ret .= $darkExtendOnly;
            $ret .= $ShowBarcodeToggleHTML;
        } else if (FormLib::get('signmod') == 'Legacy:Single New WFC Deli Short') {
            $ret .= $darkExtendOnly;
            $ret .= $ShowBarcodeToggleHTML;
        } else if (FormLib::get('signmod') == 'Legacy:Single New WFC Deli SquareTags') {
            $ret .= $darkExtendOnly;
            $ret .= $ShowBarcodeToggleHTML;
        } else if (FormLib::get('signmod') == 'Legacy:Single New WFC Deli Regular') {
            $ret .= $darkExtendOnly;
            $ret .= $ShowBarcodeToggleHTML;
        }
        $ret .= '</div>';
        $ret .= '<hr />';

        $ret .= $this->signage_obj->listItems();

        if ($this->userCanSave()) {
            $ret .= '<div id="signHiddenInput"></div>
            <p><a onClick="updateSigninfo();"
                class="btn btn-default">[Admin] Save Sign Info</a></p>';
        }

        $this->add_onload_command('$(".FannieSignageField").keydown(function(event) {
            if (event.which == 13) {
                event.preventDefault();
            }
        });');

        $ret .= '</form>';
        $this->addScript('../../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addOnloadCommand("\$('.tablesorter').tablesorter();");

        return $ret;
    }

    protected function get_view()
    {
        $dbc = $this->connection;

        $batchQ = 'SELECT batchID,
                    batchName,
                    startDate,
                    endDate
                   FROM batches AS b
                   WHERE
                    (b.startDate <= ? AND b.endDate >= ?)
                    OR b.startDate >=?
                   ORDER BY b.startDate DESC';
        $batchP = $dbc->prepare($batchQ);
        $today = date('Y-m-d');
        $batchR = $dbc->execute($batchP, array($today, $today, $today));

        $ret = '<b>Select batch(es)</b>:';
        $ret .= '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">';
        $ret .= '<select name="batch[]" multiple size="15">';
        while ($batchW = $dbc->fetch_row($batchR)) {
            $ret .= sprintf('<option value="%d">%s (%s - %s)</option>',
                        $batchW['batchID'],
                        $batchW['batchName'],
                        date('Y-m-d', strtotime($batchW['startDate'])),
                        date('Y-m-d', strtotime($batchW['endDate']))
            );
        }
        $ret .= '</select>';
        $ret .= '<br /><br />';
        $ret .= '<input type="submit" value="Make Signs" />';
        $ret .= '</form>';

        return $ret;
    }

    public function css_content()
    {
        $visualSelectCSS = SignsLib::visualSignSelectCSS();

        return <<<HTML
.altView {
    display: none;
}
$visualSelectCSS
HTML;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
    $('textarea').each(function(){
        var text = $(this).text();
        if (text == text.toUpperCase()) {
            $(this).addClass('alert-danger');
        }
    });
    $('textarea').on('change', function(){
        $(this).removeClass('alert-danger');
    });
    $('input').on('change', function(){
        $(this).removeClass('alert-danger');
    });
    $('input').each(function(){
        var name = $(this).attr('name');
        var text = $(this).val();
        var place = $(this).attr('placeholder');
        if (text == text.toUpperCase() && place != 'Custom origin...' && name != "repeats" && name != "update_repeat[]") {
            $(this).addClass('alert-danger');
        }
    });
    function updateSigninfo()
    {
        var c = confirm("Permanently change sign info?");
        if (c == true) {
            $('#signHiddenInput').html('<input type="hidden" name="update" id="updateBtn" value="Save Text">');
            $("#signform").submit();
        }
    }


var lastChecked = null;
var i = 0;
var indexCheckboxes = function(){
    $(':checkbox').each(function(){
        $(this).attr('data-index', i);
        i++;
    });
};
indexCheckboxes();
$('table').click(function(){
    indexCheckboxes();
});
$(':checkbox').on("click", function(e){
    if(lastChecked && e.shiftKey) {
        var i = parseInt(lastChecked.attr('data-index'));
        var j = parseInt($(this).attr('data-index'));
        var checked = $(this).is(":checked");

        var low = i;
        var high = j;
        if (i>j){
            var low = j;
            var high = i;
        }

        for(var c = low; c < high; c++) {
            if (c != low && c!= high) {
                var check = checked ? true : false;
                $('input[data-index="'+c+'"').prop("checked", check);
            }
        }
    }
    lastChecked = $(this);
});

$('#altViewCheckbox').click(function(e){
    let checked = $(this).is(":checked");
    if (checked) {
        $('.altView').each(function(){
            $(this).show();
        });
    } else {
        $('.altView').each(function(){
            $(this).hide();
        });
    }
});

JAVASCRIPT;
    }

    public function helpContent()
    {
        return '<p>
            Create signs and/or tags. First select a layout
            that controls how the tags look. Then select which
            prices to use: current or upcoming, retail or sale/promo.
            Text for each item can be overriden in the
            list of items below.
            </p>
            <p>This page prints to sheets of paper that are 1/4 the size 
            of a regular print job (eg, 4UP size). To print signs using this
            page, feed 1/4 size pieces of paper into the bypass tray and make
            sure that <i>Choose paper source by PDF page size</i> is checked
            in the Print window. 
            ';

    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->u = array(BarcodeLib::padUPC('4011'));
        $phpunit->assertEquals(true, $this->post_u_handler());
        $phpunit->assertNotEquals(0, strlen($this->post_u_view()));
        $this->batch = 1;
        $phpunit->assertEquals(true, $this->get_batch_handler());
        $phpunit->assertNotEquals(0, strlen($this->get_batch_view()));
    }

}

FannieDispatch::conditionalExec();

