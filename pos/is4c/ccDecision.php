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
 // session_start();

if (!function_exists("ccXML")) include_once("ccLib.php");
if (!function_exists("boxMsgscreen")) include_once("clientscripts.php");



$decision = strtoupper(trim($_POST["input"]));

if ($decision == "CL") {
	$_SESSION["msgrepeat"] = 0;
	$_SESSION["toggletax"] = 0;
	$_SESSION["chargetender"] = 0;
	$_SESSION["endorseType"] = 0;
	$_SESSION["togglefoodstamp"] = 0;
	$_SESSION["ccAmtEntered"] = 0;
	$_SESSION["ccAmt"] = 0;

	header("Location:/pos2.php");
}

elseif (strlen($decision) > 0) {

	$_SESSION["ccAmt"] = $decision;

	if (is_numeric($decision)) $_SESSION["ccAmt"] = $decision/100;

	$_SESSION["ccAmtEntered"] = 1;
	$_SESSION["strRemembered"] = $_SESSION["strEntered"];
	$_SESSION["msgrepeat"] = 1;	
	header("Location:/pos2.php");
}

elseif (strlen($decision) == 0 && $_SESSION["ccAmtInvalid"] == 1) {

	$_SESSION["ccAmt"] = "invalid";
	$_SESSION["ccAmtInvalid"] = 0;
	$_SESSION["ccAmtEntered"] = 1;	
	$_SESSION["strRemembered"] = $_SESSION["strEntered"];
	$_SESSION["msgrepeat"] = 1;	
	header("Location:/pos2.php");

}
else {

	$inxUploaded = ccXML();

	if ($inxUploaded == 1) {
		header("Location:/ccauthorize.php");
	} else {
		$_SESSION["boxMsg"] = "Communication error<p><font size=-1>Unable to complete transaction<br>Please process card manually</font>";
		boxMsgScreen();
	}
		
}
?>