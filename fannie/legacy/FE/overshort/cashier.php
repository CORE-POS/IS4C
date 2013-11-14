<?php
include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/OverShortTools/OverShortCashierPage.php');
exit;

require($FANNIE_ROOT.'src/SQLManager.php');
require($FANNIE_ROOT.'src/select_dlog.php');
include('../../db.php');
$sql->query("use is4c_trans");

if (isset($_GET["action"])){
	$out = $_GET["action"]."`";
	
	switch($_GET["action"]){
	case 'loadCashier':
		$date = $_GET['date'];
		$empno = $_GET['empno'];
		$out .= displayCashier($date,$empno);
		break;
	case 'save':
		$date = $_GET['date'];
		$empno = $_GET['empno'];
		$tenders = $_GET['tenders'];
		$notes = $_GET['notes'];
		$checks = $_GET['checks'];
		$out .= save($empno,$date,$tenders,$checks,$notes);
		break;
	}
	echo $out;
	return;
}

function displayCashier($date,$empno){
	global $sql;

	$dlog = select_dlog($date);
	$dlog = "trans_archive.dlogBig";

	$tenders = array('CA','CK','CC','MI','GD','TC','EF','EC','CP','IC','SC','AX');
	$totals = array();
	$counts = array();
	$tClause = "(";
	foreach($tenders as $t) {
		$totals[$t] = 0;
		$counts[$t] = 0;
		$tClause .= "'$t',";
	}
	$tClause = substr($tClause,0,strlen($tClause)-1).")";
	$counts["SCA"] = 0.00;


	$totalsQ = "SELECT 
		CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END
		as trans_subtype,
		-1*SUM(total) FROM $dlog WHERE emp_no = $empno
		AND ".$sql->date_equals('tdate',$date)." AND trans_subtype IN $tClause
		GROUP BY 
		CASE WHEN trans_subtype IN ('CC','AX') THEN 'CC' ELSE trans_subtype END";
	$totalsR = $sql->query($totalsQ);
	while($totalsW = $sql->fetch_row($totalsR))
		$totals[$totalsW[0]] = $totalsW[1];

	$countsQ = "SELECT tender_type,amt FROM dailyCounts WHERE emp_no = $empno AND 
		".$sql->date_equals('date',$date);
	$countsR = $sql->query($countsQ);
	while($countsW = $sql->fetch_row($countsR))
		$counts[$countsW[0]] = $countsW[1];

	$posTotal = 0;
	$countTotal = 0;
	$osTotal = 0;
	
	$ret = "";
	$ret .= "<b>$date</b> - Emp. #$empno</b><br />";	
	$ret .= "<i>Starting cash</i>: <input type=text onchange=\"resumSheet();\"  id=countSCA size=5 value=\"".$counts['SCA']."\" /><br />";
	$posTotal += $counts['SCA'];
	$ret .= "<form onsubmit=\"save(); return false;\">";
	$ret .= "<table cellpadding=4 cellspacing=0 border=1>";
	$ret .= "<tr class=color><th>Cash</th><td>POS</td><td>Count</td><td>O/S</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<th>Checks</th><td>POS</td><td>Count</td><td>O/S</td><td>List checks</td></tr>";

	$ret .= "<tr>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posCA>".$totals['CA']."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countCA value=\"".$counts['CA']."\" /></td>";
	$os = round($counts['CA'] - $totals['CA'] - $counts['SCA'],2);
	$ret .= "<td id=osCA>$os</td>";

	$posTotal += $totals['CA'];
	$countTotal += $counts['CA'];
	$osTotal += $os;

	$ret .= "<td>&nbsp;</td>";

	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posCK>".$totals['CK']."</td>";
	$ret .= "<td id=countCK>".$counts['CK']."</td>";
	$os = round($counts['CK'] - $totals['CK'],2);
	$ret .= "<td id=osCK>$os</td>";
	$checksQ = "select checks from dailyChecks where ".$sql->date_equals('date',$date)." and emp_no=$empno";
	$checksR = $sql->query($checksQ);
	$checks = "";
	while($checksW = $sql->fetch_row($checksR)){
		$checks = "";
		foreach( explode(",",$checksW[0]) as $c){
			if (is_numeric($c))
				$checks .= "$c\n";
		}
		$checks = substr($checks,0,strlen($checks)-1);
	}
	$ret .= "<td rowspan=7><textarea rows=11 cols=7 id=checklisting onchange=\"resumChecks();\">$checks</textarea></td>";
	$ret .= "</tr>";

	$posTotal += $totals['CK'];
	$countTotal += $counts['CK'];
	$osTotal += $os;

	$ret .= "<tr><td colspan=9 height=4><span style=\"font-size:1%;\">&nbsp;</span></td></tr>";

	$ret .= "<tr class=color><th>Credit Card</th><td>POS</td><td>Count</td><td>O/S</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<th>Store Charge</th><td>POS</td><td>Count</td><td>O/S</td></tr>";

	$ret .= "<tr>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posCC>".$totals['CC']."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countCC value=\"".$counts['CC']."\" /></td>";
	$os = round($counts['CC'] - $totals['CC'],2);
	$ret .= "<td id=osCC>$os</td>";

	$posTotal += $totals['CC'];
	$countTotal += $counts['CC'];
	$osTotal += $os;
	
	$ret .= "<td>&nbsp;</td>";

	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posMI>".$totals['MI']."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countMI value=\"".$counts['MI']."\" /></td>";
	$os = round($counts['MI'] - $totals['MI'],2);
	$ret .= "<td id=osMI>$os</td>";
	$ret .= "</tr>";

	$posTotal += $totals['MI'];
	$countTotal += $counts['MI'];
	$osTotal += $os;

	$ret .= "<tr><td colspan=9 height=4><span style=\"font-size:1%;\">&nbsp;</span></td></tr>";

	$ret .= "<tr class=color><th>EBT Food</th><td>POS</td><td>Count</td><td>O/S</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<th>EBT Cash</th><td>POS</td><td>Count</td><td>O/S</td></tr>";

	$ret .= "<tr>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posEF>".$totals['EF']."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countEF value=\"".$counts['EF']."\" /></td>";
	$os = round($counts['EF'] - $totals['EF'],2);
	$ret .= "<td id=osEF>$os</td>";

	$posTotal += $totals['EF'];
	$countTotal += $counts['EF'];
	$osTotal += $os;
	
	$ret .= "<td>&nbsp;</td>";

	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posEC>".$totals['EC']."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countEC value=\"".$counts['EC']."\" /></td>";
	$os = round($counts['EC'] - $totals['EC'],2);
	$ret .= "<td id=osEC>$os</td>";
	$ret .= "</tr>";

	$posTotal += $totals['EC'];
	$countTotal += $counts['EC'];
	$osTotal += $os;

	$ret .= "<tr><td colspan=9 height=4><span style=\"font-size:1%;\">&nbsp;</span></td><td rowspan=6>&nbsp;</td></tr>";

	$ret .= "<tr class=color><th>Gift Cards</th><td>POS</td><td>Count</td><td>O/S</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<th>Gift Cert.</th><td>POS</td><td>Count</td><td>O/S</td></tr>";

	$ret .= "<tr>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posGD>".$totals['GD']."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countGD value=\"".$counts['GD']."\" /></td>";
	$os = round($counts['GD'] - $totals['GD'],2);
	$ret .= "<td id=osGD>$os</td>";

	$posTotal += $totals['GD'];
	$countTotal += $counts['GD'];
	$osTotal += $os;
	
	$ret .= "<td>&nbsp;</td>";

	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posTC>".$totals['TC']."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countTC value=\"".$counts['TC']."\" /></td>";
	$os = round($counts['TC'] - $totals['TC'],2);
	$ret .= "<td id=osTC>$os</td>";
	$ret .= "</tr>";

	$posTotal += $totals['TC'];
	$countTotal += $counts['TC'];
	$osTotal += $os;

	$ret .= "<tr><td colspan=9 height=4><span style=\"font-size:1%;\">&nbsp;</span></td></tr>";

	$ret .= "<tr class=color><th>Vendor Cpns</th><td>POS</td><td>Count</td><td>O/S</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<th>Store Cpns</th><td>POS</td><td>Count</td><td>O/S</td></tr>";

	$ret .= "<tr>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posCP>".sprintf("%.2f",$totals['CP'])."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countCP value=\"".sprintf("%.2f",$counts['CP'])."\" /></td>";
	$os = round($counts['CP'] - $totals['CP'],2);
	$ret .= "<td id=osCP>$os</td>";

	$posTotal += $totals['CP'];
	$countTotal += $counts['CP'];
	$osTotal += $os;
	
	$ret .= "<td>&nbsp;</td>";

	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posIC>".$totals['IC']."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countIC value=\"".$counts['IC']."\" /></td>";
	$os = round($counts['IC'] - $totals['IC'],2);
	$ret .= "<td id=osIC>$os</td>";
	$ret .= "</tr>";

	$posTotal += $totals['IC'];
	$countTotal += $counts['IC'];
	$osTotal += $os;

	$ret .= "<tr class=color><th>Store Credit</th><td>POS</td><td>Count</td><td>O/S</td>";
	$ret .= "<td colspan=6>&nbsp;</td></tr>";

	$ret .= "<tr>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posSC>".$totals['SC']."</td>";
	$ret .= "<td><input type=text onchange=\"resumSheet();\"  size=5 id=countSC value=\"".$counts['SC']."\" /></td>";
	$os = round($counts['SC'] - $totals['SC'],2);
	$ret .= "<td id=osSC>$os</td>";
	$ret .= "<td colspan=6>&nbsp;</td></tr>";

	$posTotal += $totals['SC'];
	$countTotal += $counts['SC'];

	$ret .= "<tr><td colspan=10 height=4><span style=\"font-size:1%;\">&nbsp;</span></td></tr>";

	$ret .= "<tr class=color>";
	$ret .= "<th>Totals</th><td>POS</td><td>Count</td><td>O/S</td><td>&nbsp;</td>";
	$noteQ = "select note from dailyNotes where date='$date' and emp_no = $empno";
	$noteR = $sql->query($noteQ);
      	$noteW = $sql->fetch_array($noteR);
      	$note = str_replace("''","'",$noteW[0]);
	$ret .= "<td colspan=5 rowspan=2><textarea id=notes rows=4 cols=40>$note</textarea></td></tr>";
	$ret .= "<tr>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "<td id=posT>$posTotal</td>";
	$ret .= "<td id=countT>$countTotal</td>";
	$ret .= "<td id=osT>$osTotal</td>";
	$ret .= "<td>&nbsp;</td>";
	$ret .= "</tr>";

	$ret .= "</table>";
	$ret .= "<input type=submit value=Save />";
	$ret .= "</form>";

	$ret .= "<input type=hidden id=current_empno value=\"$empno\" />";
	$ret .= "<input type=hidden id=current_date value=\"$date\" />";

	return $ret;
}

