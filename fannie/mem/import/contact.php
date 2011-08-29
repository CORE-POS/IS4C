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
$header = "Import Member Contact Info";

include($FANNIE_ROOT.'src/header.html');

include($FANNIE_ROOT.'src/csv_parser.php');
include($FANNIE_ROOT.'src/tmp_dir.php');
if (isset($_REQUEST['MAX_FILE_SIZE']) ){

	// save new file
	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	$outfile = tempnam(sys_get_temp_dir(),"MIC");
	move_uploaded_file($tmpfile, $outfile);

	echo '<form action="contact.php" method="post">';

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
	echo '<th>Street Address</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="street" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>2nd Address Line</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="street2" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>City</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="city" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>State</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="state" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Zip</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="zip" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Phone #</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="ph1" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Alt. Phone #</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="ph2" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Email</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="email" value="'.$i.'" /></td>';
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
	$st_index = isset($_REQUEST['street'])?$_REQUEST['street']:False;
	$st2_index = isset($_REQUEST['street2'])?$_REQUEST['street2']:False;
	$city_index = isset($_REQUEST['city'])?$_REQUEST['city']:False;
	$state_index = isset($_REQUEST['state'])?$_REQUEST['state']:False;
	$zip_index = isset($_REQUEST['zip'])?$_REQUEST['zip']:False;
	$ph_index = isset($_REQUEST['ph1'])?$_REQUEST['ph1']:False;
	$ph2_index = isset($_REQUEST['ph2'])?$_REQUEST['ph2']:False;
	$email_index = isset($_REQUEST['email'])?$_REQUEST['email']:False;
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

		// get info from file and member-type default settings
		// if applicable
		$cardno = $line[$mn_index];
		$street = ($st_index !== False) ? $line[$st_index] : "";
		$street2 = ($st2_index !== False) ? $line[$st2_index] : "";
		$city = ($city_index !== False) ? $line[$city_index] : "";
		$state = ($state_index !== False) ? $line[$state_index] : "";
		$zip = ($zip_index !== False) ? $line[$zip_index] : "";
		$ph1 = ($ph_index !== False) ? $line[$ph_index] : "";
		$ph2 = ($ph2_index !== False) ? $line[$ph2_index] : "";
		$email = ($email_index !== False) ? $line[$email_index] : "";

		// combine multi-line addresses
		$full_street = !empty($street2) ? $street."\n".$street2 : $street;

		$upQ = sprintf("UPDATE meminfo SET
			street = %s,
			city = %s,
			state = %s,
			zip = %s,
			phone = %s,
			email_1 = %s,
			email_2 = %s
			WHERE card_no=%d",
			$dbc->escape($full_street),
			$dbc->escape($city),
			$dbc->escape($state),
			$dbc->escape($zip),
			$dbc->escape($ph1),
			$dbc->escape($email),
			$dbc->escape($ph2),
			$cardno);

		echo "Imported contact info for member $cardno<br />";
			
	}
	fclose($fp);
	unlink($filename);
}
else {
?>
Upload a CSV file containing member numbers and address/phone/email.
<form enctype="multipart/form-data" action="contact.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
<?php
}
include($FANNIE_ROOT.'src/footer.html');
?>
