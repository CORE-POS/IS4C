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

	echo '<form action="dept.php" method="post">';

	echo '<i>Preview: Select which columns contain desired information</i><br />';
	echo '<input type="checkbox" name="skip" /> First row contains headers (omit it)<br />';

	$preview = array();
	$fp = fopen($outfile,"r");
	while( ($line = fgets($fp)) !== False && count($preview) < 5)
		$preview[] = csv_parser($line);
	fclose($fp);

	echo '<table cellspacing="0" cellpadding="4" border="1">';
	echo '<tr>';
	echo '<th>Dept #</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="dept_no" value="'.$i.($i==0?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Name</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="desc" value="'.$i.($i==1?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Margin</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="margin" value="'.$i.($i==2?'" checked':'"').' /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>Tax</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="tax" value="'.$i.'" /></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<th>FS</th>';
	for($i=0;$i<count($preview[0]);$i++)
		echo '<td><input type="radio" name="fs" value="'.$i.'" /></td>';
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

	$dn_index = $_REQUEST['dept_no'];
	$desc_index = $_REQUEST['desc'];
	$margin_index = $_REQUEST['margin'];
	$tax_index = isset($_REQUEST['tax'])?$_REQUEST['tax']:False;
	$fs_index = isset($_REQUEST['fs'])?$_REQUEST['fs']:False;
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
		$margin = $line[$margin_index];	
		if ($margin > 1) $margin /= 100.00;
		$tax = ($tax_index !== False) ? $line[$tax_index] : 0;
		$fs = ($fs_index !== False) ? $line[$fs_index] : 0;

		if (strlen($desc) > 30) $desc = substr($desc,0,30);

		$insQ = sprintf("INSERT INTO departments (dept_no,dept_name,dept_tax,dept_fs,
				dept_limit,dept_minimum,dept_discount,modified,modifiedby)
				VALUES (%d,%s,%d,%d,50.00,0.01,1,%s,1)",$dept_no,
				$dbc->escape($desc),$tax,$fs,$dbc->now());
		$dbc->query($insQ);

		$insQ = sprintf("INSERT INTO deptMargin (dept_ID,margin) VALUES (%d,%f)",
				$dept_no,$margin);
		$dbc->query($insQ);

		$insQ = sprintf("INSERT INTO deptSalesCodes (dept_ID,salesCode) VALUES (%d,%d)",
				$dept_no,$dept_no);
		$dbc->query($insQ);
	}
	echo "Loaded requested departments";
	fclose($fp);
	unlink($filename);
}
else {
?>
Upload a CSV file containing departments numbers, descriptions, margins,
and optional tax/foodstamp settings. Unless you know better, use zero and
one for tax and foodstamp columns.
<form enctype="multipart/form-data" action="dept.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
<?php
}
include($FANNIE_ROOT.'src/footer.html');
?>
