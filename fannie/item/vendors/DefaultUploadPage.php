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

include('../../config.php');
if (!class_exists('FannieUploadPage'))
	include_once($FANNIE_ROOT.'classlib2.0/FannieUploadPage.php');
if (!class_exists('FannieDB'))
	include_once($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
if (!class_exists('ProductsController'))
	include_once($FANNIE_ROOT.'classlib2.0/data/controllers/ProductsController.php');

/* this page requires a session to pass some extra
   state information through multiple requests */
@session_start();

class DefaultUploadPage extends FannieUploadPage {

	public $title = "Fannie - Load Vendor Prices";
	public $header = "Upload Vendor price file";

	protected $preview_opts = array(
		'upc' => array(
			'name' => 'upc',
			'display_name' => 'UPC',
			'default' => 0,
			'required' => True
		),
		'srp' => array(
			'name' => 'srp',
			'display_name' => 'SRP',
			'default' => 1,
			'required' => True
		),
		'brand' => array(
			'name' => 'brand',
			'display_name' => 'Brand',
			'default' => 2,
			'required' => True
		),
		'desc' => array(
			'name' => 'desc',
			'display_name' => 'Description',
			'default' => 3,
			'required' => True
		),
		'sku' => array(
			'name' => 'sku',
			'display_name' => 'SKU',
			'default' => 4,
			'required' => False
		),
		'qty' => array(
			'name' => 'qty',
			'display_name' => 'Case Qty',
			'default' => 5,
			'required' => True
		),
		'size' => array(
			'name' => 'size',
			'display_name' => 'Unit Size',
			'default' => 6,
			'required' => False
		),
		'cost' => array(
			'name' => 'cost',
			'display_name' => 'Case Cost',
			'default' => 7,
			'required' => True
		)
	);

	protected $use_splits = True;

	function process_file($linedata){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		if (!isset($_SESSION['vid'])){
			$this->error_details = 'Missing vendor setting';
			return False;
		}
		$VENDOR_ID = $_SESSION['vid'];

		$p = $dbc->prepare_statement("SELECT vendorID FROM vendors WHERE vendorID=?");
		$idR = $dbc->exec_statement($p,array($VENDOR_ID));
		if ($dbc->num_rows($idR) == 0){
			$this->error_details = 'Cannot find vendor';
			return False;
		}

		$SKU = $this->get_column_index('sku');
		$BRAND = $this->get_column_index('brand');
		$DESCRIPTION = $this->get_column_index('desc');
		$QTY = $this->get_column_index('qty');
		$SIZE1 = $this->get_column_index('size');
		$UPC = $this->get_column_index('upc');
		$CATEGORY = $this->get_column_index('cat');
		$REG_COST = $this->get_column_index('cost');
		$NET_COST = $this->get_column_index('cost');
		$SRP = $this->get_column_index('srp');

		$itemP = $dbc->prepare_statement("INSERT INTO vendorItems 
					(brand,sku,size,upc,units,cost,description,vendorDept,vendorID)
					VALUES (?,?,?,?,?,?,?,?,?)");
		$srpP = $dbc->prepare_statement("INSERT INTO vendorSRPs (vendorID, upc, srp) VALUES (?,?,?)");

		foreach($linedata as $data){
			if (!is_array($data)) continue;

			if (!isset($data[$UPC])) continue;

			// grab data from appropriate columns
			$sku = $data[$SKU];
			$brand = $data[$BRAND];
			$description = $data[$DESCRIPTION];
			$qty = $data[$QTY];
			$size = $data[$SIZE1];
			$upc = substr($data[$UPC],0,13);
			// zeroes isn't a real item, skip it
			if ($upc == "0000000000000")
				continue;
			if ($_SESSION['vUploadCheckDigits'])
				$upc = '0'.substr($upc,0,12);
			$category = $data[$CATEGORY];
			$reg = trim($data[$REG_COST]);
			$net = trim($data[$NET_COST]);
			$srp = trim($data[$SRP]);
			// can't process items w/o price (usually promos/samples anyway)
			if (empty($reg) or empty($net) or empty($srp))
				continue;

			// syntax fixes. kill apostrophes in text fields,
			// trim $ off amounts as well as commas for the
			// occasional > $1,000 item
			$brand = str_replace("'","",$brand);
			$description = str_replace("'","",$description);
			$reg = str_replace('$',"",$reg);
			$reg = str_replace(",","",$reg);
			$net = $reg;
			$srp = str_replace('$',"",$srp);
			$srp = str_replace(",","",$srp);

			// skip the item if prices aren't numeric
			// this will catch the 'label' line in the first CSV split
			// since the splits get returned in file system order,
			// we can't be certain *when* that chunk will come up
			if (!is_numeric($reg) or !is_numeric($net) or !is_numeric($srp))
				continue;

			// need unit cost, not case cost
			$reg_unit = $reg / $qty;

			$args = array($brand,($sku===False?'':$sku),($size===False?'':$size),
					$upc,$qty,$reg_unit,$description,$category,$VENDOR_ID);
			$dbc->exec_statement($itemP,$args);

			$dbc->exec_statement($srpP,array($VENDOR_ID,$upc,$srp));
		}

		return True;
	}

	/* clear tables before processing */
	function split_start(){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		if (!isset($_SESSION['vid'])){
			$this->error_details = 'Missing vendor setting';
			return False;
		}
		$VENDOR_ID = $_SESSION['vid'];

		$p = $dbc->prepare_statement("SELECT vendorID FROM vendors WHERE vendorID=?");
		$idR = $dbc->exec_statement($p,array($VENDOR_ID));
		if ($dbc->num_rows($idR) == 0){
			$this->error_details = 'Cannot find vendor';
			return False;
		}

		$p = $dbc->prepare_statement("DELETE FROM vendorItems WHERE vendorID=?");
		$dbc->exec_statement($p,array($VENDOR_ID));
		$p = $dbc->prepare_statement("DELETE FROM vendorSRPs WHERE vendorID=?");
		$dbc->exec_statement($p,array($VENDOR_ID));

		if (FormLib::get_form_value('rm_cds') !== '')
			$_SESSION['vUploadCheckDigits'] = True;
		else
			$_SESSION['vUploadCheckDigits'] = False;
	}

	function preview_content(){
		return '<input type="checkbox" name="rm_cds" checked /> Remove check digits';
	}

	function results_content(){
		$ret = "Price data import complete<p />";
		$ret .= '<a href="'.$_SERVER['PHP_SELF'].'">Upload Another</a>';
		unset($_SESSION['vid']);
		unset($_SESSION['vUploadCheckDigits']);
		return $ret;
	}

	function form_content(){
		global $FANNIE_OP_DB;
		$vid = FormLib::get_form_value('vid');
		if ($vid === ''){
			$this->add_onload_command("\$('#FannieUploadForm').remove();");
			return '<span style="color:red;">Error: No Vendor Selected</span>';
		}
		$dbc = FannieDB::get($FANNIE_OP_DB);
		$vp = $dbc->prepare_statement('SELECT vendorName FROM vendors WHERE vendorID=?');
		$vr = $dbc->exec_statement($vp,array($vid));
		if ($dbc->num_rows($vr)==0){
			$this->add_onload_command("\$('#FannieUploadForm').remove();");
			return '<span style="color:red;">Error: No Vendor Found</span>';
		}
		$vrow = $dbc->fetch_row($vr);
		$_SESSION['vid'] = $vid;
		return '<fieldset><legend>Instructions</legend>
			Upload a price file for <i>'.$vrow['vendorName'].'</i> ('.$vid.'). File must be
			CSV. Files &gt; 2MB may be zipped.</fieldset><br />';
	}
}

$obj = new DefaultUploadPage();
$obj->draw_page();
