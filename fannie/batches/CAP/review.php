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

include("../../config.php");

require_once($FANNIE_ROOT.'src/mysql_connect.php');

$page_title = "Fannie - CAP sales";
$header = "Review Data";
include($FANNIE_ROOT."src/header.html");

if (isset($_POST["start"])){
	$start = $_POST["start"];
	$end = $_POST["end"];
	$naming = $_POST["naming"];
	$upcs = $_POST["upc"];
	$prices = $_POST["price"];
	$names = $_POST["batch"];
	$batchIDs = array();

	for($i=0;$i<count($upcs);$i++){
		if(!isset($batchIDs[$names[$i]])){
			$ins = array(
			'startDate' => "'$start'",
			'endDate' => "'$end'",
			'batchName' => "'{$names[$i]} Coop Deals $naming'",
			'batchType' => 1,
			'discountType' => 1,	
			'priority' => 0
			);
			$dbc->smart_insert('batches',$ins);
			$bID = $dbc->insert_id();
			$batchIDs[$names[$i]] = $bID;
		}
		$id = $batchIDs[$names[$i]];
		$bl = array(
		'upc' => "'{$upcs[$i]}'",
		'batchID' => $id,
		'salePrice' => sprintf("%.2f",$prices[$i]),
		'active' => 0
		);
		$dbc->smart_insert('batchList',$bl);
	}

	echo "New sales batches have been created!<p />";
	echo "<a href=\"../newbatch/\">View batches</a>";	
	include($FANNIE_ROOT."src/footer.html");
	exit;
}

$query = "SELECT t.upc,p.description,t.price,
        CASE WHEN s.super_name IS NULL THEN 'sale' ELSE s.super_name END as batch
        FROM tempCapPrices as t
        INNER JOIN products AS p
        on t.upc = p.upc LEFT JOIN
	MasterSuperDepts AS s
	ON p.department=s.dept_ID
	ORDER BY s.super_name,t.upc";
$result = $dbc->query($query);

echo "<script type=\"text/javascript\" src=\"".$FANNIE_URL."src/CalendarControl.js\">
</script>";
echo "<form action=review.php method=post>
<table cellpadding=4 cellspacing=0 border=1>
<tr><th>UPC</th><th>Desc</th><th>Sale Price</th><th>Batch</th></tr>";
while($row = $dbc->fetch_row($result)){
	printf("<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%s</tr>",
		$row[0],$row[1],$row[2],$row[3]);
	printf("<input type=hidden name=upc[] value=\"%s\" />
		<input type=hidden name=price[] value=\"%s\" />
		<input type=hidden name=batch[] value=\"%s\" />",
		$row[0],$row[2],$row[3]);
}
echo "</table><p />
<table cellpadding=4 cellspacing=0><tr>
<td><b>Start</b></td><td><input type=text name=start onclick=\"showCalendarControl(this);\" /></td>
</tr><tr>
<td><b>End</b></td><td><input type=text name=end onclick=\"showCalendarControl(this);\" /></td>
</tr><tr>
<td><b>Batch Naming</b></td><td><input type=text name=naming /></td>
</tr></table>
<input type=submit value=\"Create Batch(es)\" />
</form>";

include($FANNIE_ROOT."src/footer.html");
?>
