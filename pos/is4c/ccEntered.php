<?
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

include_once("ccLib.php");
include_once("lib.php");



function ccEntered($entered) {

	if ($_SESSION["ttlflag"] != 1) {
		boxMsg("transaction must be totaled before tender can be accepted");		
	}
	elseif ($_SESSION["CCintegrate"] != 1) {
		xBoxMsg("<img src='graphics/redsquare.gif'> System not initiated<P><font size=-1>Please process card<br>in standalone</font>");
	}
	elseif (pinghost($_SESSION["ccServer"]) != 1) {
		xBoxMsg("<img src='graphics/redsquare.gif'> Local system offline<P><font size=-1>Please process card<br>in standalone</font>");
	}
	elseif (sys_pcc() == 1) {
		xBoxMsg("<img src='graphics/redsquare.gif'> Remote system offline<P><font size=-1>Please process card<br>in standalone</font>");
	}
	else {
		$ccValid = ccValid($entered);

		if ($ccValid == 0 && $_SESSION["ccSwipe"] == "invalid") {
			// changed the error message since this usually just means a misread, not necessarily an invalid card --atf 5/16/07
			xBoxMsg("Card data invalid; scan again or type in manually<p><font size=-1>[clear] to cancel</font>");
		}
		elseif ($ccValid == 0 && $_SESSION["ccType"] = "Unsupported") {
			xBoxMsg("Card type not supported<p><font size=-1>[clear] to cancel</font>");
		}
		else {
		
			if ($_SESSION["ccAmtEntered"] != 1) {
				$_SESSION["ccAmt"] = $_SESSION["amtdue"];
			} 
			$ccAmt = $_SESSION["ccAmt"];

			if(substr($_SESSION['strEntered'],0,1) == 'V'|| substr($_SESSION['strEntered'],0,1) == 'v'){ // added 04/01/05 by CvR process void....not fully implemented
				$_SESSION["boxMsg"] = "<b>Voiding credit card amount<p><FONT size='-1'>[enter] to continue<br>or [clear] to cancel</FONT>";	
			}/*elseif($_SESSION["ccTotal"] != 0){ // added 04/01/05 CvR
				$_SESSION["boxMsg"] = "<b>Only one credit card charge per transaction</b><p><FONT size='-1'>press [clear] to cancel</FONT>";  // added 04/01/05 CvR
				$_SESSION["ccAmtInvalid"] = 1; // added 04/01/05 CvR
			}*/elseif((substr($_SESSION["strEntered"],0,1) == 'f' || substr($_SESSION["strEntered"],0,1) == 'F') && ($ccAmt <= $_SESSION["amtdue"])){
				$_SESSION["boxMsg"] = "<b>Forcing $".truncate2($ccAmt)."?</b><p><FONT size='-1'>[enter] to continue <br>or [clear] to cancel</FONT>";
				$_SESSION["ccAmtInvalid"] = 0;		
			}elseif(is_numeric($ccAmt) && ($ccAmt <= $_SESSION["amtdue"])) {
				$_SESSION["boxMsg"] = "<b>Tendering $".truncate2($ccAmt)."?</b><p><FONT size='-1'>[enter] to continue if correct<br>Enter a different amount if incorrect<br>or [clear] to cancel</FONT>";
				$_SESSION["ccAmtInvalid"] = 0;
			} else {

				$_SESSION["boxMsg"] = "<b>Invalid entry</b><p><FONT size='-1'>Enter a different amount<br>or [clear] to cancel</FONT>";
				$_SESSION["ccAmtInvalid"] = 1;
			}
			ccboxMsgscreen();
		}
	}
}
?>
