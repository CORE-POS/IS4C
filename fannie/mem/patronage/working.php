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
include('../../config.php');
include($FANNIE_ROOT.'src/trans_connect.php');
include($FANNIE_ROOT.'src/select_dlog.php');
include($FANNIE_ROOT.'install/db.php');

$page_title = "Fannie :: Working Table";
$header = "Working Table";

include($FANNIE_ROOT.'src/header.html');

if (isset($_REQUEST['date1'])){
	$mtype = "(";
	$mArgs = array();
	foreach($_REQUEST['mtype'] as $m){
		$mtype .= '?,';
		$mArgs[] = (int)$m;
	}
	$mtype = rtrim($mtype,",").")";

	$dlog = select_dlog($_REQUEST['date1'],$_REQUEST['date2']);

	if ($dbc->table_exists("dlog_patronage")){
		$drop = $dbc->prepare_statement("DROP TABLE dlog_patronage");
		$dbc->exec_statement($drop);
	}
	$create = $dbc->prepare_statement(duplicate_structure($FANNIE_SERVER_DBMS,'dlog_15','dlog_patronage'));
	$dbc->exec_statement($create);

	$insQ = sprintf("INSERT INTO dlog_patronage
			SELECT d.* FROM %s AS d
			LEFT JOIN %s%scustdata AS c ON c.CardNo=d.card_no
			AND c.personNum=1 LEFT JOIN
			%s%ssuspensions AS s ON d.card_no=s.cardno
			WHERE (d.trans_type IN ('I','D','S')
			OR (d.trans_type='T' AND d.trans_subtype IN ('MA','IC')))	
			AND d.total <> 0
			AND (s.memtype1 IN %s OR c.memType IN %s)
			AND d.tdate BETWEEN ? AND ?",
			$dlog,$FANNIE_OP_DB,$dbc->sep(),
			$FANNIE_OP_DB,$dbc->sep(),
			$mtype,$mtype);
	$args = $mArgs;
	foreach($mArgs as $m) $args[] = $m; // need them twice
	$args[] = $_REQUEST['date1'].' 00:00:00';
	$args[] = $_REQUEST['date2'].' 23:59:59';
	
	$prep = $dbc->prepare_statement($insQ);
	$dbc->exec_statement($prep,$args);

	echo '<i>Patronage working table created</i>';
}
else {
	echo '<script type="text/javascript" src="'.$FANNIE_URL.'src/CalendarControl.js"></script>';
	echo '<blockquote><i>';
	echo 'Step one: gather member transactions for the year. Dates specify the start and
	end of the year. Inactive and terminated memberships will be included if their type,
	prior to suspension, matches one of the requested types.';
	echo '</i></blockquote>';
	echo '<form action="working.php" method="get">';
	echo '<table>';
	echo '<tr><th>Start Date</th>';
	echo '<td><input type="text" name="date1" onfocus="showCalendarControl(this);" />';
	echo '</tr><tr><th>End Date</th>';
	echo '<td><input type="text" name="date2" onfocus="showCalendarControl(this);" />';
	echo '</tr><tr><td colspan="2"><b>Member Type</b>:<br />';
	$typeQ = $dbc->prepare_statement("SELECT memtype,memDesc FROM ".$FANNIE_OP_DB.$dbc->sep()."memtype ORDER BY memtype");
	$typeR = $dbc->exec_statement($typeQ);
	while($typeW = $dbc->fetch_row($typeR)){
		printf('<input type="checkbox" value="%d" name="mtype[]"
			id="mtype%d" /><label for="mtype%d">%s</label><br />',
			$typeW['memtype'],$typeW['memtype'],
			$typeW['memtype'],$typeW['memDesc']
		);
	}
	echo '</td></tr>';
	echo '</table><br />';
	echo '<input type="submit" value="Create Table" />';
	echo '</form>';
}

echo '<br /><br />';
echo '<a href="index.php">Patronage Menu</a>';

include($FANNIE_ROOT.'src/footer.html');
?>
