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

require('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FanniePage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/lib/FormLib.php');

class DeleteShelfTags extends FanniePage {

	protected $title = 'Fannie - Clear Shelf Tags';
	protected $header = 'Clear Shelf Tags';
	protected $must_authenticate = True;
	protected $auth_classes = array('barcodes');

	private $messages = '';

	function preprocess(){
		global $FANNIE_OP_DB;
		$id = FormLib::get_form_value('id',0);

		$dbc = FannieDB::get($FANNIE_OP_DB);
		$checkNoQ = $dbc->prepare_statement("SELECT * FROM shelftags where id=?");
		$checkNoR = $dbc->exec_statement($checkNoQ,array($id));

		$checkNoN = $dbc->num_rows($checkNoR);
		if($checkNoN == 0){
			$this->messages = "Barcode table is already empty. <a href='ShelfTagIndex.php'>Click here to continue</a>";
			return True;
		}

		if(FormLib::get_form_value('submit',False) === '1'){
			$deleteQ = "UPDATE shelftags SET id=-1*id WHERE id=?";
			if ($id == 0)
			      $deleteQ = "UPDATE shelftags SET id=-999 WHERE id=?";
			$prep = $dbc->prepare_statement($deleteQ);
			$deleteR = $dbc->exec_statement($prep, array($id));
			$this->messages = "Barcode table cleared <a href='ShelfTagIndex.php'>Click here to continue</a>";
			return True;
		}
		else{
			$this->messages = "<span style=\"color:red;\"><a href='DeleteShelfTags.php?id=$id&submit=1'>Click 
				here to clear barcodes</a></span>";
			return True;
		}

		return True;
	}

	function body_content(){
		return $this->messages;
	}
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new DeleteShelfTags();
	$obj->draw_page();
}

?>
