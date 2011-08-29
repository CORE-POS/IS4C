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

$page_title = "Fannie :: Product Tools";
$header = "Import Departments";

include($FANNIE_ROOT.'src/header.html');

include($FANNIE_ROOT.'src/csv_parser.php');
include($FANNIE_ROOT.'src/tmp_dir.php');
if (isset($_REQUEST['MAX_FILE_SIZE']) ){
	// save new file
	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	$outfile = tempnam(sys_get_temp_dir(),"MIC");
	move_uploaded_file($tmpfile, $outfile);

	echo '<form action="subdept.php" method="post">';

	echo '<i>Preview: Select which columns contain desired information</i><br />';
	echo '<input type="checkbox" name="skip" /> First row contains headers (omit it)<br />';

	$preview = array();
	$fp = fopen($outfile,"r");
	while( ($line = fgets($fp)) !== False && count($preview) < 5)
		$preview[] = csv_parser($line);
	fclose($fp);

	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr>';
	echo '<th>SubDept #</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="sn" value="'.$i.($i==0?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Name</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="desc" value="'.$i.($i==1?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Dept #</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="dn" value="'.$i.($i==2?'" checked':'"').' /></td>';
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

	$sn_index = $_REQUEST['sn'];
	$desc_index = $_REQUEST['desc'];
	$dn_index = $_REQUEST['dn'];
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
		$dept_no = $line[$dn_index];
		$desc = $line[$desc_index];
		$subdept_no = $line[$sn_index];

		if (strlen($desc) > 30) $desc = substr($desc,0,30);

		$insQ = sprintf("INSERT INTO subdepts (subdept_no,subdept_name,dept_ID)
				VALUES (%d,%s,%d)",$subdept_no,$dbc->escape($desc),
				$dept_no);
		$dbc->query($insQ);
	}
	echo "Loaded requested subdepartments";
	fclose($fp);
	unlink($filename);
}
else {
?>
Upload a CSV file containing subdept numbers, names, and what department
number they belong to.
<form enctype="multipart/form-data" action="subdept.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
<?php
}
include($FANNIE_ROOT.'src/footer.html');
?>
