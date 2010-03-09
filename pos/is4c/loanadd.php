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

if (!function_exists("addloan")) include("additem.php");
if (!function_exists("printheaderb")) include("drawscreen.php");
if (!function_exists("gohome")) include("maindisplay.php");
if (!function_exists("drawerKick")) include("printLib.php");
if (!function_exists("receipt")) include("clientscripts.php");
include_once("prehkeys.php");


if (isset($_POST["loan"])) {
	$entered = strtoupper(trim($_POST["loan"]));
} else {
	$entered = "";
}

$_SESSION["away"] = 1;

if (!$entered || strlen($entered) < 1) gohome();

if (!is_numeric($entered) || (strpbrk($entered,".") == true)) {
	echo "<BODY onLoad='document.forms[0].elements[0].focus();'>\n";
	printheaderb();
	loanenter("Invalid entry<br>Try again (hint: 15000 = $150)");
}
else {
?>
	<html>
	<body onload="window.top.input.location = 'input.php';document.form.submit();" >
	<?php

	$_SESSION["loanTime"] = 'time(Y-m-d H:i:s)';

	$db = pDataConnect();
	$query = "SELECT id FROM custdata WHERE CardNo = 99999";
	$result = sql_query($query,$db);
	$nonmemID = mysql_result($result,0);

	setMember($nonmemID);
	ttl();


	echo "<FORM name='form' method='post' autocomplete='off' action='pos2.php'>";
	echo "<INPUT name='input' type='hidden' value='".$entered."LN'>";
	echo "</FORM>";
	echo "</body></html>";


//	$loanamt = $entered / 100;
//	addloan($loanamt);
//	setMember(99999,1);
//	ttl();
//	$_SESSION["runningTotal"] = $_SESSION["amtdue"];
//	tender("LN", $_SESSION["runningTotal"] * 100);
//	$_SESSION["away"] = 0;
//	receipt("loan");
//	drawerKick();
//	gohome();
}

$_SESSION["scan"] = "noScan";
$_SESSION["beep"] = "noBeep";
printfooter();
?>
