<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('updateProductAllLanes')) {
    include('laneUpdates.php');
}

class ItemEditorPage extends FanniePage 
{

    private $mode = 'search';
    private $msgs = '';

    public $description = '[Item Editor] is the primary item editing tool.';
    public $themed = true;
    protected $enable_linea = true;

    function preprocess()
    {
        $FANNIE_PRODUCT_MODULES = $this->config->get('PRODUCT_MODULES');
        if (!is_array($FANNIE_PRODUCT_MODULES)) {
            $FANNIE_PRODUCT_MODULES = array('BaseItemModule' => array('seq'=>0, 'show'=>1, 'expand'=>1));
        }
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

        $this->title = _('Fannie') . ' - ' . _('Item Maintenance');
        $this->header = '';//_('Item Maintenance');

        if (FormLib::get_form_value('searchupc') !== '') {
            $this->mode = 'search_results';
        }

        if (FormLib::get_form_value('createBtn') !== ''){
            $this->msgs = $this->save_item(true);
        } else if (FormLib::get_form_value('updateBtn') !== '') {
            $this->msgs = $this->save_item(false);
        }

        return true;
    }

    function body_content()
    {
        switch($this->mode){
            case 'search_results':
                return $this->search_results();
            case 'search':
            default:
                return $this->search_form();
        }
    }

