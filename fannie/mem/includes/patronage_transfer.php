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

include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_REQUEST['submit1']) || isset($_REQUEST['submit2'])){
	$date = $_REQUEST['date'];
	$tn = $_REQUEST['trans_num'];
	$cn2 = $_REQUEST['memTo'];	

	if (!is_numeric($cn2)){
		echo "<em>Error: member given ($cn2) isn't a number</em>";
		echo "<br /><br />";
		echo "<a href=\"\" onclick=\"back(); return false;\">Back</a>";
		return;
	}

	$q = "SELECT FirstName,LastName FROM custdata WHERE CardNo=$cn2 AND personNum=1";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) == 0){
		echo "<em>Error: no such member: $cn1</em>";
		echo "<br /><br />";
		echo "<a href=\"\" onclick=\"back(); return false;\">Back</a>";
		return;
	}
	$row = $dbc->fetch_row($r);
	$name2 = $row[0].' '.$row[1];

	$dlog = select_dlog($date);
	$q = "SELECT card_no FROM $dlog WHERE trans_num=".$dbc->escape($tn)." AND "
		.$dbc->datediff($dbc->escape($date),'tdate')." = 0
		ORDER BY card_no DESC";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) == 0){
		echo "<em>Error: receipt not found: $tn</em>";
		echo "<br /><br />";
		echo "<a href=\"\" onclick=\"back(); return false;\">Back</a>";
		return;
	}
	$cn1 = array_pop($dbc->fetch_row($r));

	$q = "SELECT SUM(CASE WHEN trans_type in ('I','M','D') then total else 0 END)
		FROM $dlog WHERE trans_num=".$dbc->escape($tn)." AND "
		.$dbc->datediff($dbc->escape($date),'tdate')." = 0";
	$r = $dbc->query($q);
	$amt = array_pop($dbc->fetch_row($r));

	if (isset($_REQUEST['submit1'])){
		echo "<form action=\"corrections.php\" method=\"post\">";
		echo "<b>Confirm transfer</b>";
		echo "<p style=\"font-size:120%\">";
		printf("\$%.2f will be moved from %d to %d (%s)",
			$amt,$cn1,$cn2,$name2);
		echo "</p><p>";
		echo "<input type=\"hidden\" name=\"type\" value=\"patronage_transfer\" />";
		echo "<input type=\"hidden\" name=\"date\" value=\"$date\" />";
		echo "<input type=\"hidden\" name=\"trans_num\" value=\"$tn\" />";
		echo "<input type=\"hidden\" name=\"memTo\" value=\"$cn2\" />";
		echo "<input type=\"submit\" name=\"submit2\" value=\"Confirm\" />";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<input type=\"submit\" value=\"Back\" onclick=\"back(); return false;\" />";
		echo "</form>";
	}
	else if (isset($_REQUEST['submit2'])){
		$dtrans = array(
		'datetime'=>$dbc->now(),
		'register_no'=>$CORRECTION_LANE,
		'emp_no'=>$CORRECTION_CASHIER,
		'trans_no'=>'',
		'upc'=>'',
		'description'=>'',
		'trans_type'=>'D',
		'trans_subtype'=>'',
		'trans_status'=>'',
		'department'=>'',
		'quantity'=>1,
		'scale'=>0,
		'cost'=>0,
		'unitPrice'=>'',
		'total'=>'',
		'regPrice'=>'',
		'tax'=>0,
		'foodstamp'=>0,
		'discount'=>0,
		'memDiscount'=>0,
		'discountable'=>0,
		'discounttype'=>0,
		'voided'=>0,
		'percentDiscount'=>0,
		'ItemQtty'=>1,
		'volDiscType'=>0,
		'volume'=>0,
		'volSpecial'=>0,
		'mixMatch'=>'',
		'matched'=>0,
		'memType'=>'',
		'isStaff'=>'',
		'numflag'=>0,
		'charflag'=>'',
		'card_no'=>'',
		'trans_id'=>''	
		);

		$dbc->query("USE $FANNIE_TRANS_DB");

		$dtrans['trans_no'] = getTransNo($CORRECTION_CASHIER,$CORRECTION_LANE);
		$dtrans['trans_id'] = 1;
		$ins = buildInsert($dtrans,-1*$amt,$CORRECTION_DEPT,$cn1);
		//echo $ins."<br /><br />";
		$dbc->query($ins);

		printf("Receipt #1: %s",$CORRECTION_CASHIER.'-'.$CORRECTION_LANE.'-'.$dtrans['trans_no']);

		$dtrans['trans_no'] = getTransNo($CORRECTION_CASHIER,$CORRECTION_LANE);
		$dtrans['trans_id'] = 1;
		$ins = buildInsert($dtrans,$amt,$CORRECTION_DEPT,$cn2);
		$dbc->query($ins);
		//echo $ins."<br /><br />";

		echo "<br /><br />";
		printf("Receipt #2: %s",$CORRECTION_CASHIER.'-'.$CORRECTION_LANE.'-'.$dtrans['trans_no']);
	}

	return;
}

echo "<form action=\"corrections.php\" method=\"post\">";
echo "<p style=\"font-size:120%\">";
echo "Date <input type=\"text\" name=\"date\" size=\"10\" /> ";
echo "<br />";
echo "Receipt # <input type=\"text\" name=\"trans_num\" size=\"10\" /> ";
echo "</p><p style=\"font-size:120%;\">";
echo "To member #<input type=\"text\" name=\"memTo\" size=\"5\" />";
echo "</p><p>";
echo "<input type=\"hidden\" name=\"type\" value=\"patronage_transfer\" />";
echo "<input type=\"submit\" name=\"submit1\" value=\"Submit\" />";
echo "</p>";
echo "</form>";

function getTransNo($emp,$register){
	global $dbc;
	$q = "SELECT max(trans_no) FROM dtransactions WHERE register_no=$register AND emp_no=$emp";
	$r = $dbc->query($q);
	$n = array_pop($dbc->fetch_row($r));
	return (empty($n)?1:$n+1);	
}

function buildInsert($dtrans,$amount,$department,$cardno){
	global $dbc, $FANNIE_OP_DB,$FANNIE_SERVER_DBMS;
	$OP = $FANNIE_OP_DB . ($FANNIE_SERVER_DBMS=='MSSQL'?'.dbo.':'.');
	$dtrans['department'] = $department;
	$dtrans['card_no'] = $cardno;
	$dtrans['unitPrice'] = $amount;
	$dtrans['regPrice'] = $amount;
	$dtrans['total'] = $amount;
	if ($amount < 0){
		$dtrans['trans_status'] = 'R';
		$dtrans['quantity'] = -1;
	}
	$dtrans['upc'] = abs($amount).'DP'.$department;

	$q = "SELECT dept_name FROM {$OP}departments WHERE dept_no=$department";
	$r = $dbc->query($q);
	$dtrans['description'] = array_pop($dbc->fetch_row($r));

	$q = "SELECT memType,Staff FROM {$OP}custdata WHERE CardNo=$cardno";
	$r = $dbc->query($q);
	$w = $dbc->fetch_row($r);
	$dtrans['memType'] = $w[0];
	$dtrans['isStaff'] = $w[1];

	$query = "INSERT INTO dtransactions VALUES (";
	foreach($dtrans as $k=>$v){
		if (is_numeric($v))
			$query .= $v.",";
		elseif($k != 'datetime')
			$query .= $dbc->escape($v).",";
		else
			$query .= $v.",";
	}
	return substr($query,0,strlen($query)-1).")";
}

?>
