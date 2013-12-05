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

	$db_a = Database::mDataConnect();
	$shiftCutoff = date('Y-m-d 00:00:00');
	$excl = " AND emp_no <> 9999 ";
	$lookup = $db_a->query("SELECT MAX(datetime) FROM dtransactions 
		WHERE DATE(datetime) = CURDATE() AND upc='ENDOFSHIFT' AND 
		register_no=".$CORE_LOCAL->get('laneno'));
	if ($db_a->num_rows($lookup) > 0){
		$row = $db_a->fetch_row($lookup);
		if ($row[0] != '') $shiftCutoff = $row[0];
	}
	TransRecord::add_log_record(array('upc'=>'ENDOFSHIFT'));

	$DESIRED_TENDERS = $CORE_LOCAL->get("TRDesiredTenders");

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
        WHERE d.emp_no = e.emp_no AND d.register_no = ". $CORE_LOCAL->get('laneno')." AND d.emp_no <> 9999 AND d.trans_type <> 'L' 
		AND d.tdate >= '".$shiftCutoff."'
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
	//     $receipt .= "\n";
	// $receipt.= ReceiptLib::centerString("------------------------------------------------------");
	//     $receipt .= "\n";
	// 
	// 
	// foreach(array_keys($DESIRED_TENDERS) as $tender_code){ 
	// 	$tendersQ = "SELECT t.TenderName, -SUM(d.total), COUNT(d.total) 
	// 		FROM dlog d, ".$CORE_LOCAL->get('pDatabase').".tenders t
	// 		WHERE d.trans_subtype = t.TenderID AND d.register_no=".$CORE_LOCAL->get('laneno').
	// 		" AND trans_subtype = $tender_code AND d.tdate >= '$shiftCutoff' AND d.emp_no <> 9999";
	// 	$tendersR = $db_a->query($tendersQ);
	// 	$tender = $db_a->fetch_row($tendersR);
	// 
	// 	$receipt .= "  ".substr($tender[0]." Total: ".$blank.$blank,0,20);
	// 	$receipt .= substr($blank.number_format(($tender[1]),2),-8).substr($blank.$tender[2],-8)."\n";
	// }
	//     $receipt .= "\n";
	// $receipt.= ReceiptLib::centerString("------------------------------------------------------");
	//     $receipt .= "\n";

    $receipt .=	trTotal('CA','CASH');
    $receipt .=	trTotal('CK','CHECK');
    $receipt .=	trTotal(array('CP','MC'),'VENDOR COUPON');
    $receipt .=	trTotal('CC','CREDIT CARD');
    $receipt .=	trTotal('DC','DEBIT CARD');
    $receipt .=	trTotal('FS','SNAP - FOOD');
    $receipt .=	trTotal('EC','SNAP - CASH');
    $receipt .=	trTotal('GC','GIFT CARD');
    $receipt .=	trTotal('MI','INSTORE CHARGE');
    $receipt .=	trTotal('IC','INSTORE COUPON');
    $receipt .=	trTotal('PT','PATRONAGE');
    $receipt .= "\n";
    $receipt .=	trTotal(array('CA','CK'),'CASH + CHECK');
    $receipt .=	trTotal(array('DC','CC','FS','EC'),'DEB/CRED/SNAP');
	$receipt .= "House Charge?\n";
	$receipt .= "House Coupon?\n";
    $receipt .=	trTotal(45,'RCVD. on ACCT.');
    $receipt .=	trTotal(37,'FRMRS MARKET SNAP');

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

		$query = "select tdate,register_no,emp_no,trans_no,card_no,total 
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
				.substr($blank.$row["card_no"],-6)
				.substr($blank.number_format($row["total"], 2), -14)."\n";
			}
			$sum += $row["total"];
		}
		
		$receipt.= ReceiptLib::centerString("------------------------------------------------------");

		$receipt .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";
		$receipt .= str_repeat("\n", 3);
