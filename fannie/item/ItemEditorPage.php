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
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include('laneUpdates.php');

include(dirname(__FILE__).'/modules/BaseItemModule.php');	
include(dirname(__FILE__).'/modules/ScaleItemModule.php');	
include(dirname(__FILE__).'/modules/ExtraInfoModule.php');	
include(dirname(__FILE__).'/modules/ItemLinksModule.php');	
include(dirname(__FILE__).'/modules/LikeCodeModule.php');	
include(dirname(__FILE__).'/modules/ItemMarginModule.php');	
include(dirname(__FILE__).'/modules/ItemFlagsModule.php');	
include(dirname(__FILE__).'/modules/VendorItemModule.php');	
include(dirname(__FILE__).'/modules/AllLanesItemModule.php');	

class ItemEditorPage extends FanniePage {

	private $mode = 'search';
	private $msgs = '';

	function preprocess(){
		$this->title = 'Fannie - Item Maintanence';
		$this->header = 'Item Maintanence';

		if (FormLib::get_form_value('searchupc') !== ''){
			$this->mode = 'search_results';
		}

		if (FormLib::get_form_value('createBtn') !== ''){
			$this->msgs = $this->save_item(True);
		}
		elseif (FormLib::get_form_value('updateBtn') !== ''){
			$this->msgs = $this->save_item(False);
		}

		return True;
	}

	function body_content(){
		switch($this->mode){
		case 'search_results':
			return $this->search_results();
		case 'search':
		default:
			return $this->search_form();
		}
	}

	function search_form(){
		$ret = '';
		if (!empty($this->msgs)){
			$ret .= '<blockquote style="border:solid 1px black;">';
			$ret .= $this->msgs;
			$ret .= '</blockquote>';
		}
		$ret .= '<form action="ItemEditorPage.php" method=get>';
		$ret .= '<input name=searchupc type=text id=upc> Enter 
		<select name=\"ntype\">
		<option>UPC</option>
		<option>SKU</option>
		<option>Brand Prefix</option>
		</select> or product name here<br>';

		$ret .= '<input name=searchBtn type=submit value=submit> ';
		$ret .= '</form>';
		
		$this->add_onload_command('$(\'#upc\').focus();');

		return $ret;
	}

	function search_results(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$upc = FormLib::get_form_value('searchupc');
		$numType = FormLib::get_form_value('ntype','UPC');

		$query = "";
		$args = array();
		if (is_numeric($upc)){
			switch($numType){
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
				$upc = str_pad($upc,13,0,STR_PAD_LEFT);
				$query = "SELECT p.*,x.distributor,x.manufacturer 
					FROM products as p left join 
					prodExtra as x on p.upc=x.upc 
					WHERE p.upc = ?
					ORDER BY p.description";
				$args[] = $upc;
				break;
			}
		}
		else {
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
		if ($result === False){
			$this->msgs = '<span style="color:red;">Error searching for:</span> '.$upc;
			return $this->search_form();
		}

		$num = $dbc->num_rows($result);

		/**
		  No match for text input. Can't create a new item w/o numeric UPC,
		  so show error and search box again.
		*/
		if ($num == 0 && !is_numeric($upc)){
			$this->msgs = '<span style="color:red;">Error searching for:</span> '.$upc;
			return $this->search_form();
		}

		/**
		  List multiple results
		*/
		if ($num > 1){
			$items = array();
			while($row = $dbc->fetch_row($result)){
				$items[$row['upc']] = $row['description'];
			}
			return $this->multiple_results($items);
		}

		/**
		  Only remaining possibility is a new item or
		  editing an existing item
		*/
		$actualUPC = '';
		$new = False;
		if ($num == 0){
			$actualUPC = str_pad($upc,13,'0',STR_PAD_LEFT);
			$new = True;
		}
		else {
			$row = $dbc->fetch_row($result);
			$actualUPC = $row['upc'];
		}
		return $this->edit_form($actualUPC,$new);
	}

	function multiple_results($results){
		$ret = '';
		foreach($results as $upc => $description){
			$ret .= sprintf('<a href="ItemEditorPage.php?searchupc=%s">%s</a> - %s<br />',
				$upc, $upc, $description);
		}
		return $ret;
	}

	function edit_form($upc,$isNew){
		$ret = '<form action="ItemEditorPage.php" method="post">';

		$mod = new BaseItemModule();
		$ret .= $mod->ShowEditForm($upc);

		if ($isNew){
			$ret .= '<input type="submit" name="createBtn" value="Create Item" />';
		}
		else {
			$ret .= '<input type="submit" name="updateBtn" value="Update Item" />';
		}
               	$ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="ItemEditorPage.php">Back</a>';

		if (substr($upc,0,3) == "002"){
			$mod = new ScaleItemModule();
			$ret .= $mod->ShowEditForm($upc);
		}

		$mod = new ExtraInfoModule();
		$ret .= $mod->ShowEditForm($upc);

		$mod = new ItemLinksModule();
		$ret .= $mod->ShowEditForm($upc);

		$mod = new LikeCodeModule();
		$ret .= $mod->ShowEditForm($upc);

		$mod = new ItemMarginModule();
		$ret .= $mod->ShowEditForm($upc);

		$mod = new ItemFlagsModule();
		$ret .= $mod->ShowEditForm($upc);

		if (!$isNew){
			$mod = new VendorItemModule();
			$ret .= $mod->ShowEditForm($upc);

			$mod = new AllLanesItemModule();
			$ret .= $mod->ShowEditForm($upc);
		}

		$ret .= '</form>';

		$this->add_onload_command('$(\'#price\').focus();');
		
		return $ret;
	}

	function save_item($isNew){
		global $FANNIE_OP_DB;

		$upc = FormLib::get_form_value('upc','');
		if ($upc === '' || !is_numeric($upc)){
			return '<span style="color:red;">Error: bad UPC:</span> '.$upc;
		}

		$mod = new BaseItemModule();
		$mod->SaveFormData($upc);

		$mod = new ExtraInfoModule();
		$mod->SaveFormData($upc);

		$mod = new ScaleItemModule();
		$mod->SaveFormData($upc);

		$mod = new ItemFlagsModule();
		$mod->SaveFormData($upc);

		$mod = new LikeCodeModule();
		$mod->SaveFormData($upc);

		/* push updates to the lanes */
		$dbc = FannieDB::get($FANNIE_OP_DB);
		updateProductAllLanes($upc);

		$verify = $dbc->prepare_statement('SELECT upc,description,normal_price,department,subdept,
				foodstamp,scale,qttyEnforced,discount,inUse,deposit
				 FROM products WHERE upc = ?');
		$result = $dbc->exec_statement($verify,array($upc));
		$row = $dbc->fetch_array($result);
		$ret = "<table border=0>";
		$ret .= "<tr><td align=right><b>UPC</b></td><td><font color='red'>".$row['upc']."</font><input type=hidden value='{$row['upc']}' name=upc></td>";
		$ret .= "</tr><tr><td><b>Description</b></td><td>{$row['description']}</td>";
		$ret .= "<td><b>Price</b></td><td>\${$row['normal_price']}</td></tr></table>";
		return $ret;
	}

}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new ItemEditorPage();
	$obj->draw_page();
}

?>
