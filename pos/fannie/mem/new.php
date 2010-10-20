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
include('../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'auth/login.php');

if (!validateUserQuiet('memgen')){
	header("Location: {$FANNIE_URL}auth/ui/login.php?redirect={$FANNIE_URL}mem/new.php");
	exit;
}

$page_title = "Fannie :: Create Members";
$header = "Create Members";
include($FANNIE_ROOT.'src/header.html');

if (!isset($_REQUEST['createMems'])){
	// inner join so that only types
	// with defaults set up are shown
	$q = "SELECT m.memtype,m.memDesc FROM memtype AS m
		INNER JOIN memdefaults AS d ON 
		m.memtype=d.memtype ORDER BY
		m.memtype";
	$r = $dbc->query($q);
	$opts = "";
	while($w = $dbc->fetch_row($r)){
		$opts .= sprintf("<option value=%d>%s</option>",
			$w['memtype'],$w['memDesc']);
	}

	echo "<b>Create New Members</b><br />";
	echo '<form action="new.php" method="get">';
	echo '<b>Type</b>: <select name="memtype">'.$opts.'</select>';
	echo '<br /><br />';
	echo '<b>How Many</b>: <input size="4" type="text" name="num" value="40" />';
	echo '<br /><br />';
	echo '<input type="submit" name="createMems" value="Create Members" />';
	echo '</form>';
}
elseif (isset($_REQUEST['memtype']) && !is_numeric($_REQUEST['memtype'])){
	echo "<i>Error: member type wasn't set correctly</i>";	
}
elseif (isset($_REQUEST['num']) && !is_numeric($_REQUEST['num'])){
	echo "<i>'How Many' needs to be a number</i>";
}
elseif (isset($_REQUEST['num']) && $_REQUEST['num'] <= 0){
	echo "<i>'How Many' needs to be positive</i>";
}
else {
	/* going to create memberships
	   part of the insert arrays can
	   be prepopulated */
	$meminfo = array(
	'last_name'=>"''",
	'first_name'=>"''",
	'othlast_name'=>"''",
	'othfirst_name'=>"''",
	'street'=>"''",
	'city'=>"''",
	'state'=>"''",
	'zip'=>"''",
	'phone'=>"''",
	'email_1'=>"''",
	'email_2'=>"''",
	'ads_OK'=>1
	);

	$custdata = array(
	'personNum'=>1,
	'LastName'=>"'NEW MEMBER'",
	'FirstName'=>"''",
	'CashBack'=>999.99,
	'Balance'=>0,
	'MemDiscountLimit'=>0,
	'ChargeOk'=>1,
	'WriteChecks'=>1,
	'StoreCoupons'=>1,
	'Purchases'=>0,
	'NumberOfChecks'=>999,
	'memCoupons'=>0,
	'blueLine'=>"'NEW MEMBER'",
	'Shown'=>1
	);

	$defaultsQ = sprintf("SELECT cd_type,discount,staff,SSI
			FROM memdefaults WHERE memtype=%d",
			$_REQUEST['memtype']);
	$defaultsR = $dbc->query($defaultsQ);
	$defaults = $dbc->fetch_row($defaultsR);

	$custdata['Discount'] = $defaults['discount'];
	$custdata['Type'] = $dbc->escape($defaults['cd_type']);
	$custdata['staff'] = $defaults['staff'];
	$custdata['SSI'] = $defaults['SSI'];

	$custdata['memType'] = $_REQUEST['memtype'];

	/* everything's set but the actual member #s */
	$numQ = "SELECT MAX(CardNo) FROM custdata";
	if ($FANNIE_SERVER_DBMS == 'MSSQL')
		$numQ = "SELECT MAX(CAST(CardNo AS int)) FROM custdata";
	$numR = $dbc->query($numQ);
	$start = 1;
	if ($dbc->num_rows($numR) > 0){
		$numW = $dbc->fetch_row($numR);
		if (!empty($numW[0])) $start = $numW[0]+1;
	}

	$end = $start + $_REQUEST['num'] - 1;

	echo "<b>Starting number</b>: $start<br />";
	echo "<b>Ending number</b>: $end<br />";
	for($i=$start; $i<=$end; $i++){
		$custdata['CardNo'] = $dbc->escape($i);
		$meminfo['card_no'] = $i;
		$dbc->smart_insert('custdata',$custdata);
		$dbc->smart_insert('meminfo',$meminfo);
	}
}

include($FANNIE_ROOT.'src/footer.html');

?>
