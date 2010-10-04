<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

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

$page_title = 'Fannie - Batch Module';
$header = 'Item Batcher';
include('../src/header.html');

$_SESSION['batchID'] = 1;

include_once('../src/mysql_connect.php');

$batchListQ= "SELECT active,batchID,batchName,batchType,DATE(startDate) as startDate,endDate 
	FROM batches
	ORDER BY batchID DESC";

$batchListR = mysql_query($batchListQ);

$maxBatchQ = "SELECT max(batchID) FROM batches";
$maxBatchR = mysql_query($maxBatchQ);
$maxBatchW = mysql_fetch_row($maxBatchR);
$newBatch = $maxBatchW[0] + 1;
// $newBatchID = $newBatch; 
// global $newBatchID;

if (!isset($_POST["showinactive"])) {$_POST["showinactive"] = "hide";}
echo '<form action="index.php" method="POST">';
if ($_POST['showinactive'] == 'hide') {
        echo '<p align="center"><BUTTON name=showinactive type=submit value="show">Show Inactive Batches</BUTTON></p>';
} elseif ($_POST['showinactive'] == 'show') {
        echo '<p align="center"><BUTTON name=showinactive type=submit value="hide">Hide Inactive Batches</BUTTON></p>';
}
echo '</form>';

?>

<form name='addBatch' action='display.php?batchID=<?php echo $newBatch; ?>' method='POST' target=_blank>
	<table>
		<tr>
			<td>&nbsp;</td>
			<td>Batch Name</td>
			<td>Start Date</td>
			<td>End Date</td>
		</tr>
		<tr>
			<td>&nbsp;
				<select name=batchType>
		        	<option value=1>CAP Sale</option>
		        	<option value=1>Regular Sale</option>
		        	<option value=1>Price Change</option>
				</select>
			</td>
			<td><input type=text name=batchName></td>
	     	<td><input name="startDate" onfocus="showCalendarControl(this);" type="text" size=10></td>
	     	<td><input name="endDate" onfocus="showCalendarControl(this);" type="text" size=10></td>
	     	<td><input type=submit name=submit value=Add></td>
		</tr>
	</table>
</form>

<?php

// echo "<p><b>ADD BATCH FEATURE IS DOWN FOR SERVICE.  Check back soon!</b></p>";
echo "<table border=0 cellspacing=0 cellpadding=5 width=90%>";
echo "<th>&nbsp;<th>Batch Name<th>Batch Type<th>Start Date<th>End Date";
$bg = '#eeeeee';
$date = DATE('Y-m-d');
while($batchListW = mysql_fetch_array($batchListR)){
   	$start = $batchListW['startDate'];
   	$end = $batchListW['endDate'];
   	if ($_POST['showinactive'] == 'show') {
		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
		echo "<tr bgcolor='$bg'>";
		echo "<td align=center>";
		if ($batchListW['active'] == 1) { 
			echo "<a href=batchOFF.php?batchID=" . $batchListW['batchID'] . "
				title='Click to turn OFF'><font color=green>ON</font></a>";
		} else {
			echo "<a href=batchON.php?batchID=" . $batchListW['batchID'] . "
				title='Click to turn ON'><font color=red>OFF</font></a>";
		}
		echo "</td><td><a href=display.php?batchID=" . $batchListW['batchID'] . " target=_blank>";
	   	echo $batchListW['batchName'] . "</a></td>";
		echo "<td align=center>";
		switch ($batchListW['batchType']) {
			case '1':
				echo "CAP";
				break;
			case '2':
				echo "sale";
				break;
			case '3':
				echo "change";
				break;
		}
	   	echo "</td><td>" . $batchListW['startDate'] . "</td>";
	   	echo "<td>" . $batchListW['endDate'] . "</td>";
		echo "</tr>";
	} elseif (($_POST['showinactive'] == 'hide') && ($batchListW['active'] == 1)) {
		$bg = ($bg=='#eeeeee' ? '#ffffff' : '#eeeeee'); // Switch the background color.
		echo "<tr bgcolor='$bg'>";
		echo "<td align=center>";
		echo "<a href=batchOFF.php?batchID=" . $batchListW['batchID'] . "
				title='click to turn OFF'><font color=green>ON</font></a>";


//	--	TODO : Browser dialogue to turn batches on and off		
//		echo "<a onclick="return confirm('Really remove Batch?')" class="admin" href=batches/>ON</a>";
		
		echo "<td><a href=display.php?batchID=" . $batchListW['batchID'] . " target=_blank>";
	   	echo $batchListW['batchName'] . "</a></td>";
		echo "<td align=center>";
		switch ($batchListW['batchType']) {
			case '1':
				echo "CAP";
				break;
			case '2':
				echo "sale";
				break;
			case '3':
				echo "change";
				break;
		}
	   	echo "</td><td>" . $batchListW['startDate'] . "</td>";
	   	echo "<td>" . $batchListW['endDate'] . "</td>";
		echo "</td></tr>";
	}
}
echo "</table>";
include('../src/footer.html');

?>
