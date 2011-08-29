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

$page_title = "Fannie :: Member Tools";
$header = "Import Existing Member Equity";

include($FANNIE_ROOT.'src/header.html');

include($FANNIE_ROOT.'src/csv_parser.php');
include($FANNIE_ROOT.'src/tmp_dir.php');
if (isset($_REQUEST['MAX_FILE_SIZE']) ){
	// save new file
	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	$outfile = tempnam(sys_get_temp_dir(),"MIC");
	move_uploaded_file($tmpfile, $outfile);

	echo '<form action="equity.php" method="post">';

	echo '<i>Preview: Select which columns contain desired information</i><br />';
	echo '<input type="checkbox" name="skip" /> First row contains headers (omit it)<br />';

	$preview = array();
	$fp = fopen($outfile,"r");
	while( ($line = fgets($fp)) !== False && count($preview) < 5)
		$preview[] = csv_parser($line);
	fclose($fp);

	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr>';
	echo '<th>Member Number</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="memnum" value="'.$i.($i==0?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Equity Amt</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="amt" value="'.$i.($i==1?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Date</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="date" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Transaction ID</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="transID" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<th>Dept. #</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="dept" value="'.$i.'" /></td>';
	echo '</tr>';
	foreach($preview as $p){
		echo '<tr><td>&nbsp;</td>';
		foreach($p as $entry) echo '<td>'.$entry.'</td>';
		echo '</tr>';
	}
	echo '</table><br />';
	printf('<input type="hidden" name="ufile" value="%s" />',base64_encode($outfile));
	echo '<input type="submit" value="Import Data" name="importbutton" />';
	echo '</form>';
}
else if (isset($_REQUEST['importbutton'])){
	include($FANNIE_ROOT.'src/mysql_connect.php');

	$mn_index = $_REQUEST['memnum'];
	$amt_index = $_REQUEST['amt'];
	$date_index = isset($_REQUEST['date'])?$_REQUEST['date']:False;
	$dept_index = isset($_REQUEST['dept'])?$_REQUEST['dept']:False;
	$trans_index = isset($_REQUEST['transID'])?$_REQUEST['transID']:False;
	$skip_one = isset($_REQUEST['skip'])?True:False;

	$filename = base64_decode($_REQUEST['ufile']);

	$fp = fopen($filename,'r');
	echo "Results: <br />";
	while( ($line = fgets($fp)) !== False ){
		// skip header row
		if ($skip_one){
			$skip_one = False;
			continue;
		}

		$line = csv_parser($line);

		$cardno = $line[$mn_index];
		$amt = $line[$amt_index];
		$date = ($date_index !== False) ? $line[$date_index] : '0000-00-00';
		$dept = ($dept_index !== False) ? $line[$dept_index] : 0;	
		$trans = ($trans_index !== False) ? $line[$trans_index] : "";

		$insQ = sprintf("INSERT INTO stockpurchases card_no,stockPurchase,
				tdate,trans_num,dept) VALUES (%d,%.2f,%s,%s,%d)",
				$cardno,$amt,$dbc->escape($date),
				$dbc->escape($trans),$dept);
		$insR = $dbc->query($insQ);

		if ($insR === False){
			echo "<b>Error importing member $cardno ($fn $ln)</b><br />";
		}
		else {
			echo "Imported member $cardno ($fn $ln)<br />";
		}
	}
	fclose($fp);
	unlink($filename);
}
else {
?>
Upload a CSV file containing member numbers and equity purchase amounts. 
Optionally, you can include dates (YYYY-MM-DD), department numbers, and
transaction identifiers.
<form enctype="multipart/form-data" action="equity.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
<?php
}
include($FANNIE_ROOT.'src/footer.html');
?>
