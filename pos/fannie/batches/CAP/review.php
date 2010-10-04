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
			$ins = sprintf("INSERT INTO batches (startDate,endDate,
					batchName,batchType,discountType) 
					VALUES ('%s','%s',
					'%s Co+op %s',1,1)",$start,$end,
					$names[$i],$naming);
			$dbc->query($ins);
			$fetch = sprintf("SELECT max(batchID) from batches WHERE
					batchName='%s Co+op %s'",$names[$i],$naming);
			$fetch = $dbc->query($fetch);
			$bID = array_pop($dbc->fetch_row($fetch));
			$batchIDs[$names[$i]] = $bID;
		}
		$id = $batchIDs[$names[$i]];
		$insQ = sprintf("INSERT INTO batchList (upc,batchID,salePrice,active)
				VALUES ('%s',%d,%f,0)",$upcs[$i],$id,$prices[$i]);
		$dbc->query($insQ);
	}

	echo "New sales batches have been created!<p />";
	echo "<a href=\"../newbatch/\">View batches</a>";	
	include($FANNIE_ROOT."src/footer.html");
	exit;
}

$query = "SELECT t.upc,p.description,t.price,
        s.super_name as batch
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
