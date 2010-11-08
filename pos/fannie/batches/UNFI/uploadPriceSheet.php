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

if (isset($_POST['MAX_FILE_SIZE'])){
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
?>
<form enctype="multipart/form-data" action="uploadPriceSheet.php" method="post">
Vendor: <select name=vendorPage>
<option value="loadUNFIprices.php">UNFI</option>
<option value="loadSELECTprices.php">SELECT</option>
<option value="loadNPATHprices.php">NATURES PATH</option>
<option value="loadOWHprices.php">OREGONS WILD HARVEST</option>
<option value="loadECLECTICprices.php">ECLECTIC</option>
<option value="loadVITAMERprices.php">VITAMER</option>
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
