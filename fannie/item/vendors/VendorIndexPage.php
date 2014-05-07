<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	
	12Mar2013 Andy Theuninck Use API classes
	 7Sep2012 Eric Lee Display vendorID in select.
	                   Display both "Select" and "New" options.

*/

include('../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class VendorIndexPage extends FanniePage {

	protected $title = "Fannie : Manage Vendors";
	protected $header = "Manage Vendors";

	function preprocess(){

		$ajax = FormLib::get_form_value('action');
		if ($ajax !== ''){
			$this->ajax_callbacks($ajax);
			return False;
		}		

		return True;
	}

	function ajax_callbacks($action){
		global $FANNIE_OP_DB;
		switch($action){
		case 'vendorDisplay':
			$this->getVendorInfo(FormLib::get_form_value('vid',0));	
			break;
		case 'newVendor':
			$this->newVendor(FormLib::get_form_value('name',''));
			break;
		case 'saveContactInfo':
			$id = FormLib::get_form_value('vendorID','');
			if ($id === ''){
				echo 'Bad request';
				break;
			}
			$vcModel = new VendorContactModel(FannieDB::get($FANNIE_OP_DB));
			$vcModel->vendorID($id);
			$vcModel->phone(FormLib::get_form_value('phone'));
			$vcModel->fax(FormLib::get_form_value('fax'));
			$vcModel->email(FormLib::get_form_value('email'));
			$web = FormLib::get_form_value('website');
			if (!empty($web) && substr(strtolower($web),0,4) !== "http")
				$web = 'http://'.$web;
			$vcModel->website($web);
			$vcModel->notes(FormLib::get_form_value('notes'));
			$vcModel->save();
			$this->getVendorInfo($id);
			break;
		default:
			echo 'Bad request'; 
			break;
		}
	}

	private function getVendorInfo($id){
		global $FANNIE_OP_DB,$FANNIE_ROOT;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$ret = "";

		$nameQ = $dbc->prepare_statement("SELECT vendorName FROM vendors WHERE vendorID=?");
		$nameR = $dbc->exec_statement($nameQ,array($id));
		if ($dbc->num_rows($nameR) < 1)
			$ret .= "<b>Name</b>: Unknown";
		else
			$ret .= "<b>Id</b>: $id &nbsp; <b>Name</b>: ".array_pop($dbc->fetch_row($nameR));
		$ret .= "<p />";

		$itemQ = $dbc->prepare_statement("SELECT COUNT(*) FROM vendorItems WHERE vendorID=?");
		$itemR = $dbc->exec_statement($itemQ,array($id));
		$num = array_pop($dbc->fetch_row($itemR));
		if ($num == 0)
			$ret .= "This vendor contains 0 items";
		else {
			$ret .= "This vendor contains $num items";
			$ret .= "<br />";
			$ret .= "<a href=\"BrowseVendorItems.php?vid=$id\">Browse vendor catalog</a>";	
		}
		$ret .= "<br />";
		$ret .= "<a href=\"DefaultUploadPage.php?vid=$id\">Update vendor catalog</a>";
		$ret .= "<br />";
		$ret .= "<a href=\"UploadPluMapPage.php?vid=$id\">Update PLU/SKU mapping</a>";
		$ret .= "<p />";

		$itemQ = $dbc->prepare_statement("SELECT COUNT(*) FROM vendorDepartments WHERE vendorID=?");
		$itemR = $dbc->exec_statement($itemQ,array($id));
		$num = array_pop($dbc->fetch_row($itemR));
		if ($num == 0)
			$ret .= "<a href=\"VendorDepartmentEditor.php?vid=$id\">This vendor's items are not yet arranged into departments</a>";
		else {
			$ret .= "This vendor's items are divided into ";
			$ret .= $num." departments";
			$ret .= "<br />";
			$ret .= "<a href=\"VendorDepartmentEditor.php?vid=$id\">Display/Edit vendor departments</a>";
		}

		$vcModel = new VendorContactModel($dbc);
		$vcModel->vendorID($id);
		$vcModel->load();
		$ret .= '<ul>';
		$ret .= '<li>Phone: <span id="vcPhone">'.$vcModel->phone().'</span></li>';
		$ret .= '<li>Fax: <span id="vcFax">'.$vcModel->fax().'</span></li>';
		$ret .= '<li>Email: <span id="vcEmail">'.$vcModel->email().'</span></li>';
		$ret .= '<li>Website: <span id="vcWebsite">'.$vcModel->website().'</span></li>';
		$ret .= '<li>Ordering Notes: <span id="vcNotes">'.$vcModel->notes().'</span></li>';
		$ret .= '</ul>';
		$ret .= '<a href="" onclick="editSaveVC('.$id.');return false;" id="vcEditSave">Edit Contact Info</a>';

		echo $ret;
	}

	private function newVendor($name){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$id = 1;	
		$p = $dbc->prepare_statement("SELECT max(vendorID) FROM vendors");
		$rp = $dbc->exec_statement($p);
		$rw = $dbc->fetch_row($rp);
		if ($rw[0] != "")
			$id = $rw[0]+1;

		$insQ = $dbc->prepare_statement("INSERT INTO vendors VALUES (?,?)");
		$dbc->exec_statement($insQ,array($id,$name));

		echo $id;
	}

	function body_content(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$vendors = "<option value=\"\">Select a vendor...</option>";
		$vendors .= "<option value=\"new\">New vendor...</option>";
		$q = $dbc->prepare_statement("SELECT * FROM vendors ORDER BY vendorName");
		$rp = $dbc->exec_statement($q);
		$vid = FormLib::get_form_value('vid');
		while($rw = $dbc->fetch_row($rp)){
			if ($vid !== '' && $vid == $rw[0])
				$vendors .= "<option selected value=$rw[0]>$rw[0] $rw[1]</option>";
			else
				$vendors .= "<option value=$rw[0]>$rw[0] $rw[1]</option>";
		}
		ob_start();
		?>
		<div id="vendorarea">
		<select onchange="vendorchange();" id=vendorselect>
		<?php echo $vendors; ?>
		</select>
		</div>
		<hr />
		<div id="contentarea">
		</div>
		<?php

		$this->add_script('index.js');
		$this->add_onload_command('vendorchange();');

		return ob_get_clean();
	}
}

FannieDispatch::conditionalExec(false);

?>
