<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');

if (isset($_GET["submit"])){
	$date = "";
	if (isset($_GET["date"])) $date = $_GET["date"];
	$trans_num = "";
	if (isset($_GET["trans_num"])) $trans_num = $_GET["trans_num"];
	$card_no = "";
	if (isset($_GET["card_no"])) $card_no = $_GET["card_no"];
	$emp_no = "";
	if (isset($_GET["emp_no"])) $emp_no = $_GET["emp_no"];
	$register_no = "";
	if (isset($_GET["register_no"])) $register_no = $_GET["register_no"];
	$trans_subtype = "";
	if (isset($_GET["trans_subtype"])) $trans_subtype = $_GET["trans_subtype"];
	$tenderTotal = "";
	if (isset($_GET["tenderTotal"])) $tenderTotal = $_GET["tenderTotal"];
	$department = "";
	if (isset($_GET["department"])) $department = $_GET["department"];
	$trans_no="";

	if ($trans_num != ""){
		$temp = explode("-",$trans_num);
		$emp_no = $temp[0];
		$register_no=$temp[1];
		$trans_no=$temp[2];
	}

	$dlog = $FANNIE_TRANS_DB.".dlog_15";
	if ($FANNIE_SERVER_DBMS == 'MSSQL')
		$dlog = $FANNIE_TRANS_DB.".dbo.dlog_15";
	if ($date != "") $dlog = select_dlog($date);
	$query = "SELECT year(tdate),month(tdate),day(tdate),emp_no,register_no,trans_no FROM $dlog WHERE ";
	if ($date != "")
		$query .= $dbc->datediff("'$date'",'tdate')."=0";
	if ($card_no != ""){
		if ($query[strlen($query)-1] != " ") $query .= " AND ";
		$query .= "card_no='$card_no'";
	}
	if ($emp_no != ""){
		if ($query[strlen($query)-1] != " ") $query .= " AND ";
		$query .= "emp_no='$emp_no'";
	}
	if ($register_no != ""){
		if ($query[strlen($query)-1] != " ") $query .= " AND ";
		$query .= "register_no='$register_no'";
	}
	if ($trans_no != ""){
		if ($query[strlen($query)-1] != " ") $query .= " AND ";
		$query .= "trans_no='$trans_no'";
	}

	$tender_clause = "( ";
	if ($trans_subtype != "")
		$tender_clause .= "trans_subtype='$trans_subtype'";	
	if ($tenderTotal != ""){
		if ($tender_clause[strlen($tender_clause)] != " ") $tender_clause .= " AND ";
		$tender_clause .= "total=-1*$tenderTotal";
	}
	$tender_clause .= ")";

	$or_clause = "( ";
	if ($tender_clause != "( )") $or_clause .= $tender_clause;
	if ($department != ""){
		if ($or_clause[strlen($or_clause)-1] != " ") $or_clause .= " OR ";
		$or_clause .= "department='$department'";
	}
	$or_clause .= ")";

	if ($or_clause != "( )"){
		if ($query[strlen($query)-1] != " ") $query .= " AND ";
		$query .= $or_clause;
	}

	if ($query[strlen($query)-1] == " ") $query .= "trans_num != ''";

	$query .= " GROUP BY year(tdate),month(tdate),day(tdate),emp_no,register_no,trans_no ";
	$query .= " ORDER BY year(tdate),month(tdate),day(tdate),emp_no,register_no,trans_no ";

	$result = $dbc->query($query);
	if ($dbc->num_rows($result) == 0)
		echo "<b>No receipts match the given criteria</b>";
	elseif ($dbc->num_rows($result) == 1){
		$row = $dbc->fetch_row($result);
		$year = $row[0];
		$month = $row[1];
		$day = $row[2];
		$trans_num = $row[3].'-'.$row[4].'-'.$row[5];
		header("Location: reprint.php?year=$year&month=$month&day=$day&receipt=$trans_num");
	}
	else {
		$page_title = "Fannie : Receipt Lookup";
		$header = "Receipt Lookup";
		include($FANNIE_ROOT.'src/header.html');
		echo "<b>Matching receipts</b>:<br />";
		while ($row = $dbc->fetch_row($result)){
			$year = $row[0];
			$month = $row[1];
			$day = $row[2];
			$trans_num = $row[3].'-'.$row[4].'-'.$row[5];
			echo "<a href=reprint.php?year=$year&month=$month&day=$day&receipt=$trans_num>";
			echo "$year-$month-$day $trans_num</a><br />";
		}
		include($FANNIE_ROOT.'src/footer.html');
	}
}
else {

$depts = "<option value=\"\">Select one...</option>";
$r = $dbc->query("SELECT dept_no,dept_name from departments order by dept_name");
while($w = $dbc->fetch_row($r)){
	$depts .= sprintf("<option value=%d>%s</option>",$w[0],$w[1]);
}

$page_title = "Fannie : Receipt Lookup";
$header = "Receipt Lookup";
include($FANNIE_ROOT.'src/header.html');

?>

<script src="../../src/CalendarControl.js"
        type="text/javascript"></script>
<style type=text/css>
#mytable th {
	background: #330066;
	color: white;
	padding-left: 4px;
	padding-right: 4px;
}
</style>

<form action=index.php method=get>
Receipt Search - Fill in any information available
<table id=mytable cellspacing=4 cellpadding=0>
<tr>
	<th>Date*</th><td colspan=2><input type=text name=date size=10 onfocus="showCalendarControl(this);" /></td>
	<th>Receipt #</th><td><input type=text name=trans_num size=6 /></td>
</tr>
<tr>
	<th>Member #</th><td><input type=text name=card_no size=6 /></td>
	<th>Cashier #</th><td><input type=text name=emp_no size=6 /></td>
	<th>Lane #</th><td><input type=text name=register_no size=6 /></td>
</tr>
<tr>
	<th>Tender type</th><td colspan=2><select name=trans_subtype>
		<option value="">Select one...</option>
		<option value=CA>Cash</option>
		<option value=CC>Credit Card</option>
		<option value=CK>Check</option>
		<option value=MI>Store Charge</option>
		<option value=EC>EBT Cash</option>
		<option value=EF>EBT Foodstamps</option>
		<option value=GD>Gift Card</option>
		<option value=CP>Coupon</option>
		<option value=IC>InStore Coupon</option>
		<option value=TC>Gift Certificate</option>
		<option value=MA>MAD Coupon</option>
		<option value=RR>RRR Coupon</option>
		<option value=SC>Store Credit</option>
	</select></td>
	<th colspan=2>Tender amount</th><td><input type=text name=tenderTotal size=6 /></td>
</tr>
<tr>
	<th>Department</th><td colspan=2><select name=department><?php echo $depts ?></select></td>
	<td colspan=2><input name=submit type=submit value="Find recipt(s)" /></td>
</tr>

</table>
<i>* If no date is given, all matching receipts from the past 15 days will be returned</i><br />
<b>Tips</b>:<br />
<li>A date and a receipt number is sufficient to find any receipt</li>
<li>If you have a receipt number, you don't need to specify a lane or cashier number</li>
<li>ALL fields are optional. You can specify a tender type without an amount (or vice versa)</li>
</form>
<?php
include($FANNIE_ROOT.'src/footer.html');
}
?>
