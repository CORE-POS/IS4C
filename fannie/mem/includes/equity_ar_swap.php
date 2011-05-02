<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (empty($FANNIE_EQUITY_DEPARTMENTS)){
	echo "<em>Error: no equity departments found</em>";
	echo "<br /><br />";
	echo "<a href=\"corrections.php\">Back</a>";
	return;
}

if (empty($FANNIE_AR_DEPARTMENTS)){
	echo "<em>Error: no AR departments found</em>";
	echo "<br /><br />";
	echo "<a href=\"corrections.php\">Back</a>";
	return;
}

$ret = preg_match_all("/[0-9]+/",$FANNIE_EQUITY_DEPARTMENTS,$depts);
if ($ret == 0){
	echo "<em>Error: can't read equity department definition</em>";
	echo "<br /><br />";
	echo "<a href=\"corrections.php\">Back</a>";
	return;
}
$depts = array_pop($depts);

$ret = preg_match_all("/[0-9]+/",$FANNIE_AR_DEPARTMENTS,$adepts);
if ($ret == 0){
	echo "<em>Error: can't read AR department definition</em>";
	echo "<br /><br />";
	echo "<a href=\"corrections.php\">Back</a>";
	return;
}
$adepts = array_pop($adepts);
foreach($adepts as $a)
	$depts[] = $a;

$dlist = "(";
foreach ($depts as $d){
	$dlist .= $d.",";	
}
$dlist = substr($dlist,0,strlen($dlist)-1).")";

$q = "SELECT dept_no,dept_name FROM departments WHERE dept_no IN $dlist";
$r = $dbc->query($q);
if ($dbc->num_rows($r) == 0){
	echo "<em>Error: department(s) don't exist</em>";
	echo "<br /><br />";
	echo "<a href=\"corrections.php\">Back</a>";
	return;
}

$depts = array();
while($row = $dbc->fetch_row($r)){
	$depts[$row[0]] = $row[1];
}

if (isset($_REQUEST['submit1']) || isset($_REQUEST['submit2'])){
	$dept1 = $_REQUEST['deptFrom'];
	$dept2 = $_REQUEST['deptTo'];
	$amount = $_REQUEST['amount'];
	$cn = $_REQUEST['card_no'];

	if (!isset($depts[$dept1]) || !isset($depts[$dept2])){
		echo "<em>Error: department doesn't exist</em>";
		echo "<br /><br />";
		echo "<a href=\"\" onclick=\"back(); return false;\">Back</a>";
		return;
	}
	if (!is_numeric($amount)){
		echo "<em>Error: amount given ($amount) isn't a number</em>";
		echo "<br /><br />";
		echo "<a href=\"\" onclick=\"back(); return false;\">Back</a>";
		return;
	}
	if (!is_numeric($cn)){
		echo "<em>Error: member given ($cn1) isn't a number</em>";
		echo "<br /><br />";
		echo "<a href=\"\" onclick=\"back(); return false;\">Back</a>";
		return;
	}
	if ($dept1 == $dept2){
		echo "<em>Error: departments are the same; nothing to convert</em>";
		echo "<br /><br />";
		echo "<a href=\"\" onclick=\"back(); return false;\">Back</a>";
		return;
	}

	$q = "SELECT firstname+' '+lastname FROM custdata WHERE cardno=$cn AND personnum=1";
	$r = $dbc->query($q);
	if ($dbc->num_rows($r) == 0){
		echo "<em>Error: no such member: $cn</em>";
		echo "<br /><br />";
		echo "<a href=\"\" onclick=\"back(); return false;\">Back</a>";
		return;
	}
	$name1 = array_pop($dbc->fetch_row($r));

	if (isset($_REQUEST['submit1'])){
		echo "<form action=\"corrections.php\" method=\"post\">";
		echo "<b>Confirm transactions</b>";
		echo "<p style=\"font-size:120%\">";
		printf("\$%.2f will be moved from %s to %s for Member #%d (%s)",
			$amount,$depts[$dept1],$depts[$dept2],$cn,$name1);
		echo "</p><p>";
		echo "<input type=\"hidden\" name=\"type\" value=\"equity_ar_swap\" />";
		echo "<input type=\"hidden\" name=\"deptFrom\" value=\"$dept1\" />";
		echo "<input type=\"hidden\" name=\"deptTo\" value=\"$dept2\" />";
		echo "<input type=\"hidden\" name=\"amount\" value=\"$amount\" />";
		echo "<input type=\"hidden\" name=\"card_no\" value=\"$cn\" />";
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

		$dtrans['trans_no'] = getTransNo($CORRECTION_CASHIER,$CORRECTION_LANE);
		$dtrans['trans_id'] = 1;
		$ins = buildInsert($dtrans,-1*$amount,$dept1,$cn);
		//echo $ins."<br /><br />";
		$dbc->query($ins);
		$dtrans['trans_id']++;
		$ins = buildInsert($dtrans,$amount,$dept2,$cn);
		$dbc->query($ins);
		//echo $ins."<br /><br />";

		printf("Receipt #1: %s",$CORRECTION_CASHIER.'-'.$CORRECTION_LANE.'-'.$dtrans['trans_no']);
	}

	return;
}

echo "<form action=\"corrections.php\" method=\"post\">";
echo "<p style=\"font-size:120%\">";
echo "Convert $<input type=\"text\" name=\"amount\" size=\"5\" /> ";
echo "<select name=\"deptFrom\">";
foreach($depts as $k=>$v)
	echo "<option value=\"$k\">$v</option>";
echo "</select>";
echo " to ";
echo "<select name=\"deptTo\">";
foreach($depts as $k=>$v)
	echo "<option value=\"$k\">$v</option>";
echo "</select>";
echo "</p><p style=\"font-size:120%;\">";
$memNum = isset($_REQUEST['memIN'])?$_REQUEST['memIN']:'';
echo "Member #<input type=\"text\" name=\"card_no\" size=\"5\" value=\"$memNum\" /> ";
echo "</p><p>";
echo "<input type=\"hidden\" name=\"type\" value=\"equity_ar_swap\" />";
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
	global $dbc;
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

	$q = "SELECT dept_name FROM departments WHERE dept_no=$department";
	$r = $dbc->query($q);
	$dtrans['description'] = array_pop($dbc->fetch_row($r));

	$q = "SELECT memType,staff FROM custdata WHERE cardno=$cardno";
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
