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

use COREPOS\Fannie\API\lib\Store;

if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('updateAllLanes')) {
    include('laneUpdates_WEFC_Toronto.php');
}

class ItemEditorPage extends FanniePage 
{
    private $mode = 'search';
    private $msgs = '';

    public $description = '[Item Editor] is the primary item editing tool.';
    protected $enable_linea = true;

    public function readinessCheck()
    {
        if ($this->config->get('STORE_MODE') != 'HQ') {
            return true;
        } else {
            if ($this->config->get('STORE_ID') === '') {
                $this->error_msg = 'In HQ Mode store must have an ID!';
                return false;
            } elseif (!is_numeric($this->config->get('STORE_ID'))) {
                $this->error_msg = 'Invalid store ID: ' . $this->config->get('STORE_ID');
                return false;
            } else {
                $this->connection->selectDB($this->config->get('OP_DB'));
                $prep = $this->connection->prepare('
                    SELECT storeID
                    FROM Stores
                    WHERE storeID=?');
                $res = $this->connection->execute($prep, array($this->config->get('STORE_ID')));
                if ($res === false || $this->connection->numRows($res) == 0) {
                    $this->error_msg = 'No record exists for this store';
                    return false;
                }
            }

            return true;
        }
    }

    public function errorContent()
    {
        return '<div class="alert alert-danger">'
            . $this->error_msg . '</div>'
            . '<p><a href="../install/InstallStoresPage.php">Adjust Store Settings</a></p>';
    }

    private $config_converted = false;

    private function getConfiguredModules()
    {
        $FANNIE_PRODUCT_MODULES = $this->config->get('PRODUCT_MODULES');
        if (!is_array($FANNIE_PRODUCT_MODULES)) {
            $FANNIE_PRODUCT_MODULES = array('BaseItemModule' => array('seq'=>0, 'show'=>1, 'expand'=>1));
        }

        if ($this->config_converted === false) {
            /*
              Convert old settings to new format.
            */
            $legacy_indexes = array();
            $replacement_values = array();
            foreach ($FANNIE_PRODUCT_MODULES as $id => $m) {
                if (preg_match('/^\d+$/', $id)) {
                    // old setting. convert to new.
                    $legacy_indexes[] = $id;
                    $replacement_values[$m] = array(
                        'seq' => $id,
                        'show' => 1,
                        'expand' => 1,
                    );
                }
            }
            foreach ($legacy_indexes as $index) {
                unset($FANNIE_PRODUCT_MODULES[$index]);
            }
            foreach ($replacement_values as $name => $params) {
                $FANNIE_PRODUCT_MODULES[$name] = $params;
            }

            // verify modules exist
            foreach (array_keys($FANNIE_PRODUCT_MODULES) as $name) {
                if (class_exists($name)) {
                    continue;
                }
                $file = dirname(__FILE__) . '/modules/' . $name . '.php';
                if (!file_exists($file)) {
                    unset($FANNIE_PRODUCT_MODULES[$name]);
                } else {
                    include_once($file);
                    if (!class_exists($name)) {
                        unset($FANNIE_PRODUCT_MODULES[$name]);
                    }
                }
            }

            $this->config_converted = true;
        }

        return $FANNIE_PRODUCT_MODULES;
    }

    function preprocess()
    {
        $mods = $this->getConfiguredModules();

        $this->title = _('Fannie') . ' - ' . _('Item Maintenance');
        $this->header = '';//_('Item Maintenance');

        if (FormLib::get_form_value('searchupc') !== '') {
            $this->mode = 'searchResults';
        }
        if (FormLib::get('superFilter', false) !== false) {
            $this->session->__superFilter = FormLib::get('superFilter');
        }

        if (FormLib::get_form_value('createBtn') !== ''){
            $this->msgs = $this->saveItem(true);
        } else if (FormLib::get_form_value('updateBtn') !== '') {
            $this->msgs = $this->saveItem(false);
        }

        return true;
    }

    function body_content()
    {
        switch($this->mode){
            case 'searchResults':
                return $this->searchResults();
            case 'search':
            default:
                return $this->searchForm();
        }
    }

    private function searchForm()
    {
        $FANNIE_URL = $this->config->get('URL');
        $ret = '';
        $vars = array(
            'enter' => _('Enter'),
            'orName' => _('or product name here'),
            'advancedSearch' => _('Advanced Search'),
            'openPLU' => _('Find Open PLU Range'),
            'self' => filter_input(INPUT_SERVER, 'PHP_SELF'),
            'msgs' => '',
        );
        if (!empty($this->msgs)) {
            $vars['msgs'] = '<blockquote style="border:solid 1px black;">'
                    . $this->msgs
                    . '</blockquote>';
        }
        $model = new SuperDeptNamesModel($this->connection);
        $sOpts = $model->toOptions($this->session->__superFilter);
        if ($this->session->__superFilter !== '') {
            $this->addOnloadCommand("EXTRA_AUTO_COMPLETE_PARAMS = { superID: " . $this->session->__superFilter . " };");
        }
        $ret = <<<HTML
{$vars['msgs']}
<form action="{$vars['self']}" name="searchform" method=get>
    <div class="container-fluid">
        <div class="row form-group form-inline">
            <input name=searchupc type=text id=upc class="form-control" /> 
            {$vars['enter']}
            <select name="ntype" id="searchselect" class="form-control">
                <option>UPC</option>
                <option>EAN-8</option>
                <option>SKU</option>
                <option>Brand Prefix</option>
                <option>Batch ID</option>
                <option>Vendor ID</option>
                <option>Product Physical Location</option>
                <option>Owner Number</option>
            </select> 
            {$vars['orName']}
        </div>
    </div>
    <p>
        <a name=searchBtn id="searchbtn" type=submit class="btn btn-default">Go</a>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <label>
            <input type="checkbox" name="inUse" value="1" />
            Include items that are not inUse
        </label>
    </p>
    <p class="form-inline">
        <label>Filter</label>:
        <select class="form-control input-sm" name="superFilter"
            onchange="if (this.value == '') { EXTRA_AUTO_COMPLETE_PARAMS = {}; } else { EXTRA_AUTO_COMPLETE_PARAMS = { superID: this.value }; }">
            <option value="">No Filter</option>
            {$sOpts}
        </select>
    </p>
</form>
<p><a href="AdvancedItemSearch.php">{$vars['advancedSearch']}</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="PluRangePage.php">{$vars['openPLU']}</a>
</p>
HTML;
        
        $this->addScript('autocomplete.js');
        $wsUrl = $FANNIE_URL . 'ws/';
        $this->addOnloadCommand("bindAutoComplete('#upc', '$wsUrl', 'item');\n");
        $this->addOnloadCommand('$(\'#upc\').focus();');

        $this->addScript($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.js?v=1');
        $this->addCssFile($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.css');
        $this->addOnloadCommand('$(\'.fancyboxLink\').fancybox({\'width\':\'85%;\'});');

        // bind scanner to UPC field
        $this->addOnloadCommand("enableLinea('#upc');\n");

        return $ret;
    }

    private function searchQuery($upc, $numType, $inUseFlag, $store_id)
    {
        $query = '';
        $args = array();
        if (is_numeric($upc)) {
            switch($numType) {
                case 'SKU':
                    $query = "SELECT p.*,n.vendorName AS distributor,p.brand AS manufacturer 
                        FROM products as p inner join 
                        vendorItems as v ON p.upc=v.upc 
                        left join vendors AS n ON p.default_vendor_id=n.vendorID
                        WHERE v.sku LIKE ? ";
                    $args[] = '%'.$upc;
                    break;
                case 'Brand Prefix':
                    $query = "SELECT p.*,n.vendorName AS distributor,p.brand AS manufacturer 
                        FROM products as p 
                        left join vendors AS n ON p.default_vendor_id=n.vendorID
                        WHERE p.upc like ? ";
                    $args[] = '%'.$upc.'%';
                    break;
                case 'UPC':
                default:
                    $upc = BarcodeLib::padUPC($upc);
                    $query = "SELECT p.*,n.vendorName AS distributor,p.brand AS manufacturer 
                        FROM products as p
                        left join vendors AS n ON p.default_vendor_id=n.vendorID
                        WHERE p.upc = ? ";
                    $args[] = $upc;
                    $inUseFlag = 1; // exact matches should be allowed
                    break;
            }
        } else {
            $superFilter = isset($this->session->__superFilter) && $this->session->__superFilter !== '';
            $superJoin = $superFilter ? ' left join superdepts AS s ON p.department=s.dept_ID ' : '';
            $query = "SELECT p.*,n.vendorName AS distributor,p.brand AS manufacturer 
                FROM products AS p
                    left join vendors AS n ON p.default_vendor_id=n.vendorID
                    {$superJoin}
                WHERE (description LIKE ? 
                    OR n.vendorName LIKE ?
                    OR p.brand LIKE ?)";
            $args[] = '%'.$upc.'%';
            $args[] = '%'.$upc.'%';
            $args[] = '%'.$upc.'%';
            if ($superFilter) {
                $query .= " AND s.superID=? ";
                $args[] = $this->session->__superFilter;
            }
        }
        if (!$inUseFlag) {
            $query .= ' AND inUse=1 ';
        }
        if ($this->config->get('STORE_MODE') == 'HQ') {
            if ($store_id === false)
                $store_id = 1;
            $query .= " AND p.store_id=? ";
            $args[] = $store_id;
        }

        return array($query, $args);
    }

    private function newResultToUpc($dbc, $upc, $numType)
    {
        if (strlen($upc) > 13) {
            $upc = ltrim($upc, '0');
        }
        $actualUPC = BarcodeLib::padUPC($upc);
        $this->mode = 'new'; // mode drives appropriate help text
        if ($numType == 'SKU') {
            $prep = $dbc->prepare('
                SELECT upc
                FROM vendorItems
                WHERE sku LIKE ?
            ');
            $skuR = $dbc->execute($prep, array('%'.$upc));
            if ($skuR && $dbc->numRows($skuR)) {
                $skuW = $dbc->fetchRow($skuR);
                $actualUPC = BarcodeLib::padUPC($skuW['upc']);
            }
        }

        return $actualUPC;
    }

    private function searchResults()
    {
        $authorized = false;
        if (FannieAuth::validateUserQuiet('admin')) {
            $authorized = true;
        }
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $upc = trim(FormLib::get_form_value('searchupc'));
        $numType = FormLib::get_form_value('ntype','UPC');
        $inUseFlag = FormLib::get('inUse', false);
        $store_id = $this->config->get('STORE_ID');
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $store_id = COREPOS\Fannie\API\lib\Store::getIdByIp(); 
        }

        $query = "";
        $args = array();
        list($query, $args) = $this->searchQuery($upc, $numType, $inUseFlag, $store_id);
        $query = $dbc->addSelectLimit($query, 500);
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($query,$args);

        /**
          Query somehow failed. Unlikely. Show error and search box again.
        */
        if ($result === false) {
            $this->msgs = '<div class="alert alert-danger">' . _('Error searching for') 
                . ' ' . $upc . '</div>';
            $this->mode = 'search'; // mode drives appropriate help text
            return $this->searchForm();
        }

        if ($this->config->get('COOP_ID') == 'WFC_Duluth') {
            $lnUPCs = array();
            $bannedUPCs = array();

            $lnP = $dbc->prepare("SELECT code FROM AlternateCodes");
            $bannedP = $dbc->prepare("SELECT code, reason FROM BannedCodes WHERE code = ?");

            $lnR = $dbc->execute($lnP);
            $bannedR = $dbc->execute($bannedP, array($upc));

            while ($lnW = $dbc->fetchRow($lnR)) {
                $lnUPCs[] = BarcodeLib::padUPC($lnW['code']);
            }
            if (in_array($upc, $lnUPCs)) {
                $this->msgs = '<div class="alert alert-danger"> PLU ' . $upc . ' is being used as a linked PLU.
                    This item cannot exist in POS.</div>';
                return $this->searchForm();
            }

            $banned = '';
            while ($bannedW = $dbc->fetchRow($bannedR)) {
                 $banned = $bannedW['reason'];
            }
            if ($banned != '') {
                $this->msgs = '<div class="alert alert-danger"> PLU ' . $upc . ' is banned from POS: 
                    <strong>' . $banned. '</strong></div>';
                return $this->searchForm();
            }

            if (is_numeric($upc) && substr(BarcodeLib::padUPC($upc), 0, 1) != '0' && $authorized == false) {
                $this->msgs = '<div class="alert alert-danger">A check digit is incuded in the UPC that was entered. There must be at least one leading zero to be a valid item.</div>';
                return $this->searchForm();
            }

        }

        $num = $dbc->numRows($result);

        /**
          No match for text input. Can't create a new item w/o numeric UPC,
          so show error and search box again.
        */
        if ($num == 0 && !is_numeric($upc)) {
            $this->msgs = '<div class="alert alert-danger">' . _('No results for') 
                . ' ' . $upc . '</div>';
            $this->mode = 'search'; // mode drives appropriate help text
            return $this->searchForm();
        }

        /**
          List multiple results
        */
        if ($num > 1) {
            $items = array();
            while ($row = $dbc->fetchRow($result)) {
                $items[$row['upc']] = $row;
            }
            $this->mode = 'many'; // mode drives appropriate help text
            return $this->multipleResults($items);
        }

        /**
          Only remaining possibility is a new item or
          editing an existing item
        */
        $actualUPC = '';
        if ($num == 0) {
            $actualUPC = $this->newResultToUpc($dbc, $upc, $numType);
        } else {
            $row = $dbc->fetchRow($result);
            $actualUPC = $row['upc'];
            $this->mode = 'edit'; // mode drives appropriate help text
        }

        return $this->editForm($actualUPC, $this->mode === 'new' ? true : false);
    }

    private function multipleResults($results)
    {
        $FANNIE_URL = $this->config->get('URL');
        $ret = '<table id="itemSearchResults" class="tablesorter">';
        $ret .= '<thead><tr>
            <th>UPC</th><th>Description</th><th>Brand</th><th>Reg. Price</th><th>Sale Price</th><th>Modified</th>
            </tr></thead>';
        $ret .= '<tbody>';
        foreach ($results as $upc => $data) {
            $ret .= sprintf('<tr>
                            <td><a href="%s?searchupc=%s">%s</a></td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%.2f</td>
                            <td>%s</td>
                            <td>%s</td>
                            </tr>',
                            filter_input(INPUT_SERVER, 'PHP_SELF'),
                            $upc, $upc, 
                            $data['description'],
                            $data['manufacturer'],
                            $data['normal_price'],
                            ($data['discounttype'] > 0 ? $data['special_price'] : 'n/a'),
                            $data['modified']
            );
        }
        $ret .= '</tbody></table>';

        $this->addCssFile($FANNIE_URL . 'src/javascript/tablesorter/themes/blue/style.css');
        $this->addScript($FANNIE_URL . 'src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->addOnloadCommand('$(\'#itemSearchResults\').tablesorter();');

        return $ret;
    }

    public static function sortModules($a, $b)
    {
        if ($a['seq'] < $b['seq']) {
            return -1;
        } else if ($a['seq'] > $b['seq']) {
            return 1;
        } else {
            return 0;
        }
    }

    private function userCanEdit($upc, $isNew)
    {
        $authorized = false;
        if (FannieAuth::validateUserQuiet('pricechange') || FannieAuth::validateUserQuiet('audited_pricechange')) {
            $authorized = true;
        } elseif (($range=FannieAuth::validateUserLimited('pricechange')) !== false) {
            /**
              Check if user is authorized to edit a subset of items
            */
            if ($isNew) {
                $authorized = true;
            } else {
                $this->connection->selectDB($this->config->OP_DB);
                $prep = $this->connection->prepare("
                    SELECT upc
                    FROM products AS p
                        INNER JOIN superdepts AS s ON p.department=s.dept_ID
                    WHERE p.upc=?
                        AND s.superID BETWEEN ? AND ?");
                $args = array(BarcodeLib::padUPC($upc), $range[0], $range[1]);
                $result = $this->connection->execute($prep, $args);
                if ($result && $this->connection->numRows($result) > 0) {
                    $authorized = true;
                }
            }
        //} elseif (substr($upc, 0, 3) == '002' && $this->config->get('COOP_ID') == 'WFC_Duluth') {
         //   $authorized = true;
        }

        return $authorized;
    }

    private function editorLinksArea($upc, $isNew, $authorized)
    {
        $url = $this->config->get('URL');
        $self = filter_input(INPUT_SERVER, 'PHP_SELF');
        $ret = '<p>';
        if (!$authorized) {
            $ret .= sprintf('<a class="btn btn-danger"
                        href="%sauth/ui/loginform.php?redirect=%s?searchupc=%s">Login
                        to edit</a>', $url, $self, $upc);
            $this->addOnloadCommand("\$(':input').prop('disabled', true).prop('title','Login to edit');\n");
        } elseif ($isNew) {
            $ret .= '<button type="submit" name="createBtn" value="1"
                        class="btn btn-default">Create Item</button>';
        } else {
            $ret .= '<button type="submit" name="updateBtn" value="1"
                        class="btn btn-default">Update Item</button>';
        }
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a class="btn btn-default btn-sm" href="' . $self . '">Back</a>';
        $this->addScript($url . 'src/javascript/fancybox/jquery.fancybox-1.3.4.js?v=1');
        $this->addCssFile($url . 'src/javascript/fancybox/jquery.fancybox-1.3.4.css');
        if (!$isNew) {
            $ret .= <<<HTML
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="DeleteItemPage.php?id={$upc}" class="btn btn-danger btn-sm">Delete this item</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<label class="badge">History</label> <span class="btn-group">
<a class="btn btn-default btn-sm iframe fancyboxLink" 
    href="{$url}reports/PriceHistory/?upc={$upc}" title="Price History">Price</a>
<a class="btn btn-default btn-sm iframe fancyboxLink" 
    href="{$url}reports/CostHistory/?upc={$upc}" title="Cost History">Cost</a>
<a class="btn btn-default btn-sm iframe fancyboxLink" 
    href="{$url}reports/RecentSales/?upc={$upc}" title="Sales History">Sales</a>
<a class="btn btn-default btn-sm iframe fancyboxLink" 
    href="{$url}reports/ItemOrderHistory/ItemOrderHistoryReport.php?upc={$upc}" 
    title="Order History">Orders</a>
<a class="btn btn-default btn-sm iframe fancyboxLink" 
    href="{$url}reports/ItemBatches/ItemBatchesReport.php?upc={$upc}" 
    title="Batch History">Batches</a>
<a class="btn btn-default btn-sm iframe fancyboxLink"
    href="{$url}batches/batchhistory/BatchHistoryPage.php?upc={$upc}&nomenu=1"
    title="Extended Histocal Batch Data">Batch Historical</a>
<a class="btn btn-default btn-sm iframe fancyboxLink"
    href="{$url}reports/DDD/SingleItemDDDReport.php?upc=$upc">Loss</a>
</span>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a class="btn btn-default btn-sm iframe fancyboxLink" 
    href="{$url}item/addShelfTag.php?upc={$upc}" title="Queue a tag for this item">Shelf Tag</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a class="btn btn-default btn-sm" 
    href="{$url}item/CloneItemPage.php?id={$upc}" 
    title="Create a duplicate item with a different UPC">Clone Item</a>
HTML;
        }
        $ret .= '</p>';
        
        return $ret;
    }
    
    private function editForm($upc,$isNew)
    {
        $FANNIE_PRODUCT_MODULES = $this->getConfiguredModules();
        $FANNIE_URL = $this->config->get('URL');
        $shown = array();

        $this->addScript('autocomplete.js');
        $this->addScript($FANNIE_URL . 'src/javascript/chosen/chosen.jquery.min.js');
        $this->addCssFile($FANNIE_URL . 'src/javascript/chosen/bootstrap-chosen.css');
        $wsUrl = $FANNIE_URL . 'ws/';

        $authorized = $this->userCanEdit($upc, $isNew);

        // remove action so form cannot be submitted by pressing enter
        $ret = '<form id="item-editor-form" action="' . ($authorized ? filter_input(INPUT_SERVER, 'PHP_SELF') : '') . '" 
            enctype="multipart/form-data" method="post">';
        $ret .= '<div class="container"><div id="alert-area">';

        uasort($FANNIE_PRODUCT_MODULES, array('ItemEditorPage', 'sortModules'));
        $count = 0;
        $mod_js = '';
        $current_width = 100;
        foreach ($FANNIE_PRODUCT_MODULES as $class => $params) {
            $mod = new $class();
            if ($current_width + $mod->width() > 100) {
                $ret .= '</div><div class="row">';
                $current_width = 0;
                $count++;
            }
            switch ($mod->width()) {
                case \COREPOS\Fannie\API\item\ItemModule::META_WIDTH_THIRD:
                    $ret .= '<div class="col-sm-4">' . "\n";
                    break;
                case \COREPOS\Fannie\API\item\ItemModule::META_WIDTH_HALF:
                    $ret .= '<div class="col-sm-6">' . "\n";
                    break;
                case \COREPOS\Fannie\API\item\ItemModule::META_WIDTH_FULL:
                default:
                    $ret .= '<div class="col-sm-12">' . "\n";
                    break;
            }
            $ret .= $mod->ShowEditForm($upc, $params['show'], $params['expand']);
            $ret .= '</div>' . "\n";
            $shown[$class] = true;
            $mod_js .= $mod->getFormJavascript($upc);

            if ($count == 1 && $current_width == 0) { // show links after first mod
                $ret .= $this->editorLinksArea($upc, $isNew, $authorized);
            }

            $current_width += $mod->width();
        }
        $ret .= '</div>'; // close last row
        $ret .= '</div>'; // close fluid-container

        if (isset($shown['BaseItemModule'])) {
            $this->add_onload_command("bindAutoComplete('.brand-field', '$wsUrl', 'brand');\n");
            $this->add_onload_command("bindAutoComplete('input.vendor_field', '$wsUrl', 'vendor');\n");
            $this->add_onload_command("bindAutoComplete('.unit-of-measure', '$wsUrl', 'unit');\n");
            $this->add_onload_command("\$('.unit-of-measure').autocomplete('option', 'minLength', 1);\n");
            $this->add_onload_command("baseItem.addVendorDialog();\n");
            if ($this->config->get('STORE_MODE') == 'HQ') {
                $this->addOnloadCommand("\$('#item-editor-form').submit(baseItem.syncStoreTabs);\n");
                $this->addOnloadCommand("\$('.syncable-input').change(baseItem.syncStoreTabs);\n");
                $this->addOnloadCommand("\$('.syncable-checkbox').change(baseItem.syncStoreTabs);\n");
                $this->addOnloadCommand("baseItem.markUnSynced();\n");
            }
        }

        if (isset($shown['ItemMarginModule'])) {
            $this->add_onload_command('$(\'.price-input\').change(updateMarginMod)');
            $this->add_onload_command('$(\'.cost-input\').change(updateMarginMod)');
        }

        if (isset($shown['ProdUserModule'])) {
            $this->add_onload_command("bindAutoComplete('#lf_brand', '$wsUrl', 'long_brand');\n");
        }

        if (isset($shown['LikeCodeModule'])) {
            $this->add_onload_command("addLcDialog();\n");
        }
        /**
          Chosen initializes incorrectly if the <select> is not displayed
          Reapplying each time a new Bootstrap tab is shown fixes this
        */
        $this->add_onload_command('$(\'.chosen-select:visible\').chosen();');
        $this->add_onload_command('$(\'#store-tabs a\').on(\'shown.bs.tab\', function(){$(\'.chosen-select:visible\').chosen();});');

        $ret .= '</form>';

        if ($mod_js != '') {
            $ret .= '<script type="text/javascript">' . "\n";
            $ret .= $mod_js;
            $ret .= "\n</script>\n";
        }

        $this->add_onload_command('$(\'.fancyboxLink\').fancybox({\'width\':\'85%;\',\'titlePosition\':\'inside\'});');
        if ($this->mode == 'new') {
            $this->add_onload_command('$(\'.descript-input:visible:first\').focus();');
        }
        
        return $ret;
    }

    private function saveItem($isNew)
    {
        $FANNIE_PRODUCT_MODULES = $this->getConfiguredModules();
        $FANNIE_URL = $this->config->get('URL');

        $upc = FormLib::get_form_value('upc','');
        if ($upc === '' || !is_numeric($upc)) {
            return '<span style="color:red;">Error: bad UPC:</span> '.$upc;
        }
        $upc = BarcodeLib::padUPC($upc);

        $authorized = $this->userCanEdit($upc, $isNew);
        $audited = FannieAuth::validateUserQuiet('audited_pricechange') ? true : false;
        if ($authorized !== true) {
            // not authorized to make edits
            return '<span style="color:red;">Error: Log in to edit</span>';
        }

        uasort($FANNIE_PRODUCT_MODULES, array('ItemEditorPage', 'sortModules'));
        $this->saveModules($FANNIE_PRODUCT_MODULES, $upc);

        /* push updates to the lanes */
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $FANNIE_COOP_ID = $this->config->get('COOP_ID');
        if (isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto') {
            updateAllLanes($upc, array('products','productUser'));
        } elseif (isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WFC_Duluth') {
            $queue = new COREPOS\Fannie\API\jobs\QueueManager();
            $queue->add(array(
                'class' => 'COREPOS\\Fannie\\API\\jobs\\SyncItem',
                'data' => array(
                    'upc' => $upc,
                ),
            ));
        } else {
            COREPOS\Fannie\API\data\ItemSync::sync($upc);
        }

        if ($audited) {
            $likecode = FormLib::get('likeCode', -1);
            $no_update = FormLib::get('LikeCodeNoUpdate', false);
            if ($likecode != -1 && !$no_update) {
                \COREPOS\Fannie\API\lib\AuditLib::itemUpdate($upc, $likecode);
            } else {
                \COREPOS\Fannie\API\lib\AuditLib::itemUpdate($upc);
            }
        }

        return $this->modulesResult($FANNIE_PRODUCT_MODULES, $upc);
    }

    private function saveModules($mods, $upc)
    {
        $form = new \COREPOS\common\mvc\FormValueContainer();
        $this->connection->startTransaction();
        foreach ($mods as $class => $params) {
            $mod = new $class();
            $mod->setConnection($this->connection);
            $mod->setConfig($this->config);
            $mod->setForm($form);
            $mod->SaveFormData($upc);
        }
        $this->connection->commitTransaction();
    }

    private function modulesResult($mods, $upc)
    {
        $ret = "<table class=\"table\">";
        foreach ($mods as $class => $params) {
            $mod = new $class();
            $rows = $mod->summaryRows($upc);
            foreach ($rows as $row) {
                $ret .= '<tr>' . $row . '</tr>';
            }
        }
        $ret .= '</table>';

        return $ret;
    }

    public function helpContent()
    {
        $ret = '<p>This tool is for adding or editing an item</p>';
        if ($this->mode == 'search') {
            $ret .= '<p>
                To create a new item, simply enter the UPC. To search for an
                existing item, enter the UPC or part of the description. You
                can also search by vendor SKU or manufacturer UPC prefix by
                changing the UPC dropdown.
                </p>';
        } elseif ($this->mode == 'many') {
            $ret .= '<p>Multiple results found. Click the UPC to edit that item
                or use the browser\'s back button to try a different search</p>';
        } elseif ($this->mode == 'new') {
            $ret .= '<p>
                Creating a <strong>new</strong> item. Minimum required fields are
                description, price, and department (dept). Tax, foodstamp, and scale
                settings will be automatically assigned based on the depatment\'s
                defaults.
                </p>';
        } elseif ($this->mode == 'edit') {
            $ret .= '<p>
                Editing an <strong>existing</strong> item. Changes made here will be
                sent to the lanes immediately.
                </p>';
        }

        if ($this->mode == 'new' || $this->mode == 'edit') {
            $ret .= '<ul>
                    <li>Description appears on the lane screen & receipt</li>
                    <li>Price is the current retail price</li>
                    <li>Brand is solely for backend reporting and organization</li>
                    <li>Vendor indicates the default supplier for the item</li>
                    <li>Department (Dept) is the primary item categorization system.</li> 
                    <li>Tax sets sale tax rate</li>
                    <li>Checking FS indicates the item is eligible for purchase with foodstamps</li>
                    <li>Checking Scale indicates the item should be weighed at checkout.</li>
                    <li>Checking QtyFrc causes the lane to prompt the cashier for a quantity
                        when the item is entered</li>
                    <li>Discount controls which type of discounts apply to the item:
                        <ul>
                        <li>Trxn only means the item is only eligible for discounts that apply to
                        the entire transaction such as a member\'s discount</li>
                        <li>Line only means the item is only eligible for percent discount
                        explictly applied by the cashier as they ring in the item.</li>
                        <li>Yes means the item is eligible for both discounts above</li>
                        <li>No means the item is not eligible for either discount above</li>
                        </ul>
                    </li>
                </ul>';
        }

        return $ret;
    }

    public function javascript_content()
    {
        $WfcDuluth = (FannieConfig::config('COOP_ID') == 'WFC_Duluth') ? 1 : 0;

        return <<<JAVASCRIPT
var flags1 = null;
var flags2 = null;

$(document).ready(function(){
    alterTable();

    var WfcDuluth = '$WfcDuluth';

    // don't use default keyup on inputs
    $('#upc').keyup(function(e){
        e.preventDefault();
        if (e.keyCode == 13) {
            $('#searchbtn').trigger('click');
        }
    });
    $('#searchselect').keyup(function(e){
        if (e.keyCode == 13) {
            $('#searchbtn').trigger('click');
        }
    });
    $('#searchbtn').on('click', function(){
        var opt = $('#searchselect option:selected').text();
        var val = $('#upc').val();
        switch (opt) {
            case 'UPC':
            case 'SKU':
            case 'Brand Prefix':
                document.forms['searchform'].submit();
                break;
            case 'Batch ID':
                window.location = '../batches/newbatch/EditBatchPage.php?id='+val;
                break;
            case 'Vendor ID':
                window.location = 'vendors/VendorIndexPage.php?vid='+val;
                break;
            case 'Product Physical Location':
                window.location = 'ProdLocationEditor.php?store_id=&upc='+val+'&batchCheck=&searchupc=Update+Locations+by+UPC';
                break;
            case 'Owner Number':
                window.location = '../mem/MemRedirect.php?id='+val;
                break;
        }
    });

    // hide store-wise controls for Duluth (HOST server eplum compatibility)
    if (WfcDuluth == 1) {
        $("a.nav-tab").each(function() {
            $(this).css('display', 'none');
        });

        let syncChecked = $("#si_sync").is(":checked");
        if (syncChecked == false) {
            $("#si_sync").trigger('click');
        }
        $("#si_sync").css('display', 'none');

        var prepack = ['Bread', 'DPrepack', 'Prepack'];
        var deli = ['Counter', 'DCounter', 'DKitchen', 'Kitchen'];
        var produce = ['Produce', 'DProduce'];
        var pre = false;
        var del = false;
        var pro = false;
        $(".scale-sync-checkbox").each(function(){
            let name = $(this).parent().find("span.label-text").text();
            let checked = $(this).is(":checked");

            if (checked == true) {
                if (prepack.includes(name)) {
                    pre = true;
                }
                if (deli.includes(name)) {
                    del = true;
                }
                if (produce.includes(name)) {
                    pro = true;
                }
                $(this).trigger('click');
            }
        });

        // hide all sync checkboxes with Hobart scales
        $(".scale-sync-checkbox").each(function(){
            let name = $(this).parent().find("span.label-text").text();
            let checked = $(this).is(":checked");
            //if (name != 'Kitchen' && name != 'Prepack' && name != 'DProduce') {
                $(this).parent().css('display', 'none');
            //} else {
            //    if (name == 'Kitchen') {
            //        let newtext = 'Deli Scales';
            //        $(this).parent().find("span.label-text").text(newtext);
            //        if (del == true && checked == false) {
            //            $(this).trigger('click');
            //        }
            //    }
            //    if (name == 'Prepack') {
            //        let newtext = 'Prepack Scales';
            //        $(this).parent().find("span.label-text").text(newtext);
            //        if (pre == true && checked == false) {
            //            $(this).prop('checked', true);
            //        }
            //    }
            //    if (name == 'DProduce') {
            //        let newtext = 'Produce Scales';
            //        $(this).parent().find("span.label-text").text(newtext);
            //        if (pro == true && checked == false) {
            //            $(this).trigger('click');
            //        }
            //    }
            //}
        });

    }

    /*
        also, ensure flags1[]-flags2[] (organic 17, vegan 3,  seasonal 2, fair trade 1, gluten free 18) always match between stores
    */

    var flags2match = [1, 2, 3, 17, 18];
    if (WfcDuluth == 1) {
        $.each(flags2match, function(i, v) {
            $('input[name="flags1[]"]').on('click', function(){
                flags1 = $('input[name="flags1[]"][value='+v+']').is(':checked');
                flags2 = $('input[name="flags2[]"][value='+v+']').is(':checked');

                if (flags1 != flags2) {
                    $('input[name="flags2[]"][value='+v+']').trigger('click');
                }
            });
            $('input[name="flags2[]"]').on('click', function(){
                flags1 = $('input[name="flags1[]"][value='+v+']').is(':checked');
                flags2 = $('input[name="flags2[]"][value='+v+']').is(':checked');

                if (flags1 != flags2) {
                    $('input[name="flags1[]"][value='+v+']').trigger('click');
                }
            });
        });
    }
});
$(window).on("resize", function(event){
    alterTable();
});
function alterTable()
{
    var windowWidth = $(window).width();
    if (windowWidth < 768) {
        $('table').find('td').each(function(){
            var html = $(this).html();
            $(this).replaceWith('<div class="form-inline">'+html+'</div>');
        });
        $('table').find('th').each(function(){
            var html = $(this).html();
            $(this).replaceWith('<div class="th-alt"><strong>'+html+'</strong></div>');
        });
    } else {
        $('table').find('div.form-inline').each(function(){
            var html = $(this).html();
            $(this).replaceWith('<td class="form-inline">'+html+'</td>');
        });
        $('table').find('div.th-alt').each(function(){
            var html = $(this).html();
            $(this).replaceWith('<th>'+html+'</th>');
        });
   } 
}

JAVASCRIPT;
    }
    
    public function unitTest($phpunit)
    {
        $this->error_msg = '';
        $upc = '0000000004011';
        $phpunit->assertNotEquals(0, strlen($this->errorContent()));
        $phpunit->assertNotEquals(0, strlen($this->searchForm()));
        foreach (array('SKU','Brand Prefix','UPC') as $type) {
            $phpunit->assertInternalType('array', $this->searchQuery($upc, $type, 1, 1));
        }

        list($query, $args) = $this->searchQuery('banana', $type, 0, 1);
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $results = array();
        while ($row = $this->connection->fetchRow($res)) {
            $results[$row['upc']] = $row;
        }
        $phpunit->assertNotEquals(0, strlen($this->multipleResults($results)));

        $phpunit->assertInternalType('boolean', $this->userCanEdit($upc, false));
        $phpunit->assertInternalType('boolean', $this->userCanEdit($upc, true));

        $phpunit->assertNotEquals(0, strlen($this->editorLinksArea($upc, false, false)));
        $phpunit->assertNotEquals(0, strlen($this->editorLinksArea($upc, false, true)));
        $phpunit->assertNotEquals(0, strlen($this->editorLinksArea($upc, true, true)));

        $phpunit->assertNotEquals(0, strlen($this->editForm($upc, false)));
        $phpunit->assertNotEquals(0, strlen($this->editForm($upc, true)));

        $phpunit->assertNotEquals(0, strlen($this->modulesResult(array('BaseItemModule'=>'irrelevant'), $upc)));
    }
}

FannieDispatch::conditionalExec();

