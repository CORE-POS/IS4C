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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 Show total sales by hour for today from dlog.
 Offer dropdown of superdepartments and on-select display the same report for
  that superdept only.
*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 6Aug12 Fix obsolete value-getting in dbc.
        In fact dbc doesn't need to be created here at all.
*/

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$selected = (isset($_GET['super']))?$_GET['super']:-1;
$name = "";

$superP = $dbc->prepare_statement("SELECT superID,super_name FROM MasterSuperDepts ORDER BY super_name");
$superR = $dbc->exec_statement($superP);
$supers = array();
$supers[-1] = "All";
while($row = $dbc->fetch_row($superR)){
	$supers[$row[0]] = $row[1];
	if ($selected == $row[0])
		$name = $row[1];
}

$page_title = "Fannie : Today's $name Sales";
$header = "Today's $name Sales";
include($FANNIE_ROOT.'src/header.html');
$today = date("Y-m-d");

$query1="SELECT ".$dbc->hour('tdate').", 
sum(total)as Sales
FROM ".$FANNIE_TRANS_DB.$dbc->sep()."dlog as d left join MasterSuperDepts as t
on d.department = t.dept_ID
WHERE ".$dbc->datediff('tdate',$dbc->now())."=0
AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
AND (t.superID > 0 or t.superID IS NULL)
GROUP BY ".$dbc->hour('tdate')."
order by ".$dbc->hour('tdate');
$args = array();
if ($selected != -1){
	$query1="SELECT ".$dbc->hour('tdate').", 
	sum(total)as Sales,
	sum(case when t.superID=? then total else 0 end) as prodSales
	FROM ".$FANNIE_TRANS_DB.$dbc->sep()."dlog as d left join MasterSuperDepts as t
	on d.department = t.dept_ID
	WHERE ".$dbc->datediff('tdate',$dbc->now())."=0
	AND (trans_type ='I' OR trans_type = 'D' or trans_type='M')
	AND t.superID > 0
	GROUP BY ".$dbc->hour('tdate')."
	order by ".$dbc->hour('tdate');
	$args = array($selected);
}

$prep = $dbc->prepare_statement($query1);
$result = $dbc->exec_statement($query1,$args);
echo "<div align=\"center\"><h1>Today's <span style=\"color:green;\">$name</span> Sales!</h1>";
echo "<table cellpadding=4 cellspacing=2>";
echo "<tr><td><b>Hour</b></td><td><b>Sales</b></td></tr>";
$sum = 0;
$sum2 = 0;
while($row=$dbc->fetch_row($result)){
	printf("<tr><td>%d</td><td>%.2f</td><td style=\"%s\">%.2f%%</td></tr>",
		$row[0],
		($selected==-1)?$row[1]:$row[2],
		($selected==-1)?'display:none;':'',	
		($selected==-1)?0.00:$row[2]/$row[1]*100);
	$sum += $row[1];
	if($selected != -1) $sum2 += $row[2];
}
echo "<tr><th width=60px align=left>Total</th><td>";
if ($selected != -1)
	echo "$sum2</td><td>".round($sum2/$sum*100,2)."%";
else
	echo $sum;
echo "</td></tr></table>";

echo "<p />";
echo "Also available: <select onchange=\"top.location='index.php?super='+this.value;\">";
foreach($supers as $k=>$v){
	echo "<option value=$k";
	if ($k == $selected)
		echo " selected";
	echo ">$v</option>";
}
echo "</select></div>";

include($FANNIE_ROOT.'src/footer.html');
?>
