<?php
include('../../../config.php');
include($FANNIE_ROOT.'src/fpdf/fpdf.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');
$TRANS = $FANNIE_TRANS_DB. ($FANNIE_SERVER_DBMS == "MSSQL" ? 'dbo.' : '.');

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
		elseif($type == "term")
			$out .= termDisplays($subtype);
		elseif($type == "paidinfull")
			$out .= paidInFullDisplays($subtype);
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
	case "paidinfull":
		$opts = paidInFullDisplays($subtype);break;
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
	
	$query = "select c.CardNo,c.LastName from
		custdata as c
		where 
		c.Type = 'PC' and c.personNum=1 order by c.CardNo";
	if ($subtype == "1month"){
		$query = "select m.card_no,c.LastName from
			memDates as m left join custdata as c
			on m.card_no=c.CardNo and c.personNum=1
			where ".$sql->monthdiff($sql->now(),'start_date')." = 1
			and c.Type = 'PC' order by m.card_no";

	}
	elseif ($subtype == "0month"){
		$query = "select m.card_no,c.LastName from
			memDates as m left join custdata as c
			on m.card_no=c.CardNo and c.personNum=1
			where ".$sql->monthdiff($sql->now(),'start_date')." = 0
			and c.Type = 'PC' order by m.card_no";
	}
	elseif ($subtype == "2month"){
		$query = "select m.card_no,c.LastName from
			memDates as m left join custdata as c
			on m.card_no=c.CardNo and c.personNum=1
			where ".$sql->monthdiff($sql->now(),'start_date')." = 2
			and c.Type = 'PC' order by m.card_no";
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
	$ret .= "<input type=submit value=\"Generate Postcards\" onclick=\"document.myform.action='postcards.php';\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Cards\" onclick=\"document.myform.action='newcards.php';\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

function upgradeDisplays($subtype){
	global $sql,$TRANS;
	$ret = "<form name=myform action=upgrade.php method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";
	
	$query = "select n.memnum,c.LastName from
		custdata as c
		LEFT JOIN {$TRANS}equity_live_balance as n
		on c.CardNo = n.memnum LEFT JOIN
		{$TRANS}memEquitySpan as e on e.card_no = c.CardNo
		where n.payments >= 100 and
		e.span <> 0 AND c.personNum=1
		and c.Type = 'PC' order by n.memnum";
	if ($subtype == "1month"){
		$query = "select n.memnum,c.LastName from
			custdata as c
			LEFT JOIN {$TRANS}equity_live_balance as n
			on c.CardNo = n.memnum LEFT JOIN
			{$TRANS}memEquitySpan as e on e.card_no = c.CardNo
			where n.payments >= 100 and
			e.span <> 0 AND c.personNum=1
			and ".$sql->monthdiff($sql->now(),'e.latestPurchase')." = 1
			and c.Type = 'PC' order by n.memnum";
	}
	elseif ($subtype == "0month"){
		$query = "select n.memnum,c.LastName from
			custdata as c
			LEFT JOIN {$TRANS}equity_live_balance as n
			on c.CardNo = n.memnum LEFT JOIN
			{$TRANS}memEquitySpan as e on e.card_no = c.CardNo
			where n.payments >= 100 and
			e.span <> 0 AND c.personNum=1
			and ".$sql->monthdiff($sql->now(),'e.latestPurchase')." = 0
			and c.Type = 'PC' order by n.memnum";
	}
	elseif ($subtype == "2month"){
		$query = "select n.memnum,c.LastName from
			custdata as c
			LEFT JOIN {$TRANS}equity_live_balance as n
			on c.CardNo = n.memnum LEFT JOIN
			{$TRANS}memEquitySpan as e on e.card_no = c.CardNo
			where n.payments >= 100 and
			e.span <> 0 AND c.personNum=1
			and ".$sql->monthdiff($sql->now(),'e.latestPurchase')." = 2
			and c.Type = 'PC' order by n.memnum";
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
	$ret .= "<input type=submit value=\"Generate Postcards\" onclick=\"document.myform.action='postcards.php';\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Cards\" onclick=\"document.myform.action='newcards.php';\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

function paidInFullDisplays($subtype){
	global $sql,$TRANS;
	$ret = "<form name=myform action=postcards.php method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";
	
	$query = "select n.memnum,c.LastName from
		custdata as c
		LEFT JOIN {$TRANS}equity_live_balance as n
		on c.CardNo = n.memnum LEFT JOIN
		{$TRANS}memEquitySpan as e on e.card_no = c.CardNo
		where n.payments >= 100 
		AND c.personNum=1
		and c.Type = 'PC' order by n.memnum";
	if ($subtype == "1month"){
		$query = "select n.memnum,c.LastName from
			custdata as c
			LEFT JOIN {$TRANS}equity_live_balance as n
			on c.CardNo = n.memnum LEFT JOIN
			{$TRANS}memEquitySpan as e on e.card_no = c.CardNo
			where n.payments >= 100 
			AND c.personNum=1
			and ".$sql->monthdiff($sql->now(),'e.latestPurchase')." = 1
			and c.Type = 'PC' order by n.memnum";
	}
	elseif ($subtype == "0month"){
		$query = "select n.memnum,c.LastName from
			custdata as c
			LEFT JOIN {$TRANS}equity_live_balance as n
			on c.CardNo = n.memnum LEFT JOIN
			{$TRANS}memEquitySpan as e on e.card_no = c.CardNo
			where n.payments >= 100 
			AND c.personNum=1
			and ".$sql->monthdiff($sql->now(),'e.latestPurchase')." = 0
			and c.Type = 'PC' order by n.memnum";
	}
	elseif ($subtype == "2month"){
		$query = "select n.memnum,c.LastName from
			custdata as c
			LEFT JOIN {$TRANS}equity_live_balance as n
			on c.CardNo = n.memnum LEFT JOIN
			{$TRANS}memEquitySpan as e on e.card_no = c.CardNo
			where n.payments >= 100 
			AND c.personNum=1
			and ".$sql->monthdiff($sql->now(),'e.latestPurchase')." = 2
			and c.Type = 'PC' order by n.memnum";
	}

	$result = $sql->query($query);
	while($row = $sql->fetch_row($result)){
		$ret .= "<option value=".$row[0].">".$row[0]." - ".$row[1]."</option>";
	}

	
	$ret .= "</select>";
	$ret .= "</td><td valign=middle>";	
	$ret .= "<input type=submit value=\"Select All\" onclick=\"selectall('cardnos'); return false;\" />";
	$ret .= "<p />";
	$ret .= "<input type=submit value=\"Generate Postcards\" onclick=\"document.myform.action='postcards.php';\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

function termDisplays($subtype){
	global $sql;
	$ret = "<form name=myform action=term.php method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";
	
	$query = "select c.CardNo,c.LastName from
		custdata as c left join
		suspensions as s on c.CardNo=s.cardno
		where 
		c.personNum=1
		and c.Type IN ('INACT','INACT2')
		and s.reasoncode & 64 <> 0
		order by c.CardNo";
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

function dueDisplays($subtype){
	global $sql, $TRANS;
	$ret = "<form name=myform action=due.php method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";

	$query = "select m.card_no,c.LastName from
		memDates as m left join custdata as c
		on m.card_no=c.CardNo and c.personNum=1
		left join {$TRANS}equity_live_balance as n on
		m.card_no = n.memnum
		where ".$sql->monthdiff($sql->now(),'m.end_date')." <= 0
		AND c.Type NOT IN ('REG','TERM','INACT2') and n.payments < 100 order by m.card_no";
	if ($subtype == "0month"){
		$query = "select m.card_no,c.LastName from
			memDates as m left join custdata as c
			on m.card_no=c.CardNo and c.personNum=1
			left join {$TRANS}equity_live_balance as n on
			m.card_no = n.memnum
			where ".$sql->monthdiff($sql->now(),'m.end_date')." = 0
			and c.Type NOT IN ('REG','TERM','INACT2') and n.payments < 100 order by m.card_no";
	}
	elseif ($subtype == "1month"){
		$query = "select m.card_no,c.LastName from
			memDates as m left join custdata as c
			on m.card_no=c.CardNo and c.personNum=1
			left join {$TRANS}equity_live_balance as n on
			m.card_no = n.memnum
			where ".$sql->monthdiff($sql->now(),'m.end_date')." = -1
			and c.Type NOT IN ('REG','TERM','INACT2') and n.payments < 100 order by m.card_no";
	}
	elseif ($subtype == "2month"){
		$query = "select m.card_no,c.LastName from
			memDates as m left join custdata as c
			on m.card_no=c.CardNo and c.personNum=1
			left join {$TRANS}equity_live_balance as n on
			m.card_no = n.memnum
			where ".$sql->monthdiff($sql->now(),'m.end_date')." = -2
			and c.Type NOT IN ('REG','TERM','INACT2') and n.payments < 100 order by m.card_no";
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
	$ret .= "<input type=submit value=\"Generate Postcards\" onclick=\"document.myform.action='postcards.php';\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

function pastDueDisplays($subtype){
	global $sql,$TRANS;
	$ret = "<form name=myform action=pastdue.php method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";

	$query = "select m.card_no,c.lastname from
		memDates as m left join custdata as c
		on m.card_no=c.cardno and c.personnum=1
		left join equity_live_balance as n on
		m.card_no = n.memnum
		where ".$sql->monthdiff($sql->now(),'m.end_date')." > 0
		AND c.type <> 'TERM' and c.type <> 'INACT2'
		AND c.type <> 'REG' and n.payments < 100 order by m.card_no";
	if ($subtype == "0month"){
		$query = "select m.card_no,c.LastName from
			memDates as m left join custdata as c
			on m.card_no=c.CardNo and c.personNum=1
			left join {$TRANS}equity_live_balance as n on
			m.card_no = n.memnum
			where ".$sql->monthdiff($sql->now(),'m.end_date')." = 0
			and (".$sql->datediff($sql->now(),'m.end_date')." > 0
			or ".$sql->datediff($sql->now(),'m.end_date')." < -30)
			AND c.Type <> 'TERM' and c.Type <> 'INACT2'
			and c.Type <> 'REG' and n.payments < 100 order by m.card_no";
	}
	elseif ($subtype == "1month"){
		$query = "select m.card_no,c.LastName from
			memDates as m left join custdata as c
			on m.card_no=c.CardNo and c.personNum=1
			left join {$TRANS}equity_live_balance as n on
			m.card_no = n.memnum
			where ".$sql->monthdiff($sql->now(),'m.end_date')." = 1
			and (".$sql->datediff($sql->now(),'m.end_date')." > 0
			or ".$sql->datediff($sql->now(),'m.end_date')." < -30)
			AND c.Type <> 'TERM' and c.Type <> 'INACT2'
			and c.Type <> 'REG' and n.payments < 100 order by m.card_no";
	}
	elseif ($subtype == "2month"){
		$query = "select m.card_no,c.LastName from
			memDates as m left join custdata as c
			on m.card_no=c.CardNo and c.personNum=1
			left join {$TRANS}equity_live_balance as n on
			m.card_no = n.memnum
			where ".$sql->monthdiff($sql->now(),'m.end_date')." = 2
			and (".$sql->datediff($sql->now(),'m.end_date')." > 0
			or ".$sql->datediff($sql->now(),'m.end_date')." < -30)
			AND c.Type <> 'TERM' and c.Type <> 'INACT2'
			and c.Type <> 'REG' and n.payments < 100 order by m.card_no";
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
	$ret .= "<input type=submit value=\"Generate Postcards\" onclick=\"document.myform.action='postcards.php';\" />";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

function arDisplays($subtype){
	global $sql,$TRANS;
	$target = "../statements/makeStatement.php";
	if ($subtype == "business")
		$target = "../statements/makeStatementBusiness.php";
	elseif($subtype == "allbusiness")
		$target = "../statements/makeStatementBusinessAll.php";
	$ret = "<form action=\"$target\" method=post>";
	$ret .= "<table cellpadding=0 cellspacing=4><tr><td>";
	$ret .= "<select id=cardnos name=cardno[] multiple size=20>";

	$query = "SELECT a.cardno, c.LastName
		   FROM {$TRANS}AR_EOM_Summary a 
		   LEFT JOIN custdata as c on c.CardNo=a.cardno and c.personNum=1
		   LEFT JOIN suspensions as s ON a.cardno=s.cardno
		   WHERE c.Type not in ('TERM') and
		   c.memType <> 9 and a.twoMonthBalance > 1
		   and c.Balance <> 0
		   and s.memtype1 <> 2
		   and a.lastMonthPayments < a.twoMonthBalance
		   ORDER BY a.cardno";
	if ($subtype == "business"){
		$query = "SELECT a.cardno, c.LastName
			   FROM {$TRANS}AR_EOM_Summary a LEFT JOIN
			   custdata as c on c.CardNo=a.cardno and c.personNum=1
			   LEFT JOIN suspensions as s ON a.cardno=s.cardno
			   WHERE c.Type not in ('TERM') and
			   (c.memType = 2 or s.memtype1 = 2)
			   and (a.LastMonthBalance <> 0 or a.lastMonthCharges <> 0 or a.lastMonthPayments <> 0)
			   ORDER BY a.cardno";
	}
	elseif($subtype == "allbusiness"){
		$query = "SELECT c.CardNo,c.LastName FROM
			custdata AS c LEFT JOIN
			{$TRANS}ar_live_balance n ON c.CardNo=n.card_no
			LEFT JOIN suspensions AS s ON c.cardno=s.CardNo
			WHERE c.Type NOT IN ('TERM') AND
			(c.memType = 2 or s.memtype1=2)
			AND c.personNum=1
			AND n.balance > 0
			ORDER BY c.CardNo";
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
	$ret .= "<input type=submit value=\"Save as List\" onclick=\"doExcel(); return false;\"/>";
	$ret .= "</td></tr></table></form>";
	return $ret;
}

?>

<html>
<head>
	<title>Member Letter Portal</title>
<script type="text/javascript" src="index.js"></script>
</head>
<body onload="document.getElementById('first').checked=true; newType('paidinfull');">
<b>Type</b>:
<!--<input type=radio id=first name=type onchange="newType('welcome');" checked /> Welcome Letters-->
<!--<input type=radio name=type onchange="newType('upgrade');" /> Upgrade Letters -->
<input type=radio id=first name=type onchange="newType('paidinfull');" /> Paid In Full 
<input type=radio name=type onchange="newType('due');" /> Equity Reminders 
<!--<input type=radio name=type onchange="newType('pastdue');" /> Equity Past Due -->
<input type=radio name=type onchange="newType('ar');" /> AR Notices
<input type=radio name=type onchange="newType('term');" /> Term Letters
<p />
<div id=buttons></div>
<div id=contents></div>
<p />
<input type=submit value="Save as List" onclick="doExcel();" />
</body>
</html>
