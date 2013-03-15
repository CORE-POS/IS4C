<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
	 6Mar2013 Andy Theuninck re-do as class
	 4Sep2012 Eric Lee Add some notes to the initial page.
*/
include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieUploadPage.php');
include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
include($FANNIE_ROOT.'classlib2.0/data/controllers/MeminfoController.php');

class MemNameNumImportPage extends FannieUploadPage {
	protected $title = "Fannie :: Member Tools";
	protected $header = "Import Member Names &amp; Numbers";

	protected $preview_opts = array(
		'memnum' => array(
			'name' => 'memnum',
			'display_name' => 'Member Number',
			'default' => 0,
			'required' => True
		),
		'fn' => array(
			'name' => 'fn',
			'display_name' => 'First Name',
			'default' => 1,
			'required' => True
		),
		'ln' => array(
			'name' => 'ln',
			'display_name' => 'Last Name',
			'default' => 2,
			'required' => True
		),
		'mtype' => array(
			'name' => 'memtype',
			'display_name' => 'Type',
			'default' => 3,
			'required' => False
		)
	);


	private $details = '';
	
	function process_file($linedata){
		global $FANNIE_OP_DB;
		$dbc = FannieDB::get($FANNIE_OP_DB);

		$mn_index = $this->get_column_index('memnum');
		$fn_index = $this->get_column_index('fn');
		$ln_index = $this->get_column_index('ln');
		$t_index = $this->get_column_index('mtype');

		$defaults_table = array();
		$defQ = $dbc->prepare_statement("SELECT memtype,cd_type,discount,staff,SSI from memdefaults");
		$defR = $dbc->exec_statement($defQ);
		while($defW = $dbc->fetch_row($defR)){
			$defaults_table[$defW['memtype']] = array(
				'type' => $defW['cd_type'],
				'discount' => $defW['discount'],
				'staff' => $defW['staff'],
				'SSI' => $defW['SSI']
			);
		}

		// prepare statements
		$perP = $dbc->prepare_statement("SELECT MAX(personNum) FROM custdata WHERE CardNo=?");
		$insP = $dbc->prepare_statement("INSERT INTO custdata (CardNo,personNum,LastName,FirstName,CashBack,
			Balance,Discount,MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,
			memType,staff,SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown)
			VALUES (?,?,?,?,0,0,?,0,0,0,0,?,?,?,?,0,0,0,?,1)");
		$dateP = $dbc->prepare_statement('INSERT INTO memDates (card_no) VALUES (?)');
		foreach($linedata as $line){
			// get info from file and member-type default settings
			// if applicable
			$cardno = $line[$mn_index];
			if (!is_numeric($cardno)) continue; // skip bad record
			$ln = $line[$ln_index];
			$fn = $line[$fn_index];	
			$mtype = ($t_index !== False) ? $line[$t_index] : 0;
			$type = "PC";
			$discount = 0;
			$staff = 0;
			$SSI = 0;
			if ($t_index !== False){
				if (isset($defaults_table[$mtype]['type']))
					$type = $defaults_table[$mtype]['type'];
				if (isset($defaults_table[$mtype]['discount']))
					$discount = $defaults_table[$mtype]['discount'];
				if (isset($defaults_table[$mtype]['staff']))
					$staff = $defaults_table[$mtype]['staff'];
				if (isset($defaults_table[$mtype]['SSI']))
					$SSI = $defaults_table[$mtype]['SSI'];
			}

			// determine person number
			$perR = $dbc->exec_statement($perP,array($cardno));
			$result = array_pop($dbc->fetch_row($perR));
			$pn = !empty($result) ? ($result+1) : 1;
		
			$insR = $dbc->exec_statement($insP,array($cardno,$pn,$ln,$fn,
				$discount,$type,$memtype,$staff,$SSI,$cardno.' '.$ln));
			if ($insR === False){
				$this->details .= "<b>Error importing member $cardno ($fn $ln)</b><br />";
			}
			else {
				$this->details .= "Imported member $cardno ($fn $ln)<br />";
			}

			if ($pn == 1){
				MeminfoController::update($cardno,array());
				$dbc->exec_statement($dateP,array($cardno));
			}
		}
		return True;
	}
	
	function form_content(){
		return '<fieldset><legend>Instructions</legend>
		Upload a CSV or XLS file containing member numbers, first &amp; last names,
		and optionally type IDs.
		<br />A preview helps you to choose and map spreadsheet fields to the database.
		<br />The uploaded file will be deleted after the load.
		</fieldset><br />';
	}

	function results_content(){
		return $this->details .= 'Import completed successfully';
	}
}
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)){
	$obj = new MemNameNumImportPage();
	$obj->draw_page();
}
?>
