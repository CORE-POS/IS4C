<?php

include('../../../config.php');

require('additem.php');
require($FANNIE_ROOT.'auth/login.php');

if (!validateUserQuiet('backvoids')){
	header("Location: {$FANNIE_URL}/auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/adjust/");
	return;
}

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");

include('../../db.php');
require($FANNIE_ROOT.'src/select_dlog.php');

$CARDNO = 0;
$MEMTYPE = 0;
$ISSTAFF = 0;
$TRANSNO = 0;
$DATESTR = "";
$TRANS_ID = 0;
$TODAY_FLAG = 0;

if (isset($_GET['action'])){

	$out = $_GET['action']."`";
	switch($_GET['action']){
	case 'loadReceipt':
		$date = $_GET['date'];
		$trans_num = $_GET['trans_num'];
		$out .= loadReceipt($date,$trans_num);
		break;
	case 'refund':
		$receipt_date = $_GET["date"];
		$trans_num = $_GET["trans_num"];
		$target_date = $_GET["newdate"];

		list($trans,$date) = explode(" ",void_receipt($target_date,$receipt_date,$trans_num));
		$out .= loadReceipt($date,$trans)."`Void receipt is $date $trans";
		break;
	}
	echo $out;
	return;
}

function loadReceipt($date,$trans_num){
	global $sql;
	$dlog = select_dlog($date);
	if (substr($trans_num,0,8) == "1001-30-"){
		if ($dlog=="dlog_15")
			$dlog = "dlog_90_view";
	}
	$query = "select d.upc,d.trans_type,d.trans_subtype,d.trans_status,
		  d.total,d.card_no,p.description from $dlog as d left join
		  products as p on d.upc = p.upc 
		  where datediff(dd,tdate,'$date') = 0 and trans_num='$trans_num'
		  order by d.trans_id";
	$result = $sql->query($query);
	
	if ($sql->num_rows($result) == 0)
		return "Error: Transaction $trans_num not found on date $date";

	$ret = "<table cellspacing=0 cellpadding=3 border=1><tr>";
	$ret .= "<th>Type</th><th>Status</th><th>UPC</th><th>Description</th><th>Total</th>";
	$ret .= "<tr>";
	$cardno = "";
	while ($row = $sql->fetch_array($result)){
		$cardno = $row['card_no'];
		$ret .= "<tr>";
		$ret .= "<td>";
		switch($row['trans_type']){
		case 'I':
			$ret .= "Item"; break;
		case 'T':
			$ret .= "Tender"; break;
		case 'S':
			$ret .= "Discount"; break;
		case 'A':
			$ret .= "Tax"; break;
		default:
			$ret .= $row['trans_type']; break;
		}
		$ret .= "</td>";
		if (($row['trans_status'] == '0' || $row['trans_status'] == '') &&
		    ($row['trans_subtype'] == '0' || $row['trans_subtype'] == ''))
			$ret .= "<td></td>";
		else if ($row['trans_type'] == 'T'){
			$ret .=  "<td>";
			switch ($row['trans_subtype']){
			case 'CA':
				if ($row['total'] < 0)
					$ret .= "Cash";
				else
					$ret .= "Change";
				break;
			case 'CC':
				$ret .= "Credit Card"; break;
			case 'CK':
				$ret .= "Check"; break;
			case 'MI':
				$ret .= "Store Charge"; break;
			case 'GD':
				$ret .= "Gift Card"; break;
			case 'TC':
				$ret .= "Gift Certificate"; break;
			case 'CP':
				$ret .= "Coupon"; break;
			default:
				$ret .= $row['trans_subtype'];
			}
			$ret .= "</td>";
		}
		$ret .= "<td>".$row[0]."</td>";
		$ret .= "<td>".$row['description']."</td>";
		$ret .= "<td>".$row['total']."</td>";
		$ret .= "</tr>";
	}
	$ret .= "</table><br />";
	$ret .= "<b>Date</b>: ".$date." <b>Trans #</b>: ".$trans_num."<br />";
	$ret .= "<b>Member number</b>: ".$cardno."<br /><br />";

	$ret .= "<a href=\"\" onclick=\"refund('$date','$trans_num'); return false;\">";
	$ret .= "Void this receipt</a><br />";
	//$ret .= "<a href=\"\" onclick=\"memChange('$date','$tran_num'); return false;\">";
	//$ret .= "Move to another member</a>";
	//$ret .= " (new member number <input type=text size=5 id=newmem />)<br />";
	$ret .= "Date to file changes: <input type=text size=10 id=newdate />";

	return $ret;	
}

