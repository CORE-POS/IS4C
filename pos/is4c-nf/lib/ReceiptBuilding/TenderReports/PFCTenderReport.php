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

/**
  @class PFCTenderReport
  Custom PFC tender report
*/
class PFCTenderReport extends TenderReport {

static public function get(){
	global $CORE_LOCAL;

	$db = Database::mDataConnect();
	$shiftCutoff = date('Y-m-d 00:00:00');
	$excl = " AND emp_no <> 9999 ";
	$lookup = $db->query("SELECT MAX(datetime) FROM dtransactions 
		WHERE DATE(datetime) = CURDATE() AND upc='ENDOFSHIFT' AND 
		register_no=".$CORE_LOCAL->get('laneno'));
	if ($db->num_rows($lookup) > 0){
		$row = $db->fetch_row($lookup);
		if ($row[0] != '') $shiftCutoff = $row[0];
	}
	TransRecord::add_log_record(array('upc'=>'ENDOFSHIFT'));

	$DESIRED_TENDERS = $CORE_LOCAL->get("TRDesiredTenders");

	$db_a = Database::mDataConnect();
	$receipt = "";
	$blank = "             ";
	$fieldNames = "  ".substr("Time".$blank, 0, 13)
			.substr("Lane".$blank, 0, 9)
			.substr("Trans #".$blank, 0, 12)
			.substr("Emp #".$blank, 0, 14)
			.substr("Amount".$blank, 0, 14)."\n";
	$ref = ReceiptLib::centerString(trim($CORE_LOCAL->get("CashierNo"))." ".trim($CORE_LOCAL->get("cashier"))." ".ReceiptLib::build_time(time()))."\n";

	$cashier_names = "";
    $cashierQ = "SELECT CONCAT(SUBSTR(e.FirstName,1,1),SUBSTR(e.Lastname,1,1)) as cashier
        FROM dlog d, ".$CORE_LOCAL->get('pDatabase').".employees e
        WHERE d.emp_no = e.emp_no AND register_no = ". $CORE_LOCAL->get('laneno')."
		AND d.emp_no <> 9999
        GROUP BY d.emp_no ORDER BY d.tdate";

    $cashierR = $db_a->query($cashierQ);

    for ($i = 0; $i < $row = $db_a->fetch_array($cashierR); $i++) {
            $cashier_names .= $row['cashier'].", ";
    }

	$receipt .= ReceiptLib::centerString("T E N D E R   R E P O R T")."\n";
	$receipt .= $ref;
	$receipt .= ReceiptLib::centerString("Cashiers: " . $cashier_names)."\n\n";

	// NET TOTAL
	$netQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
		WHERE register_no=".$CORE_LOCAL->get('laneno').
		" AND trans_subtype IN('CA','CK','DC','CC','FS','EC') AND tdate >= '$shiftCutoff'$excl";
	$netR = $db_a->query($netQ);
	$net = $db_a->fetch_row($netR);
    $receipt .= "  ".substr("NET Total: ".$blank.$blank,0,20);
    $receipt .= substr($blank.number_format(($net[0]),2),-8)."\n";
    $receipt .= "\n";
	$receipt.= ReceiptLib::centerString("------------------------------------------------------");
    $receipt .= "\n";


	foreach(array_keys($DESIRED_TENDERS) as $tender_code){ 
		$tendersQ = "SELECT t.TenderName, -SUM(d.total), COUNT(d.total) 
			FROM dlog d, ".$CORE_LOCAL->get('pDatabase').".tenders t
			WHERE d.trans_subtype = t.TenderID AND d.register_no=".$CORE_LOCAL->get('laneno').
			" AND trans_subtype = $tender_code AND d.tdate >= '$shiftCutoff' AND d.emp_no <> 9999";
		$tendersR = $db_a->query($tendersQ);
		$tender = $db_a->fetch_row($tendersR);

		$receipt .= "  ".substr($tender[0]." Total: ".$blank.$blank,0,20);
		$receipt .= substr($blank.number_format(($tender[1]),2),-8).substr($blank.$tender[2],-8)."\n";
	}
    $receipt .= "\n";
	$receipt.= ReceiptLib::centerString("------------------------------------------------------");
    $receipt .= "\n";

	// // CASH TOTAL
	//     $caQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN('CA') AND tdate >= '$shiftCutoff'$excl";
	// $caR = $db_a->query($caQ);
	// $ca = $db_a->fetch_row($caR);
	// $receipt .= "  ".substr("CASH Total: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($ca[0]),2),-8).substr($blank.$ca[1],-8)."\n";
	// // CHECK TOTAL
	//     $ckQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN('CK') AND tdate >= '$shiftCutoff'$excl";
	// $ckR = $db_a->query($ckQ);
	// $ck = $db_a->fetch_row($ckR);
	// $receipt .= "  ".substr("CHECK Total: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($ck[0]),2),-8).substr($blank.$ck[1],-8)."\n";
	// CARD TENDERS TOTAL
    $cardQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
    	WHERE register_no=".$CORE_LOCAL->get('laneno').
		" AND trans_subtype IN('DC','CC','FS','EC') AND tdate >= '$shiftCutoff'$excl";
	$cardR = $db_a->query($cardQ);
	$card = $db_a->fetch_row($cardR);
	$receipt .= "  ".substr("DC / CC / EBT Total: ".$blank.$blank,0,20);
	$receipt .= substr($blank.number_format(($card[0]),2),-8).substr($blank.$card[1],-8)."\n";
	$receipt.= ReceiptLib::centerString("------------------------------------------------------");
    $receipt .= "\n";
    // EQUITY TOTAL
    $eqQ = "SELECT SUM(total), COUNT(total) from dlog where register_no=".$CORE_LOCAL->get('laneno').
			" and department = 45 AND tdate >= '$shiftCutoff'$excl";
	$eqR = $db_a->query($eqQ);
	$eq = $db_a->fetch_row($eqR);
	$receipt .= "  ".substr("Member Equity: ".$blank.$blank,0,20);
	$receipt .= substr($blank.number_format(($eq[0]),2),-8).substr($blank.$eq[1],-8)."\n";
    // GIFT SOLD TOTAL
    $gsQ = "SELECT SUM(total), COUNT(total) from dlog where register_no=".$CORE_LOCAL->get('laneno').
			" and department = 44 AND tdate >= '$shiftCutoff'$excl";
	$gsR = $db_a->query($gsQ);
	$gs = $db_a->fetch_row($gsR);
	$receipt .= "  ".substr("Gift Sold: ".$blank.$blank,0,20);
	$receipt .= substr($blank.number_format(($gs[0]),2),-8).substr($blank.$gs[1],-8)."\n";
	//     // GIFT TENDER TOTAL
	//     $gtQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype = 'TC' AND tdate >= '$shiftCutoff'$excl";
	// $gtR = $db_a->query($gtQ);
	// $gt = $db_a->fetch_row($gtR);
	// $receipt .= "  ".substr("Gift Tender: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($gt[0]),2),-8).substr($blank.$gt[1],-8)."\n";
	//     // COUPON - VENDOR TOTAL
	//     $mcQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN ('CP','MC') AND tdate >= '$shiftCutoff'$excl";
	// $mcR = $db_a->query($mcQ);
	// $mc = $db_a->fetch_row($mcR);
	// $receipt .= "  ".substr("Coupons - Vendor: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($mc[0]),2),-8).substr($blank.$mc[1],-8)."\n";
	//     // COUPON - INSTORE TOTAL
	//     $icQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN ('IC') AND tdate >= '$shiftCutoff'$excl";
	// $icR = $db_a->query($icQ);
	// $ic = $db_a->fetch_row($icR);
	// $receipt .= "  ".substr("Coupons - Instore: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($ic[0]),2),-8).substr($blank.$ic[1],-8)."\n";
	//     // COUPON - INSTORE TOTAL
	//     $ptQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN ('PT') AND tdate >= '$shiftCutoff'$excl";
	// $ptR = $db_a->query($ptQ);
	// $pt = $db_a->fetch_row($ptR);
	// $receipt .= "  ".substr("Patronage: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($pt[0]),2),-8).substr($blank.$pt[1],-8)."\n";
	// $receipt.= ReceiptLib::centerString("------------------------------------------------------");
	//     $receipt .= "\n";
	//     // INSTORE CHARGE TOTAL
	//     $miQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN ('MI') AND tdate >= '$shiftCutoff'$excl";
	// $miR = $db_a->query($miQ);
	// $mi = $db_a->fetch_row($miR);
	// $receipt .= "  ".substr("Instore Charges: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($mi[0]),2),-8).substr($blank.$mi[1],-8)."\n";
    // R/A TOTAL
    $raQ = "SELECT SUM(total), COUNT(total) from dlog where register_no=".$CORE_LOCAL->get('laneno').
			" and department = 49 AND tdate >= '$shiftCutoff'$excl";
	$raR = $db_a->query($raQ);
	$ra = $db_a->fetch_row($raR);
	$receipt .= "  ".substr("R/A: ".$blank.$blank,0,20);
	$receipt .= substr($blank.number_format(($ra[0]),2),-8).substr($blank.$ra[1],-8)."\n";
	//     // CREDIT CARD TOTAL
	//     $ccQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN ('CC') AND tdate >= '$shiftCutoff'$excl";
	// $ccR = $db_a->query($ccQ);
	// $cc = $db_a->fetch_row($ccR);
	// $receipt .= "  ".substr("Credit Card: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($cc[0]),2),-8).substr($blank.$cc[1],-8)."\n";
	//     // DEBIT CARD TOTAL
	//     $dcQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN ('DC') AND tdate >= '$shiftCutoff'$excl";
	// $dcR = $db_a->query($dcQ);
	// $dc = $db_a->fetch_row($dcR);
	// $receipt .= "  ".substr("Debit Card: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($dc[0]),2),-8).substr($blank.$dc[1],-8)."\n";
	//     // EBT FOOD TOTAL
	//     $fsQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN ('FS') AND tdate >= '$shiftCutoff'$excl";
	// $fsR = $db_a->query($fsQ);
	// $fs = $db_a->fetch_row($fsR);
	// $receipt .= "  ".substr("EBT Food: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($fs[0]),2),-8).substr($blank.$fs[1],-8)."\n";
	//     // EBT CASH TOTAL
	//     $ecQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
	// 	" AND trans_subtype IN ('EC') AND tdate >= '$shiftCutoff'$excl";
	// $ecR = $db_a->query($ecQ);
	// $ec = $db_a->fetch_row($ecR);
	// $receipt .= "  ".substr("EBT Cash: ".$blank.$blank,0,20);
	// $receipt .= substr($blank.number_format(($ec[0]),2),-8).substr($blank.$ec[1],-8)."\n";
	$receipt.= ReceiptLib::centerString("------------------------------------------------------");

	$receipt .= str_repeat("\n", 5);

	foreach(array_keys($DESIRED_TENDERS) as $tender_code){ 
		$query = "select tdate from dlog 
			where register_no=".$CORE_LOCAL->get('laneno').
			" and trans_subtype = '".$tender_code."'
			 AND tdate >= '$shiftCutoff'$excl order by tdate";
		$result = $db_a->query($query);
		$num_rows = $db_a->num_rows($result);
		if ($num_rows <= 0) continue;

		//$receipt .= chr(27).chr(33).chr(5);

		$titleStr = "";
		$itemize = 1;
		for ($i = 0; $i < strlen($DESIRED_TENDERS[$tender_code]); $i++)
			$titleStr .= $DESIRED_TENDERS[$tender_code][$i]." ";
		$titleStr = substr($titleStr,0,strlen($titleStr)-1);
		$receipt .= ReceiptLib::centerString($titleStr)."\n";

		if ($itemize == 1) $receipt .=	ReceiptLib::centerString("------------------------------------------------------");

		$query = "select tdate,register_no,emp_no,trans_no,total 
			from dlog where register_no=".$CORE_LOCAL->get('laneno').
			" and trans_subtype = '".$tender_code."' and (total <> 0 OR total <> -0) 
			 AND tdate >= '$shiftCutoff'$excl order by tdate";
		$result = $db_a->query($query);
		$num_rows = $db_a->num_rows($result);
		
		if ($itemize == 1) $receipt .= $fieldNames;
		$sum = 0;

		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db_a->fetch_array($result);
			$timeStamp = self::timeStamp($row["tdate"]);
			if ($itemize == 1 && $row["total"]) {
				$receipt .= "  ".substr($timeStamp.$blank, 0, 13)
				.substr($row["register_no"].$blank, 0, 9)
				.substr($row["trans_no"].$blank, 0, 8)
				.substr($blank.$row['emp_no'], -10)
				.substr($blank.number_format($row["total"], 2), -14)."\n";
			}
			$sum += $row["total"];
		}
		
		$receipt.= ReceiptLib::centerString("------------------------------------------------------");

		$receipt .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";
		$receipt .= str_repeat("\n", 3);
//		$receipt .= chr(27).chr(105);
	}

	$titleStr = "M E M B E R   E Q U I T Y";
	$receipt .= ReceiptLib::centerString($titleStr)."\n";

	$receipt .=	ReceiptLib::centerString("------------------------------------------------------");

	$query = "select tdate,register_no,emp_no,trans_no,total
	       	from dlog where register_no=".$CORE_LOCAL->get('laneno').
			" and department = 45 AND tdate >= '$shiftCutoff'$excl order by tdate";
	$result = $db_a->query($query);
	$num_rows = $db_a->num_rows($result);
	
	$itemize = 1;
	if ($itemize == 1) $receipt .= $fieldNames;
	$sum = 0;

	for ($i = 0; $i < $num_rows; $i++) {
		$row = $db_a->fetch_array($result);
		$timeStamp = self::timeStamp($row["tdate"]);
		if ($itemize == 1) {
			$receipt .= "  ".substr($timeStamp.$blank, 0, 13)
			.substr($row["register_no"].$blank, 0, 9)
			.substr($row["trans_no"].$blank, 0, 8)
			.substr($blank.$row['emp_no'], -10)
			.substr($blank.number_format($row["total"], 2), -14)."\n";
		}
		$sum += $row["total"];
	}
	
	$receipt.= ReceiptLib::centerString("------------------------------------------------------");

	$receipt .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";

	$receipt .= str_repeat("\n", 3);

	$titleStr = "R C V D  /  A C C O U N T";
	$receipt .= ReceiptLib::centerString($titleStr)."\n";

	$receipt .=	ReceiptLib::centerString("------------------------------------------------------");

	$query = "select tdate,register_no,emp_no,trans_no,total
	       	from dlog where register_no=".$CORE_LOCAL->get('laneno').
			" and  department = 49 AND tdate >= '$shiftCutoff'$excl order by tdate";
	$result = $db_a->query($query);
	$num_rows = $db_a->num_rows($result);
	$itemize = 1;	
	if ($itemize == 1) $receipt .= $fieldNames;
	$sum = 0;

	for ($i = 0; $i < $num_rows; $i++) {
		$row = $db_a->fetch_array($result);
		$timeStamp = self::timeStamp($row["tdate"]);
		if ($itemize == 1) {
			$receipt .= "  ".substr($timeStamp.$blank, 0, 13)
			.substr($row["register_no"].$blank, 0, 9)
			.substr($row["trans_no"].$blank, 0, 8)
			.substr($blank.$row['emp_no'], -10)
			.substr($blank.number_format($row["total"], 2), -14)."\n";
		}
		$sum += $row["total"];
	}
	
	$receipt.= ReceiptLib::centerString("------------------------------------------------------");

	$receipt .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";

	$receipt .= str_repeat("\n", 3);

	$titleStr = "G I F T   C A R D   S O L D";
	$receipt .= ReceiptLib::centerString($titleStr)."\n";

	$receipt .=	ReceiptLib::centerString("------------------------------------------------------");

	$query = "select tdate,register_no,emp_no,trans_no,total
	       	from dlog where register_no=".$CORE_LOCAL->get('laneno').
			" and department = 44 AND tdate >= '$shiftCutoff'$excl order by tdate";
	$result = $db_a->query($query);
	$num_rows = $db_a->num_rows($result);
	$itemize = 1;
	if ($itemize == 1) $receipt .= $fieldNames;
	$sum = 0;

	for ($i = 0; $i < $num_rows; $i++) {
		$row = $db_a->fetch_array($result);
		$timeStamp = self::timeStamp($row["tdate"]);
		if ($itemize == 1) {
			$receipt .= "  ".substr($timeStamp.$blank, 0, 13)
			.substr($row["register_no"].$blank, 0, 9)
			.substr($row["trans_no"].$blank, 0, 8)
			.substr($blank.$row['emp_no'], -10)
			.substr($blank.number_format($row["total"], 2), -14)."\n";
		}
		$sum += $row["total"];
	}
	
	$receipt.= ReceiptLib::centerString("------------------------------------------------------");

	$receipt .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";

	
	$receipt .= str_repeat("\n", 5);

	return $receipt.chr(27).chr(105);
}

}

?>
