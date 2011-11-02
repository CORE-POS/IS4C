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

/* configuration for your module - Important */
include("../../config.php");
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/tmp_dir.php');

if (isset($_POST['MAX_FILE_SIZE']) && $_REQUEST['vendorPage'] != ""){
	$tpath = sys_get_temp_dir()."/vendorupload/";
	if (!is_dir($tpath)) mkdir($tpath);
	$dh = opendir($tpath);
	while (($file = readdir($dh)) !== false) {
		if (!is_dir($tpath.$file)) unlink($tpath.$file);
	}
	closedir($dh);

	if ($_FILES['upload']['error'] != UPLOAD_ERR_OK){
		echo "Error uploading file<br />";
		echo "Error code is: ".$_FILES['upload']['error'].'<br />';
		echo '<a href="http://www.php.net/manual/en/features.file-upload.errors.php">Details</a>';
		exit;
	}

	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	if ($path_parts['extension'] == "zip"){
		move_uploaded_file($tmpfile,$tpath."unfi.zip");
		$output = system("unzip {$tpath}unfi.zip -d $tpath &> /dev/null");
		unlink($tpath."unfi.zip");
		$dh = opendir($tpath);
		while (($file = readdir($dh)) !== false) {
			if (!is_dir($tpath.$file)) rename($tpath.$file,$tpath."unfi.csv");
		}
		closedir($dh);
	}
	else {
		$out = move_uploaded_file($tmpfile, $tpath."unfi.csv");
	}
	header("Location: load-scripts/".$_REQUEST['vendorPage']);
}
else {

	/* html header, including navbar */
	$page_title = "Fannie - UNFI Price File";
	$header = "Upload UNFI Pricing File";
	include($FANNIE_ROOT.'src/header.html');

	if (isset($_REQUEST['vendorPage']))
		echo "<i>Error: no vendor selected</i><br />";

	$opts = '<option value="">Select a vendor</option>';
	$q = "select v.vendorName,l.loadScript from 
		vendors as v inner join
		vendorLoadScripts as l
		on v.vendorID=l.vendorID
		order by v.vendorName";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		$opts .= sprintf('<option value="%s">%s</option>',
			$w['loadScript'],$w['vendorName']);
	}
?>
<form enctype="multipart/form-data" action="uploadPriceSheet.php" method="post">
Vendor: <select name=vendorPage>
<?php echo $opts; ?>
</select><br />
<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>
<?php
	/* html footer */
	include($FANNIE_ROOT.'src/footer.html');
}
?>