//		$receipt .= chr(27).chr(105);
	}

	// $titleStr = "M E M B E R   E Q U I T Y";
	// $receipt .= ReceiptLib::centerString($titleStr)."\n";
	// 
	// $receipt .=	ReceiptLib::centerString("------------------------------------------------------");
	// 
	// $query = "select tdate,register_no,emp_no,trans_no,total
	//        	from dlog where register_no=".$CORE_LOCAL->get('laneno').
	// 		" and department = 36 AND tdate >= '$shiftCutoff'$excl order by tdate";
	// $result = $db_a->query($query);
	// $num_rows = $db_a->num_rows($result);
	// 
	// $itemize = 1;
	// if ($itemize == 1) $receipt .= $fieldNames;
	// $sum = 0;
	// 
	// for ($i = 0; $i < $num_rows; $i++) {
	// 	$row = $db_a->fetch_array($result);
	// 	$timeStamp = self::timeStamp($row["tdate"]);
	// 	if ($itemize == 1) {
	// 		$receipt .= "  ".substr($timeStamp.$blank, 0, 13)
	// 		.substr($row["register_no"].$blank, 0, 9)
	// 		.substr($row["trans_no"].$blank, 0, 8)
	// 		.substr($blank.$row['emp_no'], -10)
	// 		.substr($blank.number_format($row["total"], 2), -14)."\n";
	// 	}
	// 	$sum += $row["total"];
	// }
	// 
	// $receipt.= ReceiptLib::centerString("------------------------------------------------------");
	// 
	// $receipt .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";
	// 
	// $receipt .= str_repeat("\n", 3);
	$receipt .= trTotal(36,'MEMBER EQUITY', True);
	
	// $titleStr = "R C V D  /  A C C O U N T";
	// $receipt .= ReceiptLib::centerString($titleStr)."\n";
	// 
	// $receipt .=	ReceiptLib::centerString("------------------------------------------------------");
	// 
	// $query = "select tdate,register_no,emp_no,trans_no,total
	//        	from dlog where register_no=".$CORE_LOCAL->get('laneno').
	// 		" and  department = 45 AND tdate >= '$shiftCutoff'$excl order by tdate";
	// $result = $db_a->query($query);
	// $num_rows = $db_a->num_rows($result);
	// $itemize = 1;	
	// if ($itemize == 1) $receipt .= $fieldNames;
	// $sum = 0;
	// 
	// for ($i = 0; $i < $num_rows; $i++) {
	// 	$row = $db_a->fetch_array($result);
	// 	$timeStamp = self::timeStamp($row["tdate"]);
	// 	if ($itemize == 1) {
	// 		$receipt .= "  ".substr($timeStamp.$blank, 0, 13)
	// 		.substr($row["register_no"].$blank, 0, 9)
	// 		.substr($row["trans_no"].$blank, 0, 8)
	// 		.substr($blank.$row['emp_no'], -10)
	// 		.substr($blank.number_format($row["total"], 2), -14)."\n";
	// 	}
	// 	$sum += $row["total"];
	// }
	// 
	// $receipt.= ReceiptLib::centerString("------------------------------------------------------");
	// 
	// $receipt .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";
	// 
	// $receipt .= str_repeat("\n", 3);
	$receipt .= trTotal(45,'RCVD / ACCOUNT', True);
	
	// $titleStr = "G I F T   C A R D   S O L D";
	// $receipt .= ReceiptLib::centerString($titleStr)."\n";
	// 
	// $receipt .=	ReceiptLib::centerString("------------------------------------------------------");
	// 
	// $query = "select tdate,register_no,emp_no,trans_no,total
	//        	from dlog where register_no=".$CORE_LOCAL->get('laneno').
	// 		" and department = 41 AND tdate >= '$shiftCutoff'$excl order by tdate";
	// $result = $db_a->query($query);
	// $num_rows = $db_a->num_rows($result);
	// $itemize = 1;
	// if ($itemize == 1) $receipt .= $fieldNames;
	// $sum = 0;
	// 
	// for ($i = 0; $i < $num_rows; $i++) {
	// 	$row = $db_a->fetch_array($result);
	// 	$timeStamp = self::timeStamp($row["tdate"]);
	// 	if ($itemize == 1) {
	// 		$receipt .= "  ".substr($timeStamp.$blank, 0, 13)
	// 		.substr($row["register_no"].$blank, 0, 9)
	// 		.substr($row["trans_no"].$blank, 0, 8)
	// 		.substr($blank.$row['emp_no'], -10)
	// 		.substr($blank.number_format($row["total"], 2), -14)."\n";
	// 	}
	// 	$sum += $row["total"];
	// }
	// 
	// $receipt.= ReceiptLib::centerString("------------------------------------------------------");
	// 
	// $receipt .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";
	$receipt .= trTotal(41,'GIFT CARDS SOLD', True);
	
	$receipt .= str_repeat("\n", 5);

	return $receipt.chr(27).chr(105);
}

}