function save($empno,$date,$tenders,$checks,$notes){
	global $sql;
	
	$notes = str_replace("'","''",urldecode($notes));
	$checkQ = "select emp_no from dailyNotes where ".$sql->date_equals('date',$date)." and emp_no=$empno";
	if ($sql->num_rows($sql->query($checkQ)) == 0){
		$insQ = "INSERT INTO dailyNotes VALUES ('$date',$empno,'$notes')";
		$sql->query_all($insQ);
	}
	else {
		$upQ = "UPDATE dailyNotes SET note='$notes' WHERE ".$sql->date_equals('date',$date)." and emp_no=$empno";
		$sql->query_all($upQ);
	}	

	$checkQ = "select id from dailyChecks where ".$sql->date_equals('date',$date)." and emp_no=$empno";
	$checkR = $sql->query($checkQ);
	if ($sql->num_rows($checkR) == 0){
		$insQ = "INSERT INTO dailyChecks (date,emp_no,checks) VALUES ('$date',$empno,'$checks')";
		$sql->query_all($insQ);
	}
	else {
		$upQ = "UPDATE dailyChecks SET checks='$checks' WHERE ".$sql->date_equals('date',$date)." and emp_no=$empno";
		$sql->query_all($upQ);
	}

	$tarray = explode("|",$tenders);
	foreach($tarray as $t){
		$temp = explode(":",$t);
		if (count($temp) != 2) continue;
		if (!is_numeric($temp[1])) continue;

		$tender = $temp[0];
		$amt = $temp[1];

		$checkQ = "SELECT emp_no FROM dailyCounts where ".$sql->date_equals('date',$date)." and emp_no=$empno and tender_type='$tender'";
		if ($sql->num_rows($sql->query($checkQ)) == 0){
			$insQ = "INSERT INTO dailyCounts VALUES ('$date',$empno,'$tender',$amt)";
			$sql->query_all($insQ);
		}
		else {
			$upQ = "UPDATE dailyCounts SET amt=$amt WHERE ".$sql->date_equals('date',$date)." and emp_no=$empno and tender_type='$tender'";
			$sql->query_all($upQ);
		}
	}

	return "Saved";
}

?>

<html>
<head>
	<title>Cashier</title>
<script type=text/javascript src=cashier.js></script>
<link href="<?php echo $FANNIE_URL; ?>src/style.css"
      rel="stylesheet" type="text/css">
<script src="<?php echo $FANNIE_URL; ?>src/CalendarControl.js"
        language="javascript"></script>
<style type=text/css>
tr.color {
	background: #ffffcc;
}
</style>
</head>
<body>
<div id=input>
<form onsubmit="loadCashier(); return false;">
<b>Date</b>:<input type=text  id=date size=10 onfocus="this.value='';showCalendarControl(this);" /> 
<b>Cashier</b>:<input type=text  id=empno size=5 /> 
<input type=submit value="Load Cashier" />
</form>
</div>

<div id=display>
</div>

</body>
</html>
