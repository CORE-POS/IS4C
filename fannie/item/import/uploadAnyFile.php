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

/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 + Upload a file from workstation to $temp_dir/misc/
*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 *  2Sep2012 Eric Lee Based on /IS4C/fannie/batches/UNFI/uploadPriceSheet.php
*/

/* configuration for your module - Important */
include("../../config.php");
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'src/tmp_dir.php');

$tpath = sys_get_temp_dir()."/misc/";

/* Is this a request-to-upload or an initial display of the form? */
if (isset($_POST['MAX_FILE_SIZE']) && $_REQUEST['doUpload'] != ""){
	if (!is_dir($tpath)) mkdir($tpath);
	/* rm any files in $tpath - probably not a good idea
	$dh = opendir($tpath);
	while (($file = readdir($dh)) !== false) {
		if (!is_dir($tpath.$file)) unlink($tpath.$file);
	}
	closedir($dh);
	*/

	if ($_FILES['upload']['error'] != UPLOAD_ERR_OK){
		echo "Error uploading file<br />";
		echo "Error code is: ".$_FILES['upload']['error'].'<br />';
		echo '<a href="http://www.php.net/manual/en/features.file-upload.errors.php">Details</a>';
		exit;
	}


	$tmpfile = $_FILES['upload']['tmp_name'];
	$path_parts = pathinfo($_FILES['upload']['name']);
	/* Could base a routine for upzipping an uploaded .zip on this,
	    which as it stands is specialized for a UNFI price update.
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
	else { Not a .zip }
	*/

	$out = move_uploaded_file($tmpfile, $tpath."{$path_parts['basename']}");
	echo "Done. File is in $tpath{$path_parts['basename']}<br />";
	echo "This directory may be deleted on reboot.<br />";

}
else {

	/* html header, including navbar */
	$page_title = "Fannie - Upload Any File";
	$header = "Upload Any File";
	include($FANNIE_ROOT.'src/header.html');
?>
<form enctype="multipart/form-data" action="uploadAnyFile.php" method="post">
<input type="hidden" name="MAX_FILE_SIZE" value="20971520" />
<input type="hidden" name="doUpload" value="x" />
Filename: <input type="file" id="file" name="upload" />
<input type="submit" value="Upload File" />
<br />Best if the file is <2MB.
<br />The file will be placed in <?php echo "$tpath" ?>
</form>
<?php
	/* html footer */
	include($FANNIE_ROOT.'src/footer.html');
}
?>
