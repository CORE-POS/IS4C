<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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
if (!function_exists("build_time")) include_once($CORE_PATH."lib/ReceiptLib.php");

/**
  @class TenderReport
  Generate a tender report
*/
class TenderReport extends LibraryClass {

/** 
 Print a tender report
 
 This tender report is based on a single tender tape view
 rather than multiple views (e.g. ckTenders, ckTenderTotal, etc)
 adding a new tender is mostly just a matter of adding it
 to the $DESIRED_TENDERS array (exception being if you want
 special handling in the tender tape view (e.g., three
 tender types are actually compined under EBT)

 @todo Make $DESIRED_TENDERS configurable elsewhere
 */
static public function get(){
	global $CORE_LOCAL;

	$DESIRED_TENDERS = array("CK"=>"CHECK TENDERS",
				 "CC"=>"CREDIT CARD TENDERS",
				 "GD"=>"GIFT CARD TENDERS",
				 "TC"=>"GIFT CERT TENDERS",
				 "MI"=>"STORE CHARGE TENDERS",
				 "EF"=>"EBT CARD TENDERS",
				 "CP"=>"COUPONS TENDERED",
				 "IC"=>"INSTORE COUPONS TENDERED",
				 "ST"=>"STAMP BOOKS SOLD",
				 "BP"=>"BUS PASSES SOLD",
				 "AR"=>"AR PAYMENTS",
				 "EQ"=>"EQUITY SALES"
			 );

	$db_a = Database::mDataConnect();

	$blank = "             ";
	$fieldNames = "  ".substr("Time".$blank, 0, 13)
			.substr("Lane".$blank, 0, 9)
			.substr("Trans #".$blank, 0, 12)
			.substr("Change".$blank, 0, 14)
			.substr("Amount".$blank, 0, 14)."\n";
	$ref = ReceiptLib::centerString(trim($CORE_LOCAL->get("CashierNo"))." ".trim($CORE_LOCAL->get("cashier"))." ".build_time(time()))."\n\n";
	$receipt = "";

	foreach(array_keys($DESIRED_TENDERS) as $tender_code){
		$query = "select tdate from TenderTapeGeneric where emp_no=".$CORE_LOCAL->get("CashierNo").
			" and trans_subtype = '".$tender_code."' order by tdate";
		$result = $db_a->query($query);
		$num_rows = $db_a->num_rows($result);
		if ($num_rows <= 0) continue;

		//$receipt .= chr(27).chr(33).chr(5);

		$titleStr = "";
		for ($i = 0; $i < strlen($DESIRED_TENDERS[$tender_code]); $i++)
			$titleStr .= $DESIRED_TENDERS[$tender_code][$i]." ";
		$titleStr = substr($titleStr,0,strlen($titleStr)-1);
		$receipt .= ReceiptLib::centerString($titleStr)."\n";

		$receipt .= $ref;
		$receipt .=	ReceiptLib::centerString("------------------------------------------------------");

		$query = "select tdate,register_no,trans_no,tender
		       	from TenderTapeGeneric where emp_no=".$CORE_LOCAL->get("CashierNo").
			" and trans_subtype = '".$tender_code."' order by tdate";
		$result = $db_a->query($query);
		$num_rows = $db_a->num_rows($result);

		$receipt .= $fieldNames;
		$sum = 0;

		for ($i = 0; $i < $num_rows; $i++) {

			$row = $db_a->fetch_array($result);
			$timeStamp = self::timeStamp($row["tdate"]);
			$receipt .= "  ".substr($timeStamp.$blank, 0, 13)
				.substr($row["register_no"].$blank, 0, 9)
				.substr($row["trans_no"].$blank, 0, 8)
				.substr($blank.number_format("0", 2), -10)
				.substr($blank.number_format($row["tender"], 2), -14)."\n";
			$sum += $row["tender"];
		}
		$receipt.= ReceiptLib::centerString("------------------------------------------------------");

		$receipt .= substr($blank.$blank.$blank.$blank."Total: ".$sum, -56)."\n";
		$receipt .= str_repeat("\n", 8);
		$receipt .= chr(27).chr(105);
	}

	ReceiptLib::writeLine($receipt.chr(27).chr(105));
	$db_a->close();
}

static private function timeStamp($time) {

	return strftime("%I:%M %p", strtotime($time));
}

}

?>
