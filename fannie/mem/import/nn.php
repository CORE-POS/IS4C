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
$header = "Import Member Names &amp; Numbers";

include($FANNIE_ROOT.'src/header.html');

include($FANNIE_ROOT.'src/csv_parser.php');
include($FANNIE_ROOT.'src/tmp_dir.php');
if (isset($_REQUEST['MAX_FILE_SIZE']) ){
	// save new file
	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	$outfile = tempnam(sys_get_temp_dir(),"MIC");
	move_uploaded_file($tmpfile, $outfile);

	echo '<form action="nn.php" method="post">';

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
	echo '<th>First Name</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="fn" value="'.$i.($i==1?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Last Name</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="ln" value="'.$i.($i==2?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Type</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="typeID" value="'.$i.'" /></td>';
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
	$defaults_table = array();
	$defQ = "SELECT memtype,cd_type,discount,staff,SSI from memdefaults";
	$defR = $dbc->query($defQ);
	while($defW = $dbc->fetch_row($defR)){
		$defaults_table[$defW['memtype']] = array(
			'type' => $defW['cd_type'],
			'discount' => $defW['discount'],
			'staff' => $defW['staff'],
			'SSI' => $defW['SSI']
		);
	}

	$mn_index = $_REQUEST['memnum'];
	$fn_index = $_REQUEST['fn'];
	$ln_index = $_REQUEST['ln'];
	$t_index = isset($_REQUEST['typeID'])?$_REQUEST['typeID']:False;
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

		// get info from file and member-type default settings
		// if applicable
		$cardno = $line[$mn_index];
		$ln = $line[$ln_index];
		$fn = $line[$fn_index];	
		$mtype = ($t_index !== False) ? $line[$t_index] : 0;
		$type = "PC";
		$discount = 0;
		$staff = 0;
		$SSI = 0;
		if ($t_index !== False){
			if (isset($defaults_table[$mtype]['type']))
				$type = $defaults_table[$mtype]['type'];
			if (isset($defaults_table[$mtype]['discount']))
				$discount = $defaults_table[$mtype]['discount'];
			if (isset($defaults_table[$mtype]['staff']))
				$staff = $defaults_table[$mtype]['staff'];
			if (isset($defaults_table[$mtype]['SSI']))
				$SSI = $defaults_table[$mtype]['SSI'];
		}

		// determine person number
		$perQ = sprintf("SELECT MAX(personNum) FROM custdata WHERE CardNo=%d",$cardno);
		$perR = $dbc->query($perQ);
		$result = array_pop($dbc->fetch_row($perR));
		$pn = !empty($result) ? ($result+1) : 1;

		$insQ = sprintf("INSERT INTO custdata (CardNo,personNum,LastName,FirstName,CashBack,
			Balance,Discount,MemDiscountLimit,ChargeOk,WriteChecks,StoreCoupons,Type,
			memType,staff,SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown)
			VALUES (%d,%d,%s,%s,0,0,%d,0,0,0,0,%s,%d,%d,%d,0,0,0,%s,1)",
			$cardno,$pn,$dbc->escape($ln),$dbc->escape($fn),$discount,
			$dbc->escape($type),$staff,$SSI,$dbc->escape($cardno." ".$ln));
		$insR = $dbc->query($insQ);

		if ($insR === False){
			echo "<b>Error importing member $cardno ($fn $ln)</b><br />";
		}
		else {
			echo "Imported member $cardno ($fn $ln)<br />";
		}

		if ($pn == 1){
			$memInfoQ = sprintf("INSERT INTO meminfo(card_no,last_name,first_name,othlast_name,
				othfirst_name,street,city,state,zip,phone,email_1,email_2,ads_OK) VALUES
				(%d,'','','','','','','','','','','',1)",$cardno);;
			$dbc->query($memInfoQ);

			$memDatesQ = sprintf("INSERT INTO memDates (card_no,start_date,end_date) VALUES
					(%d,NULL,NULL)",$cardno);
			$dbc->query($memDatesQ);
		}
		
	}
	fclose($fp);
	unlink($filename);
}
else {
?>
Upload a CSV file containing member numbers, names, and optionally types.
<form enctype="multipart/form-data" action="nn.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
<?php
}
include($FANNIE_ROOT.'src/footer.html');
?>