function void_receipt($target_date,$receipt_date,$trans_num,$card_no=0){
	global $CARDNO,$MEMTYPE,$ISSTAFF,$TRANSNO,$DATESTR,$TRANS_ID,$TODAY_FLAG,$sql;

	$emp_no = 1001;
	$register_no = 30;

	$TODAY_FLAG = 0;
	$todayQ = "select datediff(dd,'$target_date',getdate())";
	$todayR = $sql->query($todayQ);
	if (array_pop($sql->fetch_array($todayR)) == 0){
		$TODAY_FLAG = 1;
	}

	$dtrans = "transarchive";
	$receiptTodayQ = "select datediff(dd,'$receipt_date',getdate())";
	$receiptTodayR = $sql->query($receiptTodayQ);
	if (array_pop($sql->fetch_array($receiptTodayR)) == 0){
		$dtrans = "dtransactions";
	}

	$newTransNumQ = "select max(trans_no) from $dtrans where datediff(dd,'$target_date',datetime) = 0
			 and emp_no = $emp_no and register_no = $register_no";
	$newTransNumR = $sql->query($newTransNumQ);
	$TRANSNO = 0;
	if ($sql->num_rows($newTransNumR) > 0)
		$TRANSNO = array_pop($sql->fetch_array($newTransNumR));
	$TRANSNO++;
	$TRANS_ID = 0;

	$CARDNO = $card_no;
	$DATESTR = $target_date.' 00:00:00';
	
	$ret = "";
	list($old_emp,$old_reg,$old_trans) = explode("-",$trans_num);
	$query = "select upc, description, trans_type, trans_subtype,
		trans_status, department, quantity, Scale, unitPrice,
		total, regPrice, tax, foodstamp, discount, memDiscount,
		discountable, discounttype, voided, PercentDiscount,
		ItemQtty, volDiscType, volume, volSpecial, mixMatch,
		matched, memType, isStaff, card_no, trans_id 
		from $dtrans where register_no = $old_reg
		and emp_no = $old_emp and trans_no = $old_trans
		and datediff(dd,datetime,'$receipt_date') = 0
		and trans_status <> 'X'
		order by trans_id";
	$result = $sql->query($query);
	addcomment("VOIDING TRANSACTION $trans_num");
	$total = 0;
	while ($row = $sql->fetch_array($result)){
		if ($CARDNO == 0)
			$CARDNO = $row["card_no"];	
		$MEMTYPE = $row["memType"];
		$ISSTAFF = $row["isStaff"];
		
		if ($row["upc"] == "TAX"){
			addtax(-1*$row["total"]);
			$total += -1*$row["total"];
		}
		elseif ($row["upc"] == "DISCOUNT"){
			addtransDiscount(-1*$row["total"]);
			$total += -1*$row["total"];
		}
		elseif ($row["trans_type"] == "T"){
			if ($row["description"] == "Change"){
				addchange(-1*$row["total"]);
				$total += -1*$row["total"];
			}
			elseif ($row["description"] == "FS Change"){
				addfsones(-1*$row["total"]);
				$total += -1*$row["total"];
			}
			else{
				addtender($row["description"],$row["trans_subtype"],-1*$row["total"]);
				$total += -1*$row["total"];
			}
		}
		elseif (strstr($row["description"],"** YOU SAVED")){
			$temp = explode("$",$row["description"]);
			adddiscount(substr($temp[1],0,-3),$row["department"]);
		}
		elseif (strstr($row["description"],"% Discount Applied")){
			$temp = explode("%",$row["description"]);	
			discountnotify(substr($temp[0],3));
		}
		elseif ($row["description"] == "** Order is Tax Exempt **")
			addTaxExempt();
		elseif ($row["description"] == "** Tax Excemption Reversed **")
			reverseTaxExempt();
		elseif ($row["description"] == " * Manufacturers Coupon")
			addCoupon($row["upc"],$row["department"],-1*$row["total"]);
		elseif (strstr($row["description"],"** Tare Weight")){
			$temp = explode(" ",$row["description"]);
			addTare($temp[3]*100);
		}
		elseif ($row["upc"] == "MAD Coupon")
			addMadCoup();
		elseif ($row["upc"] != "0" &&
			(is_numeric($row["upc"]) || strstr($row["upc"],"DP"))) {
			$row["trans_status"] = "V";
			$row["total"] *= -1;
			$row["discount"] *= -1;
			$row["memDiscount"] *= -1;
			$row["quantity"] *= -1;
			$row["ItemQtty"] *= -1;
			addItem($row["upc"],$row["description"],$row["trans_type"],$row["trans_subtype"],
				$row["trans_status"],$row["department"],$row["quantity"],
				$row["unitPrice"],$row["total"],$row["regPrice"],
				$row["Scale"],$row["tax"],$row["foodstamp"],$row["discount"],
				$row["memDiscount"],$row["discountable"],$row["discounttype"],
				$row["ItemQtty"],$row["volDiscType"],$row["volume"],$row["volSpecial"],
				$row["mixMatch"],$row["matched"],$row["voided"]);
			$total += $row["total"];
		}
		
	}
	if ($TODAY_FLAG == 0){
		$voidHistQ = "insert voidTransHistory values ('$DATESTR','VOIDING TRANSACTION $trans_num',
			     '$emp_no-$register_no-$TRANSNO',$total)";	
		$voidHistR = $sql->query($voidHistQ);
	}

	return "1001-30-".$TRANSNO." ".$target_date;
}

?>

<html>
<head><title>Transaction adjustment tool</title>
<script type=text/javascript src=index.js></script>
<style type=text/css>
a {
	color: blue;
}
</style>
</head>
<form onsubmit="loadReceipt(); return false;">
<b>Date</b> <input type=text id=rdate /><br />
<b>Trans #</b> <input type=text id=rtrans_num /><br />
<input type=submit value=Submit />
</form>

<div id=contentarea>

</div>