// function trTotal($k, $label) {
// 	global $CORE_LOCAL;
// 	$db_a = Database::mDataConnect();
// 
// 	$blank = "             ";
// 	$shiftCutoff = date('Y-m-d 00:00:00');
// 	$lookup = $db_a->query("SELECT MAX(datetime) FROM dtransactions 
// 		WHERE DATE(datetime) = CURDATE() AND upc='ENDOFSHIFT' AND 
// 		register_no=".$CORE_LOCAL->get('laneno'));
// 	if ($db_a->num_rows($lookup) > 0){
// 		$row = $db_a->fetch_row($lookup);
// 		if ($row[0] != '') $shiftCutoff = $row[0];
// 	}
// 
// 	if (is_array($k)) $k = "'" . implode("','", $k) . "'";
//     elseif (!is_numeric($k)) $k = "'".$k."'";
// 	$q = (!is_numeric($k)) ? 'trans_subtype' : 'department';
// 	
//     $tenderQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
//     	WHERE register_no=".$CORE_LOCAL->get('laneno').
// 		" AND $q IN($k) AND tdate >= '$shiftCutoff' AND emp_no <> 9999";
// 	
// 	$tenderR = $db_a->query($tenderQ);
// 	$tender = $db_a->fetch_row($tenderR);
// 
// 	return "  ".substr($label.$blank.$blank,0,20).substr($blank.number_format(($tender[0]),2),-8).substr($blank.$tender[1],-8)."\n";
// 	
// }


function trTotal($k, $label,$i=False) {
	global $CORE_LOCAL;
	$db_a = Database::mDataConnect();

	$blank = "             ";
	$shiftCutoff = date('Y-m-d 00:00:00');
	$lookup = $db_a->query("SELECT MAX(datetime) FROM dtransactions 
		WHERE DATE(datetime) = CURDATE() AND upc='ENDOFSHIFT' AND 
		register_no=".$CORE_LOCAL->get('laneno'));
	if ($db_a->num_rows($lookup) > 0){
		$row = $db_a->fetch_row($lookup);
		if ($row[0] != '') $shiftCutoff = $row[0];
	}

	if (is_array($k)) $k = "'" . implode("','", $k) . "'";
    elseif (!is_numeric($k)) $k = "'".$k."'";
	$q = (!is_numeric($k)) ? 'trans_subtype' : 'department';
	
	if($i===False) {
	    $tenderQ = "SELECT -SUM(total) AS net, COUNT(total) FROM dlog 
	    	WHERE register_no=".$CORE_LOCAL->get('laneno').
			" AND $q IN($k) AND tdate >= '$shiftCutoff' AND emp_no <> 9999";
	} else {
		$tenderQ = "SELECT tdate,register_no,emp_no,trans_no,card_no,total FROM dlog 
			WHERE register_no=".$CORE_LOCAL->get('laneno').
			" and $q IN($k) AND tdate >= '$shiftCutoff' AND emp_no <> 9999 order by tdate";
		
	}
	$tenderR = $db_a->query($tenderQ);
	$tender = $db_a->fetch_array($tenderR);
	$num_rows = $db_a->num_rows($tenderR);

	if($i===False) {
		$ret = "  ".substr($label.$blank.$blank,0,20).substr($blank.number_format(($tender[0]),2),-8).substr($blank.$tender[1],-8)."\n";
	} else {
		$sum = 0;
		$titleStr = chunk_split($label,1);
		$ret = ReceiptLib::centerString($titleStr)."\n";
		$ret .=	ReceiptLib::centerString("------------------------------------------------------");
		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db_a->fetch_array($result);
			$timeStamp = self::timeStamp($row["tdate"]);
			$ret .= "  ".substr($timeStamp.$blank, 0, 13)
				.substr($row["register_no"].$blank, 0, 9)
				.substr($row["trans_no"].$blank, 0, 8)
				.substr($blank.$row['emp_no'], -8)
				.substr($blank.$row["card_no"],-6)
				.substr($blank.number_format($row["total"], 2), -14)."\n";
			$sum += $row["total"];
		}
		$ret .= ReceiptLib::centerString("------------------------------------------------------");
		$ret .= substr($blank.$blank.$blank."Count: ".$num_rows."  Total: ".number_format($sum,2), -56)."\n";

		$ret .= str_repeat("\n", 3);
	}	
	
	return $ret;
}

?>
