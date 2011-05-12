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

/* configuration for your module - Important */
include("../../config.php");
include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_POST['MAX_FILE_SIZE']) && $_REQUEST['vendorPage'] != ""){
	$dh = opendir("tmp/");
	while (($file = readdir($dh)) !== false) {
		if (!is_dir("tmp/".$file)) unlink("tmp/".$file);
	}
	closedir($dh);

	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	if ($path_parts['extension'] == "zip"){
		move_uploaded_file($tmpfile,"tmp/unfi.zip");
		$output = system("unzip tmp/unfi.zip -d tmp/ &> /dev/null");
		unlink("tmp/unfi.zip");
		$dh = opendir("tmp/");
		while (($file = readdir($dh)) !== false) {
			if (!is_dir("tmp/".$file)) rename("tmp/".$file,"tmp/unfi.csv");
		}
		closedir($dh);
	}
	else {
		move_uploaded_file($tmpfile, "tmp/unfi.csv");
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

	if (!is_writable("tmp/")){
		echo '<p><span style="color:red">Warning: tmp directory is not
			writeable. Uploads will not work</span></p>';
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
