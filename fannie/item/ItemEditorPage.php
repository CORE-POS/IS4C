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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('addProductAllLanes')) {
    include('laneUpdates.php');
}

class ItemEditorPage extends FanniePage 
{

    private $mode = 'search';
    private $msgs = '';

    public $description = '[Item Editor] is the primary item editing tool.';

    function preprocess()
    {
        global $FANNIE_PRODUCT_MODULES;
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
        $this->header = _('Item Maintenance');

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
        global $FANNIE_URL;
        $ret = '';
        if (!empty($this->msgs)) {
            $ret .= '<blockquote style="border:solid 1px black;">';
            $ret .= $this->msgs;
            $ret .= '</blockquote>';
        }
        $ret .= '<form action="ItemEditorPage.php" method=get>';
        $ret .= '<input name=searchupc type=text id=upc> 
            ' . _('Enter') .' 
            <select name="ntype">
            <option>UPC</option>
            <option>SKU</option>
            <option>Brand Prefix</option>
            </select> 
            ' . _('or product name here') . '<br>';

        $ret .= '<input name=searchBtn type=submit value=Go> ';
        $ret .= '</form>';
        $ret .= '<p><a href="AdvancedItemSearch.php">' . _('Advanced Search') . '</a>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<a href="PluRangePage.php">' . _('Find Open PLU Range') . '</a>';
        $ret .= '</p>';
        
        $this->add_script('autocomplete.js');
        $ws = $FANNIE_URL . 'ws/';
        $this->add_onload_command("bindAutoComplete('#upc', '$ws', 'item');\n");
        $this->add_onload_command('$(\'#upc\').focus();');

        return $ret;
    }

