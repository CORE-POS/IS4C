<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

    This file is part of IT CORE.

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

ini_set('display_errors','Off');
include_once(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));

if ($CORE_LOCAL->get("End") == 1) {
	TransRecord::addtransDiscount();
	TransRecord::addTax();
	$taxes = Database::LineItemTaxes();
	foreach($taxes as $tax){
		TransRecord::addQueued('TAXLINEITEM',$tax['description'],$tax['rate_id'],'',$tax['amount']);
	}
}

$receiptType = isset($_REQUEST['receiptType'])?$_REQUEST['receiptType']:'';

$yesSync = JsonLib::array_to_json(array('sync'=>True));
$noSync = JsonLib::array_to_json(array('sync'=>False));
$output = $noSync;

if (strlen($receiptType) > 0) {

	$receiptContent = array();

	$kicker_class = ($CORE_LOCAL->get("kickerModule")=="") ? 'Kicker' : $CORE_LOCAL->get('kickerModule');
	$kicker_object = new $kicker_class();
	if (!is_object($kicker_object)) $kicker_object = new Kicker();
	$dokick = $kicker_object->doKick();

	$print_class = $CORE_LOCAL->get('ReceiptDriver');
	if ($print_class === '' || !class_exists($print_class))
		$print_class = 'ESCPOSPrintHandler';
	$PRINT_OBJ = new $print_class();

	$email = CoreState::getCustomerPref('email_receipt');
	$customerEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
	$doEmail = ($customerEmail !== False) ? True : False;
	
	if ($receiptType != "none")
		$receiptContent[] = ReceiptLib::printReceipt($receiptType,False,$doEmail);

	if ($CORE_LOCAL->get("ccCustCopy") == 1){
		$CORE_LOCAL->set("ccCustCopy",0);
		$receiptContent[] = ReceiptLib::printReceipt($receiptType);
	}
	elseif ($receiptType == "ccSlip" || $receiptType == 'gcSlip'){
		// don't mess with reprints
	}
	elseif ($CORE_LOCAL->get("autoReprint") == 1){
		$CORE_LOCAL->set("autoReprint",0);
		$receiptContent[] = ReceiptLib::printReceipt($receiptType,True);
	}

	if ($CORE_LOCAL->get("End") >= 1 || $receiptType == "cancelled"
		|| $receiptType == "suspended"){
		$CORE_LOCAL->set("End",0);
		cleartemptrans($receiptType);
		$output = $yesSync;
		UdpComm::udpSend("termReset");
	}

	// close session so if printer hangs
	// this script won't lock the session file
	if (session_id() != ''){
		session_write_close();
	}

	if ($receiptType == "full" && $dokick){
		ReceiptLib::drawerKick();
	}

	$EMAIL_OBJ = new EmailPrintHandler();
	foreach($receiptContent as $receipt){
		if(is_array($receipt)){
			if (!empty($receipt['print']))
				$PRINT_OBJ->writeLine($receipt['print']);
			if (!empty($receipt['any']))
				$EMAIL_OBJ->writeLine($receipt['any'],$customerEmail);
		}
		elseif(!empty($receipt))
			$PRINT_OBJ->writeLine($receipt);
	}
}

$td = SigCapture::term_object();
if (is_object($td))
	$td->WriteToScale("reset");

echo $output;

function cleartemptrans($type) {
	global $CORE_LOCAL;

	TransRecord::emptyQueue();

	// make sure transno advances even if something
	// wacky happens with the db shuffling
	Database::loadglobalvalues();	
	$CORE_LOCAL->set("transno",$CORE_LOCAL->get("transno") + 1);
	Database::setglobalvalue("TransNo", $CORE_LOCAL->get("transno"));

	$db = Database::tDataConnect();

	if($type == "cancelled") {
		$db->query("update localtemptrans set trans_status = 'X'");
	}

	moveTempData();
	truncateTempTables();

	/**
	  Moved to separate ajax call (ajax-transaction-sync.php)
	*/
	if ($CORE_LOCAL->get("testremote")==0)
		Database::testremote(); 

	if ($CORE_LOCAL->get("TaxExempt") != 0) {
		$CORE_LOCAL->set("TaxExempt",0);
		Database::setglobalvalue("TaxExempt", 0);
	}

	CoreState::memberReset();
	CoreState::transReset();
	CoreState::printReset();

	Database::getsubtotals();

	return 1;
}


function truncateTempTables() {
	$connection = Database::tDataConnect();
	$query1 = "truncate table localtemptrans";
	$query2 = "truncate table activitytemplog";
	$query3 = "truncate table couponApplied";

	$connection->query($query1);
	$connection->query($query2);
	$connection->query($query3);
}

function moveTempData() {
	$connection = Database::tDataConnect();

	$connection->query("update localtemptrans set trans_type = 'T' where trans_subtype IN ('CP','IC')");
	$connection->query("update localtemptrans set upc = 'DISCOUNT', description = upc, department = 0, trans_type='S' where trans_status = 'S'");

	$connection->query("insert into localtrans select * from localtemptrans");
	$connection->query("insert into localtrans_today select * from localtemptrans");
	$connection->query("insert into dtransactions select * from localtemptrans");

	$connection->query("insert into activitylog select * from activitytemplog");
	$connection->query("insert into alog select * from activitytemplog");
}
?>
