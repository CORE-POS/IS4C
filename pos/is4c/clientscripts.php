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
 

if (!function_exists("gohome")) include("maindisplay.php");

function lockscreen() {

	if ($_SESSION["timedlogout"] > 0) {
		if ($_SESSION["LastID"] == 0) {

			$timeout = $_SESSION["timedlogout"];
			setglobalvalue("LoggedIn", 0);
			echo "<SCRIPT type=\"text/javascript\">";
			echo "logout = setTimeout(\"window.top.location = 'login.php'\", ".$timeout.");\n";
			echo "</SCRIPT>";
			$intAway = 1;

			$_SESSION["training"] = 0;
		}
	} 
	else {

		if ($_SESSION["lockScreen"] != 0) {
			echo "<SCRIPT type=\"text/javascript\">\n";
			echo "lockScreen = setTimeout(\"window.location = 'login3.php'\", 180000);\n";
			echo "</SCRIPT>\n";
		}
		else {
			echo "<SCRIPT  type=\"text/javascript\">\n";
			echo "lockScreen = setTimeout(\"window.location = 'reload.php'\", 180000);\n";
			echo "</SCRIPT>\n";

		}
	}

}

function clearinput() {
	$_SESSION["msgrepeat"] = 0;
	$_SESSION["strendered"] = "";
	$_SESSION["strRemembered"] = "";
	$_SESSION["SNR"] = 0;
	$_SESSION["wgtRequested"] = 0;
	$_SESSION["refund"] = 0;	// added by apbw 6/04/05 to correct voiding of refunded items
	if ($_SESSION["tare"] > 0) {
		addtare(0);
	}
	lastpage();
}

function boxMsgscreen() {
	echo "<SCRIPT type=\"text/javascript\">";
	echo "window.location = '/boxMsg2.php';";
	echo "</SCRIPT>";
}


function ccboxMsgscreen(){

	echo "<SCRIPT type=\"text/javascript\">";
	echo "window.location = '/ccboxMsg2.php';";
	echo "</SCRIPT>";
}

function unlockscreen() {
	$_SESSION["scan"] = "scan";
	echo "<SCRIPT type=\"text/javascript\">";
	echo "window.top.frames(1).location = '/pos2.php';";
	echo "</SCRIPT>";
}

function qttyscreen() {

	echo "<SCRIPT type=\"text/javascript\">";
	echo "window.location = '/qtty2.php';";
	echo "</SCRIPT>";

}

function loginscreen() {
	gohome();
}

function moveto($url) {
	echo "<SCRIPT type=\"text/javascript\">\n";
	echo "window.location = '".$url."';\n";
	echo "</SCRIPT>\n";
}

function receipt($arg) {
	$_SESSION["receiptType"] = $arg;
	echo "<script language='javascript'>\n";
	echo "window.top.end.location  = 'end.php';\n";
	echo "</script>\n";
}

function endorseType() {

	echo "<script language='javascript'>\n";
	echo "window.top.endorse.location  = 'endorse.php';\n";
	echo "</script>\n";

}

?>