    function search_results()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upc = FormLib::get_form_value('searchupc');
        $numType = FormLib::get_form_value('ntype','UPC');

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
                    break;
                case 'Brand Prefix':
                    $query = "SELECT p.*,x.distributor,p.brand AS manufacturer 
                        FROM products as p left join 
                        prodExtra as x on p.upc=x.upc 
                        WHERE p.upc like ?
                        ORDER BY p.upc";
                    $args[] = '%'.$upc.'%';
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
                WHERE description LIKE ?
                ORDER BY description";
            $args[] = '%'.$upc.'%';    
        }

        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($query,$args);

        /**
          Query somehow failed. Unlikely. Show error and search box again.
        */
        if ($result === false) {
            $this->msgs = '<span style="color:red;">' . _('Error searching for') . ':</span> '.$upc;
            return $this->search_form();
        }

        $num = $dbc->num_rows($result);

        /**
          No match for text input. Can't create a new item w/o numeric UPC,
          so show error and search box again.
        */
        if ($num == 0 && !is_numeric($upc)) {
            $this->msgs = '<span style="color:red;">' . _('Error searching for') . ':</span> '.$upc;
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
        } else {
            $row = $dbc->fetch_row($result);
            $actualUPC = $row['upc'];
        }

        return $this->edit_form($actualUPC,$new);
    }

    function multiple_results($results)
    {
        global $FANNIE_URL;
        $ret = '<table id="itemSearchResults" class="tablesorter">';
        $ret .= '<thead><tr>
            <th>UPC</th><th>Description</th><th>Brand</th><th>Reg. Price</th><th>Sale Price</th><th>Modified</th>
            </tr></thead>';
        $ret .= '<tbody>';
        foreach ($results as $upc => $data) {
            $ret .= sprintf('<tr>
                            <td><a href="ItemEditorPage.php?searchupc=%s">%s</a></td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%.2f</td>
                            <td>%s</td>
                            <td>%s</td>
                            </tr>',
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
        global $FANNIE_PRODUCT_MODULES, $FANNIE_URL;
        $shown = array();

        $this->add_script('autocomplete.js');
        $ws = $FANNIE_URL . 'ws/';

        $authorized = false;
        if (FannieAuth::validateUserQuiet('pricechange') || FannieAuth::validateUserQuiet('audited_pricechange')) {
            $authorized = true;
        }

        // remove action so form cannot be submitted by pressing enter
        $ret = '<form action="' . ($authorized ? 'ItemEditorPage.php' : '') . '" method="post">';

        uasort($FANNIE_PRODUCT_MODULES, array('ItemEditorPage', 'sortModules'));
        $count = 0;
        $mod_js = '';
        foreach ($FANNIE_PRODUCT_MODULES as $class => $params) {
            $mod = new $class();
            $ret .= $mod->ShowEditForm($upc, $params['show'], $params['expand']);
            $shown[$class] = true;
            $mod_js .= $mod->getFormJavascript($upc);

            if ($count == 0) { // show links after first mod

                if (!$authorized) {
                    $ret .= sprintf('<a href="%sauth/ui/loginform.php?redirect=%sitem/ItemEditorPage.php?searchupc=%s">Login
                                to edit</a>', $FANNIE_URL, $FANNIE_URL, $upc);
                } else if ($isNew) {
                    $ret .= '<input type="submit" name="createBtn" value="Create Item" />';
                } else {
                    $ret .= '<input type="submit" name="updateBtn" value="Update Item" />';
                }
                $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <a href="ItemEditorPage.php">Back</a>';
                if (!$isNew) {
                    $this->add_script($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.js?v=1');
                    $this->add_css_file($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.css');
                    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $ret .= '<a href="deleteItem.php?submit=submit&upc='.$upc.'">Delete this item</a>';

                    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $ret .= '<a class="iframe fancyboxLink" href="'.$FANNIE_URL.'reports/PriceHistory/?upc='.$upc.'" title="Price History">Price History</a>';

                    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $ret .= '<a class="iframe fancyboxLink" href="'.$FANNIE_URL.'reports/RecentSales/?upc='.$upc.'" title="Sales History">Sales History</a>';

                    $js = "window.open('addShelfTag.php?upc=$upc', 'New Shelftag','location=0,status=1,scrollbars=1,width=300,height=220');";
                    $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    $ret .= '<a href="" onclick="'.$js.'return false;">Shelf Tag</a>';
                }
            }

            $count++;
        }

        if (isset($shown['BaseItemModule'])) {
            $this->add_onload_command("bindAutoComplete('#brand_field', '$ws', 'brand');\n");
            $this->add_onload_command("bindAutoComplete('#vendor_field', '$ws', 'vendor');\n");
            $this->add_onload_command("addVendorDialog();\n");
        }

        if (isset($shown['ProdUserModule'])) {
            $this->add_onload_command("bindAutoComplete('#lf_brand', '$ws', 'long_brand');\n");
        }

        if (isset($shown['LikeCodeModule'])) {
            $this->add_onload_command("addLcDialog();\n");
        }

        $ret .= '</form>';

        if ($mod_js != '') {
            $ret .= '<script type="text/javascript">' . "\n";
            $ret .= $mod_js;
            $ret .= "\n</script>\n";
        }

        $this->add_onload_command('$(\'.fancyboxLink\').fancybox();');
        $this->add_onload_command('$(\'#price\').focus();');
        
        return $ret;
    }

    function save_item($isNew)
    {
        global $FANNIE_OP_DB, $FANNIE_PRODUCT_MODULES;

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
        $dbc = FannieDB::get($FANNIE_OP_DB);
        updateProductAllLanes($upc);

        if ($audited) {
            $lc = FormLib::get('likeCode', -1);
            $no_update = FormLib::get('LikeCodeNoUpdate', false);
            if ($lc != -1 && !$no_update) {
                AuditLib::itemUpdate($upc, $lc);
            } else {
                AuditLib::itemUpdate($upc);
            }
        }

        $ret = "<table border=0>";
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

}

FannieDispatch::conditionalExec(false);

