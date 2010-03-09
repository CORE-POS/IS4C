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

if (!function_exists("printheaderb")) include("drawscreen.php");
if (!function_exists("getsubtotals")) include("connect.php");
if (!function_exists("addItem")) include("additem.php");
if (!function_exists("pDataConnect")) include("connect.php");
if (!function_exists("tender")) include("prehkeys.php");
if (!function_exists("maindisplay")) include("maindisplay.php");
if (!function_exists("boxMsgscreen")) include("clientscripts.php");
include("ccLib.php");



printheaderb();
$user= $_SESSION["CashierNo"];
$remote_oux = remote_oux();

$value = '=';
$track = $_SESSION["strRemembered"];

$equal = strpos($track,$value);
$equalFour = $equal-4;
$four = substr($track,$equalFour,4);

//check to see if the file exists

if (file_exists($remote_oux)){

	$today = date('mdYHis');
	$emp = $_SESSION['CashierNo'];
	$trans = $_SESSION['transno'];
	$stamp = $today."_".$emp."_".$trans;
	$local = 'OUX/'.$stamp;

	copy_file($remote_oux,$local);

	$amountIn = $_SESSION["ccAmt"];
	$_SESSION["ccAmt"] = 0;
	$_SESSION["ccAmtEntered"] = 0;
	$_SESSION["ouxWait"] = 0;

	//$fp = fopen($filename,'r'); //open the file with read permissions
	$fp = fopen($remote_oux,'r'); //fixed CvR 07/07/05 
	parse_oux();
	fclose($fp); //close the file

	$catTroutD = "$four" + "$TroutD";

	if ($Result == "CAPTURED") {
	        	
		$_SESSION["msgrepeat"] = 0;
		$_SESSION["ccTender"] = 1;
		tender("CC",  $amountIn * 100);
		$ccamt = $_SESSION["ccTotal"];

	    //-------added 04/01/05 by CvR---need to save TroutD to void it
		
		ccout($User1,$Result,$TroutD,$ccamt,$catTroutD);

	    //-----

		gohome();

	} elseif ($Result == "Error") {

		if($Reference == "Invalid Card Num"){

			$_SESSION["boxMsg"] = "Invalid card Number. Please try again<p>or [clear] to cancel";
				    //-------added 04/01/05 by CvR---need to save TroutD to void it

		ccout($User1,$Result,$TroutD,0,$catTroutD);

	    //-----

		} elseif ($Reference == "F+Card# to Force"){

			$_SESSION["boxMsg"] = "This appears to be a <br>duplicate transaction.<br>Enter 'F' before swiping<p>or [clear] to cancel";
			ccout($User1,$Result,$TroutD,0,$catTroutD);

		} else {
			if (strlen($Reference) > 0) {
				$errorInfo = "Ref: ".$Reference;
			} else {
				$errorInfo = "Auth: ".$Auth;
			}
			$_SESSION["boxMsg"] = "Error<br>".$errorInfo."<p>[clear]";
				    //-------added 04/01/05 by CvR---need to save TroutD to void it

		ccout($User1,$Result,$TroutD,0,$catTroutD);

	    //-----


		}

		$_SESSION["ccTender"]=0;
		boxMsgScreen();

  //-------added 04/02/05 by CvR---to void cc input...
	
	} elseif($Result == "VOIDED") {

		$_SESSION["msgrepeat"] = 0;
		$_SESSION["ccTender"] = 1;

		$cn = pDataConnect();

		$selCCOutQ = "SELECT * FROM CC_OUT";
		$selCCOutR = sql_query($selCCOutQ);
		$selCCOutW = sql_fetch_row($selCCOutR);

		tender("CC", $selCCOutW[3]*100);
		$ccamt = $_SESSION["ccTotal"];

		$trunCCOutQ = "TRUNCATE TABLE CC_OUT";
		$trunCCOutR = sql_query($trunCCOutQ, $cn);
   //-----

		gohome();

	} elseif ($Result == "NOT CAPTURED" && $Reference == "TRY AGAIN"){

		$_SESSION["ccTender"]=0;
		$_SESSION["boxMsg"] = "Communication error, please try again.<p>[clear]";
		boxMsgScreen();
		
			    //-------added 04/01/05 by CvR---need to save TroutD to void it

		ccout($User1,$Result,$TroutD,0,$catTroutD);

	    //-----

	}elseif($Result == "NOT CAPTURED"){

		$_SESSION["ccTender"]=0;

		if (strlen($Reference) > 0) {
			$errorInfo = "Ref: ".$Reference;
		} else {
			$errorInfo = "Auth: ".$Auth;
		}
		$_SESSION["boxMsg"] = "Unable to process<br>".$errorInfo."<p>[clear]";

	    //-------added 04/01/05 by CvR---need to save TroutD to void it

		ccout($User1,$Result,$TroutD,0,$catTroutD);

	    //-----


		boxMsgScreen();
	}

} else {
	if ($_SESSION["ouxWait"] < 5) {

		$_SESSION["ouxWait"]++;
		plainmsg("authorizing...");
		printfooter();

	} else {

		$_SESSION["ouxWait"] = 0;
		$_SESSION["ccTender"] = 0;
		$_SESSION["boxMsg"] = "Communication error<p><font size=-1>Unable to complete transaction<br>Please process card manually</font>";
		boxMsgScreen();
	}
}

?>

<HTML>
<META HTTP-EQUIV="Refresh" CONTENT="3;">

</HTML>

