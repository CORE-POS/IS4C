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
 
<html>
<body>
<FORM name='Form1' method='post' action='/pos2.php'>
    <INPUT Type='hidden' name='input' value=''>
</FORM>

<?php
if (!function_exists("endorseType")) include_once("clientscripts.php");		// apbw 03/24/05 Wedge Printer Swap Patch

$decision = strtoupper(trim($_POST["input"]));

if ($decision == "CL") {
	$_SESSION["msgrepeat"] = 0;
	$_SESSION["toggletax"] = 0;
	$_SESSION["chargetender"] = 0;
	$_SESSION["togglefoodstamp"] = 0;
	$_SESSION["endorseType"] = "";
/*	header("Location:/pos2.php");*/
	echo "<script language='javascript'>";
	echo "window.location = '/pos2.php';";
	echo "</script>";
}

elseif (strlen($decision) > 0) {

	$_SESSION["msgrepeat"] = 0;

	echo "<SCRIPT language=\"javascript\">"
		."document.Form1.input.value=\"".$_SESSION["strEntered"]."\";"
		."document.Form1.submit();"
		."</SCRIPT>";
} else {

	endorseType();

	echo "<SCRIPT language=\"javascript\">"
		."document.Form1.input.value=\"".$_SESSION["strEntered"]."\";"
		."document.Form1.submit();"
		."</SCRIPT>";
}

?>
</body>
</html>
