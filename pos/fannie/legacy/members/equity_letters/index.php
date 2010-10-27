<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['action'])){
	$out = $_GET['action']."`";
	switch($_GET['action']){
	case 'redisplay':
		$type = $_GET['type'];
		$subtype = $_GET['subtype'];
		if ($type == "welcome")
			$out .= welcomeDisplays($subtype);
		elseif($type == "due")
			$out .= dueDisplays($subtype);
		elseif($type == "pastdue")
			$out .= pastDueDisplays($subtype);
		elseif($type == "ar")
			$out .= arDisplays($subtype);
		elseif($type == "upgrade")
			$out .= upgradeDisplays($subtype);
		break;	
	}
	echo $out;
	return;
}
elseif (isset($_GET['excel'])){
	$type = $_GET['type'];
	$subtype = $_GET['subtype'];
	$opts = "";
	switch($type){
	case "welcome":
		$opts = welcomeDisplays($subtype); break;
	case "due":
		$opts = dueDisplays($subtype); break;
	case "pastdue":
		$opts = pastDueDisplays($subtype); break;
	case "ar":
		$opts = arDisplays($subtype); break;
	case "upgrade":
		$opts = upgradeDisplays($subtype); break;
	}

	$opts = preg_replace("/.*?<select.*?>/","",$opts);
	$opts = preg_replace("/<\/select>.*/","",$opts);
	$opts = preg_replace("/<option .*?>/","",$opts);
	$opts = explode("</option>",$opts);

	$out = "<table cellpadding=4 cellspacing=0 border=1>";
	foreach($opts as $o){
		if ($o == "") continue;
		$temp = explode(" - ",$o);
		$out .= "<tr>";
		foreach($temp as $t)
			$out .= "<td>".$t."</td>";
		$out .= "</tr>";
	}	
	$out .= "</table>";

	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="memberPortalList.xls"');
	echo $out;
	return;
}

