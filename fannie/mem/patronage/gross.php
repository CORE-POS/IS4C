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
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'install/db.php');

$page_title = "Fannie :: Patronage Tools";
$header = "Calculate Gross Purchases &amp; Discounts";

include($FANNIE_ROOT.'src/header.html');

if (isset($_REQUEST['FY'])){
	if ($dbc->table_exists("patronage_workingcopy")){
		$drop = $dbc->prepare_statement("DROP TABLE patronage_workingcopy");
		$dbc->exec_statement($drop);
	}
	$create = $dbc->prepare_statement(duplicate_structure($FANNIE_SERVER_DBMS,'patronage','patronage_workingcopy'));
	$dbc->exec_statement($create);

	$insQ = sprintf("INSERT INTO patronage_workingcopy
		SELECT card_no,
		SUM(CASE WHEN trans_type IN ('I','D') THEN total ELSE 0 END),
		SUM(CASE WHEN trans_type IN ('S') then total ELSE 0 END),
		0,0,0,0,0,?
		FROM %s%sdlog_patronage as d
		LEFT JOIN MasterSuperDepts AS m
		ON d.department=m.dept_ID WHERE m.superID is null or m.superID <> 0
		GROUP BY card_no",$FANNIE_TRANS_DB,$dbc->sep());
	$prep = $dbc->prepare_statement($insQ);
	$dbc->exec_statement($prep,array($_REQUEST['FY']));
	
	echo '<i>Purchases and Discounts loaded</i>';
}
else {
	echo '<blockquote><i>';
	echo 'Step two: calculate totals sales and percent discounts per member for the year';
	echo '</i></blockquote>';
	echo '<form action="gross.php" method="get">';
	echo '<b>Fiscal Year</b>: ';
	echo '<select name="FY">';
	$q = $dbc->prepare_statement("SELECT year(tdate) FROM $FANNIE_TRANS_DB".$dbc->sep()."dlog_patronage
		GROUP BY year(tdate) ORDER BY year(tdate) DESC");
	$r = $dbc->exec_statement($q);
	while($w = $dbc->fetch_row($r))
		printf('<option>%d</option>',$w[0]);
	echo '</select>';
	echo '<br /><br />';
	echo '<input type="submit" value="Calculate Purchases" />';
	echo '</form>';
}

echo '<br /><br />';
echo '<a href="index.php">Patronage Menu</a>';

include($FANNIE_ROOT.'src/footer.html');

?>
