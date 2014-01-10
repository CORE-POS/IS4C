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

include('../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
include('laneUpdates.php');

// validate modules & include class definitions
if (!is_array($FANNIE_PRODUCT_MODULES)) $FANNIE_PRODUCT_MODULES = 'BaseItemModule';
for($i=0;$i<count($FANNIE_PRODUCT_MODULES);$i++) {
    $mod = $FANNIE_PRODUCT_MODULES[$i];
    if (class_exists($mod)) continue;
    $file = dirname(__FILE__).'/modules/'.$mod.'.php';
    if (!file_exists($file)) {
        $FANNIE_PRODUCT_MODULES[$i] = ''; // not found
    } else {
        include_once($file);
        if (!class_exists($mod)) {
            $FANNIE_PRODUCT_MODULES[$i] = ''; // still not found
        }
    }
}

class ItemEditorPage extends FanniePage {

    private $mode = 'search';
    private $msgs = '';

    function preprocess()
    {
        $this->title = 'Fannie - Item Maintenance';
        $this->header = 'Item Maintenance';

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
        $ret = '';
        if (!empty($this->msgs)) {
            $ret .= '<blockquote style="border:solid 1px black;">';
            $ret .= $this->msgs;
            $ret .= '</blockquote>';
        }
        $ret .= '<form action="ItemEditorPage.php" method=get>';
        $ret .= '<input name=searchupc type=text id=upc> Enter 
        <select name="ntype">
        <option>UPC</option>
        <option>SKU</option>
        <option>Brand Prefix</option>
        </select> or product name here<br>';

        $ret .= '<input name=searchBtn type=submit value=Go> ';
        $ret .= '</form>';
        $ret .= '<p><a href="AdvancedItemSearch.php">Advanced Search</a></p>';
        
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
                    $query = "SELECT p.*,x.distributor,x.manufacturer 
                        FROM products as p inner join 
                        vendorItems as v ON p.upc=v.upc 
                        left join prodExtra as x on p.upc=x.upc 
                        WHERE v.sku LIKE ?";
                    $args[] = '%'.$upc;
                    break;
                case 'Brand Prefix':
                    $query = "SELECT p.*,x.distributor,x.manufacturer 
                        FROM products as p left join 
                        prodExtra as x on p.upc=x.upc 
                        WHERE p.upc like ?
                        ORDER BY p.upc";
                    $args[] = '%'.$upc.'%';
                    break;
                case 'UPC':
                default:
                    $upc = BarcodeLib::padUPC($upc);
                    $query = "SELECT p.*,x.distributor,x.manufacturer 
                        FROM products as p left join 
                        prodExtra as x on p.upc=x.upc 
                        WHERE p.upc = ?
                        ORDER BY p.description";
                    $args[] = $upc;
                    break;
            }
        } else {
            $query = "SELECT p.*,x.distributor,x.manufacturer 
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
            $this->msgs = '<span style="color:red;">Error searching for:</span> '.$upc;
            return $this->search_form();
        }

        $num = $dbc->num_rows($result);

        /**
          No match for text input. Can't create a new item w/o numeric UPC,
          so show error and search box again.
        */
        if ($num == 0 && !is_numeric($upc)) {
            $this->msgs = '<span style="color:red;">Error searching for:</span> '.$upc;
            return $this->search_form();
        }

        /**
          List multiple results
        */
        if ($num > 1) {
            $items = array();
            while($row = $dbc->fetch_row($result)) {
                $items[$row['upc']] = $row['description'];
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
        $ret = '';
        foreach($results as $upc => $description) {
            $ret .= sprintf('<a href="ItemEditorPage.php?searchupc=%s">%s</a> - %s<br />',
                $upc, $upc, $description);
        }

        return $ret;
    }

    function edit_form($upc,$isNew)
    {
        global $FANNIE_PRODUCT_MODULES, $FANNIE_URL;
        $shown = array();

        $authorized = false;
        if (FannieAuth::validateUserQuiet('pricechange') || FannieAuth::validateUserQuiet('audited_pricechange')) {
            $authorized = true;
        }

        // remove action so form cannot be submitted by pressing enter
        $ret = '<form action="' . ($authorized ? 'ItemEditorPage.php' : '') . '" method="post">';

        if (in_array('BaseItemModule',$FANNIE_PRODUCT_MODULES)) {
            $mod = new BaseItemModule();
            $ret .= $mod->ShowEditForm($upc);
            $shown['BaseItemModule'] = true;
        }

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
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= '<a href="deleteItem.php?submit=submit&upc='.$upc.'">Delete this item</a>';

            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= '<a href="'.$FANNIE_URL.'reports/PriceHistory/?upc='.$upc.'" target="_price_history">Price History</a>';

            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= '<a href="'.$FANNIE_URL.'reports/RecentSales/?upc='.$upc.'" target="_recentsales">Sales History</a>';

            $js = "window.open('addShelfTag.php?upc=$upc', 'New Shelftag','location=0,status=1,scrollbars=1,width=300,height=220');";
            $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            $ret .= '<a href="" onclick="'.$js.'return false;">Shelf Tag</a>';
        }

        if (in_array('ScaleItemModule',$FANNIE_PRODUCT_MODULES)) {
            if (substr($upc,0,3) == "002") {
                $mod = new ScaleItemModule();
                $ret .= $mod->ShowEditForm($upc);
            }
            $shown['ScaleItemModule'] = true;
        }

        if (in_array('ExtraInfoModule',$FANNIE_PRODUCT_MODULES)) {
            $mod = new ExtraInfoModule();
            $ret .= $mod->ShowEditForm($upc);
            $shown['ExtraInfoModule'] = true;
        }

        if (in_array('LikeCodeModule',$FANNIE_PRODUCT_MODULES)) {
            $mod = new LikeCodeModule();
            $ret .= $mod->ShowEditForm($upc);
            $shown['LikeCodeModule'] = true;
        }

        if (in_array('ItemMarginModule',$FANNIE_PRODUCT_MODULES)) {
            $mod = new ItemMarginModule();
            $ret .= $mod->ShowEditForm($upc);
            $shown['ItemMarginModule'] = true;
        }

        if (in_array('ItemFlagsModule',$FANNIE_PRODUCT_MODULES)) {
            $mod = new ItemFlagsModule();
            $ret .= $mod->ShowEditForm($upc);
            $shown['ItemFlagsModule'] = true;
        }

        if (!$isNew) {
            if (in_array('VendorItemModule',$FANNIE_PRODUCT_MODULES)) {
                $mod = new VendorItemModule();
                $ret .= $mod->ShowEditForm($upc);
                $shown['VendorItemModule'] = true;
            }

            if (in_array('AllLanesItemModule',$FANNIE_PRODUCT_MODULES)) {
                $mod = new AllLanesItemModule();
                $ret .= $mod->ShowEditForm($upc);
                $shown['AllLanesItemModule'] = true;
            }
        }

        // show any remaining, valid modules
        foreach($FANNIE_PRODUCT_MODULES as $mod) {
            if ($mod == '') continue;
            if (isset($shown[$mod])) continue;
            if (!class_exists($mod)) {
                include_once(dirname(__FILE__).'/modules/'.$mod.'.php');
            }
            if (!class_exists($mod)) continue;
            $obj = new $mod();
            $ret .= $obj->ShowEditForm($upc);
        }

        $ret .= '</form>';

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

        $audited = false;
        if (FannieAuth::validateUserQuiet('pricechange')) { 
            // validated; nothing to do
        } else if (FannieAuth::validateUserQuiet('audited_pricechange')) {
            $audited = true;
        } else {
            // not authorized to make edits
            return '<span style="color:red;">Error: Log in to edit</span>';
        }

        // save base module data first
        if (in_array('BaseItemModule',$FANNIE_PRODUCT_MODULES)) {
            $mod = new BaseItemModule();
            $mod->SaveFormData($upc);
        }

        // save everything else
        foreach($FANNIE_PRODUCT_MODULES as $mod) {
            if ($mod == '') continue;
            if ($mod == 'BaseItemModule') continue;
            $obj = new $mod();
            $sfd = $obj->SaveFormData($upc);
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

        $verify = $dbc->prepare_statement('SELECT upc,description,normal_price,department,subdept,
                foodstamp,scale,qttyEnforced,discount,inUse,deposit
                 FROM products WHERE upc = ?');
        $result = $dbc->exec_statement($verify,array($upc));
        $row = $dbc->fetch_array($result);
        $ret = "<table border=0>";
        $ret .= "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$row['upc']."</font><input type=hidden value='{$row['upc']}' name=upc></td>";
        $js = "window.open('addShelfTag.php?upc=$upc', 'New Shelftag','location=0,status=1,scrollbars=1,width=300,height=220');";
        $ret .= "<td colspan=2 align=right><a href=\"\" onclick=\"{$js}return false;\">Shelf Tag</a></td>";
        $ret .= "</tr><tr><td><b>Description</b></td><td>{$row['description']}</td>";
        $ret .= "<td><b>Price</b></td><td>\${$row['normal_price']}</td></tr></table>";

        return $ret;
    }

}

FannieDispatch::conditionalExec(false);

