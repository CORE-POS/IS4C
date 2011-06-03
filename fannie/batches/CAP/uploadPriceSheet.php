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

include('../../config.php');

if (isset($_POST['MAX_FILE_SIZE'])){
	$dh = opendir("tmp/");
	while (($file = readdir($dh)) !== false) {
		if (!is_dir("tmp/".$file)) unlink("tmp/".$file);
	}
	closedir($dh);

	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	if ($path_parts['extension'] == "zip"){
		move_uploaded_file($tmpfile,"tmp/CAP.zip");
		$output = system("unzip tmp/CAP.zip -d tmp/ &> /dev/null");
		unlink("tmp/CAP.zip");
		$dh = opendir("tmp/");
		while (($file = readdir($dh)) !== false) {
			if (!is_dir("tmp/".$file)) rename("tmp/".$file,"tmp/CAP.csv");
		}
		closedir($dh);
	}
	else {
		move_uploaded_file($tmpfile, "tmp/CAP.csv");
	}
	header("Location: loadSales.php");
}
else {

	/* html header, including navbar */
	$page_title = "Fannie - CAP sales";
	$header = "Upload CAP file";
	include($FANNIE_ROOT."src/header.html");
?>
<form enctype="multipart/form-data" action="uploadPriceSheet.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
</form>
<?php
	/* html footer */
	include($FANNIE_ROOT."src/footer.html");
}
?>
