<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
?>
<body bgcolor="#eeeeee">
<?

/*
$chkScale = exec("tail -3 /pos/is4c/rs232/scale", $aChkScale);
$scaleData = $aChkScale[0];

$chkScanner = exec("tail -3 /pos/is4c/rs232/scanner", $aChkScanner);
$scanData = $aChkScanner[0];
*/

$scaleFile = "/pos/is4c/rs232/scale";
$scaleHandle = fopen($scaleFile, "r");
$scaleData = rtrim(fread($scaleHandle, filesize($scaleFile)));
fclose($scaleHandle);

$scanFile = "/pos/is4c/rs232/scanner";
$scanHandle = fopen($scanFile, "r");
$scanData = rtrim(fread($scanHandle, filesize($scanFile)));
fclose($scanHandle);

if (strlen($scanData) > 9) {
	$clearScanner = exec("echo '' > /pos/is4c/rs232/scanner", $aClearScanner);
	

	echo "<SCRIPT language='javascript'>\n";
	echo "var inputVal = window.top.input.document.form.reginput.value;";

	echo "window.top.input.document.form.reginput.value =inputVal+'".$scanData."';\n";
	echo "window.top.input.document.form.submit();\n";

	echo "</SCRIPT>";


}

if ($scaleData != $_SESSION["lastscale"]) {

	$_SESSION["lastscale"] = $scaleData;
	echo "<SCRIPT language='javascript'>\n";
	echo "window.top.scale.document.form.reginput.value = '".$scaleData."';\n";
	echo "window.top.scale.document.form.submit();\n";

	echo "</SCRIPT>";


}

?>

</body>
<HTML>
<META HTTP-EQUIV="Refresh" CONTENT="100;">

</HTML>