    function search_form()
    {
        $FANNIE_URL = $this->config->get('URL');
        $ret = '';
        if (!empty($this->msgs)) {
            $ret .= '<blockquote style="border:solid 1px black;">';
            $ret .= $this->msgs;
            $ret .= '</blockquote>';
        }
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method=get>';
        $ret .= '
            <div class="container-fluid">
            <div class="row form-group form-inline">
            <input name=searchupc type=text id=upc
                class="form-control" /> 
            ' . _('Enter') .' 
            <select name="ntype" class="form-control">
            <option>UPC</option>
            <option>SKU</option>
            <option>Brand Prefix</option>
            </select> 
            ' . _('or product name here') 
            . '</div></div>';

        $ret .= '<p><button name=searchBtn type=submit
                    class="btn btn-default">Go</button>
                 &nbsp;&nbsp;&nbsp;&nbsp;
                 <label>
                    <input type="checkbox" name="inUse" value="1" />
                    Include items that are not inUse
                 </label>
                 </p>';
        $ret .= '</form>';
        $ret .= '<p><a href="AdvancedItemSearch.php">' . _('Advanced Search') . '</a>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<a href="PluRangePage.php">' . _('Find Open PLU Range') . '</a>';
        $ret .= '</p>';
        
        $this->add_script('autocomplete.js');
        $ws = $FANNIE_URL . 'ws/';
        $this->add_onload_command("bindAutoComplete('#upc', '$ws', 'item');\n");
        $this->add_onload_command('$(\'#upc\').focus();');

        $this->add_script($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.js?v=1');
        $this->add_css_file($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.css');
        $this->add_onload_command('$(\'.fancyboxLink\').fancybox({\'width\':\'85%;\'});');

        // bind scanner to UPC field
        $this->add_onload_command("enableLinea('#upc');\n");

        return $ret;
    }

    function search_results()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $upc = FormLib::get_form_value('searchupc');
        $numType = FormLib::get_form_value('ntype','UPC');
        $inUseFlag = FormLib::get('inUse', false);

        $query = "";
        $args = array();
        if (is_numeric($upc)) {
            switch($numType) {
                case 'SKU':
                    $query = "SELECT p.*,x.distributor,p.brand AS manufacturer 
                        FROM products as p inner join 
                        vendorItems as v ON p.upc=v.upc 
                        left join prodExtra as x on p.upc=x.upc 
                        WHERE v.sku LIKE ?";
                    $args[] = '%'.$upc;
                    if (!$inUseFlag) {
                        $query .= ' AND inUse=1 ';
                    }
                    break;
                case 'Brand Prefix':
                    $query = "SELECT p.*,x.distributor,p.brand AS manufacturer 
                        FROM products as p left join 
                        prodExtra as x on p.upc=x.upc 
                        WHERE p.upc like ?
                        ORDER BY p.upc";
                    $args[] = '%'.$upc.'%';
                    if (!$inUseFlag) {
                        $query .= ' AND inUse=1 ';
                    }
                    break;
                case 'UPC':
                default:
                    $upc = BarcodeLib::padUPC($upc);
                    $query = "SELECT p.*,x.distributor,p.brand AS manufacturer 
                        FROM products as p left join 
                        prodExtra as x on p.upc=x.upc 
                        WHERE p.upc = ?
                        ORDER BY p.description";
                    $args[] = $upc;
                    break;
            }
        } else {
            $query = "SELECT p.*,x.distributor,p.brand AS manufacturer 
                FROM products AS p LEFT JOIN 
                prodExtra AS x ON p.upc=x.upc
                WHERE description LIKE ? ";
            if (!$inUseFlag) {
                $query .= ' AND inUse=1 ';
            }
            $query .= " ORDER BY description";
            $args[] = '%'.$upc.'%';    
        }

        $query = $dbc->addSelectLimit($query, 500);
        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($query,$args);

        /**
          Query somehow failed. Unlikely. Show error and search box again.
        */
        if ($result === false) {
            $this->msgs = '<div class="alert alert-danger">' . _('Error searching for') 
                . ' ' . $upc . '</div>';
            $this->mode = 'search'; // mode drives appropriate help text
            return $this->search_form();
        }

        $num = $dbc->num_rows($result);

        /**
          No match for text input. Can't create a new item w/o numeric UPC,
          so show error and search box again.
        */
        if ($num == 0 && !is_numeric($upc)) {
            $this->msgs = '<div class="alert alert-danger">' . _('Error searching for') 
                . ' ' . $upc . '</div>';
            $this->mode = 'search'; // mode drives appropriate help text
            return $this->search_form();
        }

        /**
          List multiple results
        */
        if ($num > 1) {
            $items = array();
            while($row = $dbc->fetch_row($result)) {
                $items[$row['upc']] = $row;
            }
            $this->mode = 'many'; // mode drives appropriate help text
            return $this->multiple_results($items);
        }

        /**
          Only remaining possibility is a new item or
          editing an existing item
        */
        $actualUPC = '';
        $new = false;
        if ($num == 0) {
            $actualUPC = BarcodeLib::padUPC($upc);
            $new = true;
            $this->mode = 'new'; // mode drives appropriate help text
        } else {
            $row = $dbc->fetch_row($result);
            $actualUPC = $row['upc'];
            $this->mode = 'edit'; // mode drives appropriate help text
        }

        return $this->edit_form($actualUPC,$new);
    }

    function multiple_results($results)
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
                            $_SERVER['PHP_SELF'],
                            $upc, $upc, 
                            $data['description'],
                            $data['manufacturer'],
                            $data['normal_price'],
                            ($data['discounttype'] > 0 ? $data['special_price'] : 'n/a'),
                            $data['modified']
            );
        }
        $ret .= '</tbody></table>';

        $this->add_css_file($FANNIE_URL . 'src/javascript/tablesorter/themes/blue/style.css');
        $this->add_script($FANNIE_URL . 'src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->add_onload_command('$(\'#itemSearchResults\').tablesorter();');

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

    function edit_form($upc,$isNew)
    {
        $FANNIE_PRODUCT_MODULES = $this->config->get('PRODUCT_MODULES');
        $FANNIE_URL = $this->config->get('URL');
        $shown = array();

        $this->add_script('autocomplete.js');
        $this->add_script($FANNIE_URL . 'src/javascript/chosen/chosen.jquery.min.js');
        $this->add_css_file($FANNIE_URL . 'src/javascript/chosen/chosen.min.css');
        $ws = $FANNIE_URL . 'ws/';

        $authorized = false;
        if (FannieAuth::validateUserQuiet('pricechange') || FannieAuth::validateUserQuiet('audited_pricechange')) {
            $authorized = true;
        }

        // remove action so form cannot be submitted by pressing enter
        $ret = '<form action="' . ($authorized ? $_SERVER['PHP_SELF'] : '') . '" method="post">';
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

                $ret .= '<p>';
                if (!$authorized) {
                    $ret .= sprintf('<a class="btn btn-danger"
                                href="%sauth/ui/loginform.php?redirect=%s?searchupc=%s">Login
                                to edit</a>', $FANNIE_URL, $_SERVER['PHP_SELF'], $upc);
                    $this->addOnloadCommand("\$(':input').prop('disabled', true).prop('title','Login to edit');\n");
                } else if ($isNew) {
                    $ret .= '<button type="submit" name="createBtn" value="1"
                                class="btn btn-default">Create Item</button>';
                } else {
                    $ret .= '<button type="submit" name="updateBtn" value="1"
                                class="btn btn-default">Update Item</button>';
                }
                $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a href="' . $_SERVER['PHP_SELF'] . '">Back</a>';
                if (!$isNew) {
                    $this->add_script($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.js?v=1');
                    $this->add_css_file($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.css');
                    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $ret .= '<a href="deleteItem.php?submit=submit&upc='.$upc.'">Delete this item</a>';

                    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $ret .= '<a class="iframe fancyboxLink" href="'.$FANNIE_URL.'reports/PriceHistory/?upc='.$upc.'" title="Price History">Price History</a>';

                    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $ret .= '<a class="iframe fancyboxLink" href="'.$FANNIE_URL.'reports/RecentSales/?upc='.$upc.'" title="Sales History">Sales History</a>';

                    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $ret .= '<a class="iframe fancyboxLink" href="'.$FANNIE_URL.'item/addShelfTag.php?upc='.$upc.'" title="Create Shelf Tag">Shelf Tag</a>';

                    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $ret .=  '<a href="' . $FANNIE_URL . 'item/CloneItemPage.php?id=' . $upc . '" title="Clone Item">Clone Item</a>';
                }
                $ret .= '</p>';
            }

            $current_width += $mod->width();
        }
        $ret .= '</div>'; // close last row
        $ret .= '</div>'; // close fluid-container

        if (isset($shown['BaseItemModule'])) {
            $this->add_onload_command("bindAutoComplete('#brand-field', '$ws', 'brand');\n");
            $this->add_onload_command("bindAutoComplete('#vendor_field', '$ws', 'vendor');\n");
            $this->add_onload_command("addVendorDialog();\n");
        }

        if (isset($shown['ItemMarginModule'])) {
            $this->add_onload_command('$(\'#price\').change(updateMarginMod)');
            $this->add_onload_command('$(\'#cost\').change(updateMarginMod)');
        }

        if (isset($shown['ProdUserModule'])) {
            $this->add_onload_command("bindAutoComplete('#lf_brand', '$ws', 'long_brand');\n");
        }

        if (isset($shown['LikeCodeModule'])) {
            $this->add_onload_command("addLcDialog();\n");
        }
        $this->add_onload_command('$(\'.chosen-select\').chosen();');

        $ret .= '</form>';

        if ($mod_js != '') {
            $ret .= '<script type="text/javascript">' . "\n";
            $ret .= $mod_js;
            $ret .= "\n</script>\n";
        }

        $this->add_onload_command('$(\'.fancyboxLink\').fancybox({\'width\':\'85%;\'});');
        $this->add_onload_command('$(\'#price\').focus();');
        
        return $ret;
    }

    function save_item($isNew)
    {
        $FANNIE_PRODUCT_MODULES = $this->config->get('PRODUCT_MODULES');
        $FANNIE_URL = $this->config->get('URL');

        $upc = FormLib::get_form_value('upc','');
        if ($upc === '' || !is_numeric($upc)) {
            return '<span style="color:red;">Error: bad UPC:</span> '.$upc;
        }
        $upc = BarcodeLib::padUPC($upc);

        $audited = false;
        if (FannieAuth::validateUserQuiet('pricechange')) { 
            // validated; nothing to do
        } else if (FannieAuth::validateUserQuiet('audited_pricechange')) {
            $audited = true;
        } else {
            // not authorized to make edits
            return '<span style="color:red;">Error: Log in to edit</span>';
        }

        uasort($FANNIE_PRODUCT_MODULES, array('ItemEditorPage', 'sortModules'));
        foreach ($FANNIE_PRODUCT_MODULES as $class => $params) {
            $mod = new $class();
            $mod->SaveFormData($upc);
        }

        /* push updates to the lanes */
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        updateProductAllLanes($upc);

        if ($audited) {
            $lc = FormLib::get('likeCode', -1);
            $no_update = FormLib::get('LikeCodeNoUpdate', false);
            if ($lc != -1 && !$no_update) {
                \COREPOS\Fannie\API\lib\AuditLib::itemUpdate($upc, $lc);
            } else {
                \COREPOS\Fannie\API\lib\AuditLib::itemUpdate($upc);
            }
        }

        $ret = "<table class=\"table\">";
        foreach ($FANNIE_PRODUCT_MODULES as $class => $params) {
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
                    <li>Checking NoDisc indicates the item is not eligible for transaction
                        level percent discounts (e.g., a member discount)</li>
                </ul>';
        }

        return $ret;
    }
}

FannieDispatch::conditionalExec(false);