function welcomeDisplays($subtype){
	global $sql;
	$ret = "<form name=myform action=welcome.php method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";
	
	$query = "select c.cardno,c.lastname from
		custdata as c
		where 
		c.type = 'PC' and c.personnum=1 order by convert(int,c.cardno)";
	if ($subtype == "1month"){
		$query = "select m.card_no,c.lastname from
			memDates as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			where datediff(mm,getdate(),start_date) = -1
			and c.type = 'PC' order by m.card_no";

	}
	elseif ($subtype == "0month"){
		$query = "select m.card_no,c.lastname from
			memDates as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			where datediff(mm,getdate(),start_date) = 0
			and c.type = 'PC' order by m.card_no";
	}
	elseif ($subtype == "2month"){
		$query = "select m.card_no,c.lastname from
			memDates as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			where datediff(mm,getdate(),start_date) = -2
			and c.type = 'PC' order by m.card_no";
	}

	$result = $sql->query($query);
	while($row = $sql->fetch_row($result)){
		$ret .= "<option value=".$row[0].">".$row[0]." - ".$row[1]."</option>";
	}

	
	$ret .= "</select>";
	$ret .= "</td><td valign=middle>";	
	$ret .= "<input type=submit value=\"Select All\" onclick=\"selectall('cardnos'); return false;\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Letters\" onclick=\"document.myform.action='welcome.php';\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Cards\" onclick=\"document.myform.action='newcards.php';\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

function upgradeDisplays($subtype){
	global $sql;
	$ret = "<form name=myform action=upgrade.php method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";
	
	$query = "select n.memnum,c.lastname from
		custdata as c
		LEFT JOIN newBalanceStockToday_test as n
		on c.cardno = n.memnum LEFT JOIN
		memEquitySpan as e on e.card_no = c.cardno
		where n.payments >= 100 and
		e.span <> 0 AND c.personnum=1
		and c.type = 'PC' order by n.memnum";
	if ($subtype == "1month"){
		$query = "select n.memnum,c.lastname from
			custdata as c
			LEFT JOIN newBalanceStockToday_test as n
			on c.cardno = n.memnum LEFT JOIN
			memEquitySpan as e on e.card_no = c.cardno
			where n.payments >= 100 and
			e.span <> 0 AND c.personnum=1
			and datediff(mm,getdate(),e.latestPurchase) = -1
			and c.type = 'PC' order by n.memnum";
	}
	elseif ($subtype == "0month"){
		$query = "select n.memnum,c.lastname from
			custdata as c
			LEFT JOIN newBalanceStockToday_test as n
			on c.cardno = n.memnum LEFT JOIN
			memEquitySpan as e on e.card_no = c.cardno
			where n.payments >= 100 and
			e.span <> 0 AND c.personnum=1
			and datediff(mm,getdate(),e.latestPurchase) = 0
			and c.type = 'PC' order by n.memnum";
	}
	elseif ($subtype == "2month"){
		$query = "select n.memnum,c.lastname from
			custdata as c
			LEFT JOIN newBalanceStockToday_test as n
			on c.cardno = n.memnum LEFT JOIN
			memEquitySpan as e on e.card_no = c.cardno
			where n.payments >= 100 and
			e.span <> 0 AND c.personnum=1
			and datediff(mm,getdate(),e.latestPurchase) = -2
			and c.type = 'PC' order by c.cardno";
	}

	$result = $sql->query($query);
	while($row = $sql->fetch_row($result)){
		$ret .= "<option value=".$row[0].">".$row[0]." - ".$row[1]."</option>";
	}

	
	$ret .= "</select>";
	$ret .= "</td><td valign=middle>";	
	$ret .= "<input type=submit value=\"Select All\" onclick=\"selectall('cardnos'); return false;\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Letters\" onclick=\"document.myform.action='upgrade.php';\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Cards\" onclick=\"document.myform.action='newcards.php';\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

function dueDisplays($subtype){
	global $sql;
	$ret = "<form action=due.php method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";

	$query = "select m.card_no,c.lastname from
		memDates as m left join custdata as c
		on m.card_no=c.cardno and c.personnum=1
		left join newBalanceStockToday_test as n on
		m.card_no = n.memnum
		where datediff(mm,getdate(),m.end_date) >= 0
		AND c.type NOT IN ('REG','TERM','INACT2') and n.payments < 100 order by m.card_no";
	if ($subtype == "0month"){
		$query = "select m.card_no,c.lastname from
			memDates as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			left join newBalanceStockToday_test as n on
			m.card_no = n.memnum
			where datediff(mm,getdate(),m.end_date) = 0
			and c.type NOT IN ('REG','TERM','INACT2') and n.payments < 100 order by m.card_no";
	}
	elseif ($subtype == "1month"){
		$query = "select m.card_no,c.lastname from
			memDates as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			left join newBalanceStockToday_test as n on
			m.card_no = n.memnum
			where datediff(mm,getdate(),m.end_date) = 1
			and c.type NOT IN ('REG','TERM','INACT2') and n.payments < 100 order by m.card_no";
	}
	elseif ($subtype == "2month"){
		$query = "select m.card_no,c.lastname from
			memDates as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			left join newBalanceStockToday_test as n on
			m.card_no = n.memnum
			where datediff(mm,getdate(),m.end_date) = 2
			and c.type NOT IN ('REG','TERM','INACT2') and n.payments < 100 order by m.card_no";
	}

	$result = $sql->query($query);
	while($row = $sql->fetch_row($result)){
		$ret .= "<option value=".$row[0].">".$row[0]." - ".$row[1]."</option>";
	}

	$ret .= "</select>";
	$ret .= "</td><td valign=middle>";	
	$ret .= "<input type=submit value=\"Select All\" onclick=\"selectall('cardnos'); return false;\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Letters\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

function pastDueDisplays($subtype){
	global $sql;
	$ret = "<form action=pastdue.php method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";

	$query = "select m.card_no,c.lastname from
		memDates as m left join custdata as c
		on m.card_no=c.cardno and c.personnum=1
		left join newBalanceStockToday_test as n on
		m.card_no = n.memnum
		where datediff(dd,getdate(),m.end_date) < 0
		AND c.type <> 'TERM' and c.type <> 'INACT2'
		AND c.type <> 'REG' and n.payments < 100 order by m.card_no";
	if ($subtype == "0month"){
		$query = "select m.card_no,c.lastname from
			memDates as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			left join newBalanceStockToday_test as n on
			m.card_no = n.memnum
			where datediff(mm,getdate(),m.end_date) = 0
			and (datediff(dd,getdate(),m.end_date) < 0
			or datediff(dd,getdate(),m.end_date) > -30)
			AND c.type <> 'TERM' and c.type <> 'INACT2'
			and c.type <> 'REG' and n.payments < 100 order by m.card_no";
	}
	elseif ($subtype == "1month"){
		$query = "select m.card_no,c.lastname from
			memDates as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			left join newBalanceStockToday_test as n on
			m.card_no = n.memnum
			where datediff(mm,getdate(),m.end_date) = -1
			and datediff(dd,getdate(),m.end_date) < 0
			AND c.type <> 'TERM' and c.type <> 'INACT2'
			and c.type <> 'REG' and n.payments < 100 order by m.card_no";
	}
	elseif ($subtype == "2month"){
		$query = "select m.card_no,c.lastname from
			memDates as m left join custdata as c
			on m.card_no=c.cardno and c.personnum=1
			left join newBalanceStockToday_test as n on
			m.card_no = n.memnum
			where datediff(mm,getdate(),m.end_date) = -2
			and datediff(dd,getdate(),m.end_date) < 0
			AND c.type <> 'TERM' and c.type <> 'INACT2'
			and c.type <> 'REG' and n.payments < 100 order by m.card_no";
	}

	$result = $sql->query($query);
	while($row = $sql->fetch_row($result)){
		$ret .= "<option value=".$row[0].">".$row[0]." - ".$row[1]."</option>";
	}

	$ret .= "</select>";
	$ret .= "</td><td valign=middle>";	
	$ret .= "<input type=submit value=\"Select All\" onclick=\"selectall('cardnos'); return false;\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Letters\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

function arDisplays($subtype){
	global $sql;
	$target = "../statements/makeStatement.php";
	if ($subtype == "business")
		$target = "../statements/makeStatementBusiness.php";
	elseif($subtype == "allbusiness")
		$target = "../statements/makeStatementBusinessAll.php";
	$ret = "<form action=\"$target\" method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";

	$query = "SELECT a.cardno, c.lastname
		   FROM AR_EOM_Summary a 
		   LEFT JOIN custdata as c on c.cardno=a.cardno and c.personnum=1
		   WHERE c.type not in ('TERM') and
		   c.memtype <> 9 and a.twoMonthBalance > 1
		   and c.Balance <> 0
		   and a.lastMonthPayments < a.twoMonthBalance
		   ORDER BY a.cardno";
	if ($subtype == "business"){
		$query = "SELECT a.cardno, c.lastname
			   FROM AR_EOM_Summary a LEFT JOIN
			   custdata as c on c.cardno=a.cardno and c.personnum=1
			   WHERE c.type not in ('TERM') and
			   c.memtype = 2
			   and (a.LastMonthBalance <> 0 or a.lastMonthCharges <> 0 or a.lastMonthPayments <> 0)
			   ORDER BY a.cardno";
	}
	elseif($subtype == "allbusiness"){
		$query = "SELECT c.cardno,c.lastname FROM
			custdata AS c LEFT JOIN
			newBalanceToday_cust n ON c.cardno=n.memnum
			WHERE c.type NOT IN ('TERM') AND
			c.memtype = 2
			AND c.personnum=1
			AND n.balance > 0
			ORDER BY c.cardno";
	}

	$result = $sql->query($query);
	while($row = $sql->fetch_row($result)){
		$ret .= "<option value=".$row[0].">".$row[0]." - ".$row[1]."</option>";
	}

	$ret .= "</select>";
	$ret .= "</td><td valign=middle>";	
	$ret .= "<input type=submit value=\"Select All\" onclick=\"selectall('cardnos'); return false;\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Letters\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Save as List\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}


?>

<html>
<head>
	<title>Member Letter Portal</title>
<script type="text/javascript" src="index.js"></script>
</head>
<body onload="document.getElementById('first').checked=true; newType('welcome');">
<b>Type</b>:
<input type=radio id=first name=type onchange="newType('welcome');" checked /> Welcome Letters
<input type=radio name=type onchange="newType('upgrade');" /> Upgrade Letters 
<input type=radio name=type onchange="newType('due');" /> Equity Reminders 
<input type=radio name=type onchange="newType('pastdue');" /> Equity Past Due 
<input type=radio name=type onchange="newType('ar');" /> AR Notices
<p />
<div id=buttons></div>
<div id=contents></div>
<p />
<input type=submit value="Save as List" onclick="doExcel();" />
</body>
</html>
