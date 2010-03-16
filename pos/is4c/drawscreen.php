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

include_once("lib.php");

function printfooter() {

//	if ($_SESSION["msgrepeat"] != 1) {
		$_SESSION["runningTotal"] = $_SESSION["amtdue"];
//	}

	if ($_SESSION["End"] == 1) {
		$_SESSION["runningTotal"] = -1 * $_SESSION["change"];
	}
	
	if ($_SESSION["scale"] == 1) {
		$weight = number_format($_SESSION["weight"], 2)."lb.";
	}
	else {
		$weight = "_ _ _ _";
	}

	if (is_numeric($_SESSION["discounttotal"])) {
		$dbldiscounttotal = number_format($_SESSION["discounttotal"], 2);
	}
	else {
		$dbldiscounttotal = 0.00;
		$_SESSION["runningTotal"] = 0;
	}

	if (!$_SESSION["runningTotal"]) {
		$_SESSION["runningTotal"] = 0;
	}

	if ($_SESSION["isMember"] == 1 || $_SESSION["sc"] == 1) {
		$labelyousaved = "You Saved";
	}
	else {
		$labelyousaved = "Could Have Saved";
	}

	if ($_SESSION["percentDiscount"] == 0) {
		$strpercentdisclabel = "% Discount";
	}
	else {
		$strpercentdisclabel = $_SESSION["percentDiscount"]."% Discount";
	}

// -----------------------------------------------------------------------------------------------

	echo "</table></td></tr></table>";
	echo "<table border=0 cellspacing=0 cellpadding=0>";
	echo "<tr><td width=119 bgcolor=white align=left><font face=arial color=black size=-1><b>".$labelyousaved."</b></font></td>";
	echo "<td width=117 bgcolor=white align=center><font face=arial color=#004080 size=-1><b>".$strpercentdisclabel."</b></font></td>";
	echo "<td width=117 bgcolor=white align=center><font face=arial color=#004080 size=-1><b>Mbr Special</b></font></td>";

// -----------------------------------------------------------------------------------------------


	$strdiscountlabel = "Special";


// ----------------------First Row Labels ---------------------------------------------------------

	echo "<td width=117 bgcolor=white align=center><font face=arial color=#004080 size=-1><b>";
	echo $strdiscountlabel."</b></font></td>";

	if ( $_SESSION["ttlflag"] == 1  and $_SESSION["End"] != 1 ) {
		if ($_SESSION["fntlflag"] == 1) {
			echo "<td width=170 bgcolor=#800080 align=right><font face=arial color=white size=-1><b>fs Amount Due</b></font></td></tr>";
			// $_SESSION["ttlflag"] = 0;
		} else {
			echo "<td width=170 bgcolor=#800000 align=right><font face=arial color=white size=-1><b>Amount Due</b></font></td></tr>";
		}
	}
	elseif ($_SESSION["ttlflag"] == 1  and $_SESSION["End"] == 1 ) {
		echo "<td width=170 bgcolor=#004080 align=right><font face=arial color=white size=-1><b>Change</b></font></td></tr>";
	}	
	else {
		echo "<td width=170 bgcolor=black align=right><font face=arial color=white size=-1><b>Total</b></font></td></tr>";
	}

// ------------------ Second Row blank -----------------------------
		echo "<tr><td height=1 colspan=5 bgcolor=black></td></tr>";
		echo "<tr><td height=2 colspan=5></td></tr>";
// -----------------------------------------------------------------

	$special = $_SESSION["memSpecial"] + $_SESSION["staffSpecial"];

	if ($_SESSION["isMember"] == 1) {
		$dblyousaved = number_format( $_SESSION["transDiscount"] + $dbldiscounttotal + $special + $_SESSION["memCouponTTL"], 2);
		$_SESSION["yousaved"] = $dblyousaved;
		$_SESSION["couldhavesaved"] = 0;
		$_SESSION["specials"] = number_format($dbldiscounttotal + $special, 2);
	}
	else {
		$dblyousaved = number_format($_SESSION["memSpecial"], 2);
		$_SESSION["yousaved"] = $dbldiscounttotal + $_SESSION["staffSpecial"];
		$_SESSION["couldhavesaved"] = $dblyousaved;
		$_SESSION["specials"] = $dbldiscounttotal + $_SESSION["staffSpecial"];
	}

	if ($_SESSION["sc"] == 1) {
		
		$_SESSION["yousaved"] = $_SESSION["yousaved"] + $_SESSION["scDiscount"];
		$dblyousaved = $_SESSION["yousaved"];
	}

	if ($_SESSION["percentDiscount"] != 0 || $_SESSION["memCouponTTL"] > 0) {
		$strperdiscount = number_format($_SESSION["transDiscount"] + $_SESSION["memCouponTTL"], 2);
	}
	else {
		$strperdiscount = "n/a";
	}

	if ($_SESSION["isMember"] == 1) {
		$strmemSpecial = number_format($_SESSION["memSpecial"], 2);
	} else {
		$strmemSpecial = "n/a";
	}

	if ($_SESSION["End"] == 1) {
		rePoll();
	}

	if (strlen($_SESSION["endorseType"]) > 0 || $_SESSION["waitforScale"] == 1) {
		$_SESSION["waitforScale"] = 0;
		$_SESSION["beep"] = "noBeep";
	}

	if ($_SESSION["scale"] == 0 && $_SESSION["SNR"] == 1) {
		rePoll();
	}

	if ($_SESSION["cashOverAmt"] <> 0) {		// apbw/cvr 03/05/05 CashBackBeep
		twoPairs();		// apbw/cvr 03/05/05 CashBackBeep
		$_SESSION["cashOverAmt"] = 0;			// apbw/cvr 03/05/05 CashBackBeep
	}

echo "<TR><TD height='2' colspan='5'></TD></TR>";
echo "<TR><TD width='119' height='60' align='left' bgcolor='#EEEEEE'><FONT face='arial' size='+2' color='#004080'><CENTER><B>".number_format($dblyousaved, 2)."</B></CENTER></FONT></TD>";
echo "<TD width='117' align='center'><FONT face='arial' color='#808080' size='+1'>".number_format($strperdiscount, 2)."</FONT></TD>";
echo "<TD width='117' align='center'><FONT face='arial' color='#808080' size='+1'>".number_format($strmemSpecial, 2)."</FONT></TD>";
echo "<TD width='117' height='60' align='center'><FONT face='arial' color='#808080' size='+1'>".number_format($dbldiscounttotal, 2)."</FONT></TD>";



if ($_SESSION["ttlflag"] == 1 && $_SESSION["End"] != 1) {

	if ($_SESSION["fntlflag"] == 1) {
		echo "<TD width='170' height='60' align='center' bgcolor='white'><FONT face='arial' size='+3' color='#800080'><B>".number_format($_SESSION["fsEligible"], 2)."</B></FONT>";
		// $_SESSION["fntlflag"] = 0;
	} else {	
		echo "<TD width='170' height='60' align='center' bgcolor='white'><FONT face='arial' size='+3' color='#800000'><B>".number_format($_SESSION["runningTotal"], 2)."</B></FONT>";
	}
}
elseif ($_SESSION["ttlflag"] == 1 && $_SESSION["End"] == 1) {
	echo "<TD width= '170' height='60' align='center' bgcolor='white'><FONT face='arial' size='+3' color='#004080'><B>".number_format($_SESSION["runningTotal"], 2)."</B></FONT>";
}
else {
	echo "<TD width='170' height='60' align='center'><FONT face='arial' size='+3'><B>".number_format($_SESSION["runningTotal"], 2)."</B></FONT>";
}

echo"</td></tr>\n";



echo "</TABLE>\n";
echo "<FORM name='hidden'>\n";
echo "<INPUT Type='hidden' name='alert' value='".$_SESSION["beep"]."'>\n";
echo "<INPUT Type='hidden' name='scan' value='".$_SESSION["scan"]."'>\n";
echo "<INPUT Type='hidden' name='screset' value='".$_SESSION["screset"]."'>\n";
echo "</FORM>";


	$_SESSION["beep"] = "noBeep";
	$_SESSION["scan"] = "scan";
	$_SESSION["screset"] = "stayCool";

// echo $_SESSION["errorMsg"];


}

//--------------------------------------------------------------------//

function printfooterb() {

echo "<TR><TD><TD></TR></TABLE></TD></TR></TABLE>\n";
echo "<FORM name='hidden'>\n";
echo "<INPUT Type='hidden' name='alert' value='noBeep'>\n";
echo "<INPUT Type='hidden' name='scan' value='scan'>\n";
echo "</FORM>";

// echo	$_SESSION["errorMsg"];

}

//--------------------------------------------------------------------//

function plainmsg($strmsg) {

echo "<TR><TD height='295' width='640' valign='center' align='center' colspan='3'></CENTER>";
echo "<FONT face='arial' size='+2' color='#004080'><B>".$strmsg."</B></FONT></CENTER></TD></TR>";

}


//-------------------------------------------------------------------//

function msgbox($strmsg, $icon) {

printheaderb();
echo "<TR><TD height='295' width='640' align='center' valign='center'>";
echo "<TABLE border='0' cellpadding='0' cellspacing='0'>";
echo "<TR><TD colspan='5' bgcolor='#004080' height='30' width='260' valign='center'>&nbsp;&nbsp;&nbsp;<FONT size='+1' face='arial' color='white'><B>".$_SESSION["alertBar"]."</B></FONT></TD></TR>";
echo "<TR><TD colspan='5' bgcolor='black' height='1' width='260'></TD></TR>";
echo "<TR><TD width='1' height='118' bgcolor='black'></TD>";
echo "<TD bgcolor='white' height='118' width='50' valign='top' align='left'>";
echo "<IMG src='".$icon."'></TD>";
echo "<TD bgcolor='white' height='118' width='208' valign='center' align='left'><FONT face='arial' color='black'>";
echo $strmsg."</FONT></CENTER></TD>";
echo "<TD width='10' bgcolor='white' height='118'></TD>";
echo "<TD width='1' height='118' bgcolor='black'></TD></TR>";
echo "<TR><TD colspan='5' bgcolor='black' height='1' width='260'></TD></TR></TABLE>";
echo "</TD></TR>";


	$_SESSION["strRemembered"] = $_SESSION["strEntered"];
	errorBeep();
	//$_SESSION["scan"] = "noScan";
	$_SESSION["msgrepeat"] = 1;
	//$_SESSION["toggletax"] = 0;
	//$_SESSION["togglefoodstamp"] = 0;
	$_SESSION["away"] = 1;

}
//--------------------------------------------------------------------//

function xboxMsg($strmsg) {
	msgbox($strmsg, "graphics/crossD.gif");
}

function boxMsg($strmsg) {
	msgbox($strmsg, "graphics/exclaimC.gif");
}

function inputUnknown() {
	msgbox("<B>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;input unknown</B>", "graphics/exclaimC.gif");
}

//--------------------------------------------------------------------//

function printheaderb() {



	if ($_SESSION["memberID"] == "0" || !$_SESSION["memberID"]) {
		$strmemberID = "";
	}
	else {
		$strmemberID = $_SESSION["memMsg"];
		if ($_SESSION["isMember"] == 0 || substr($_SESSION["memberID"], 0, 2) == "28") {
			$strmemberID = str_replace("(0)", "(n/a)", $strmemberID);
		}
	}
	// Check if training mode is on, if so, set $mode so that the appropriate style is applied (Training Mode watermark)
	if($_SESSION["CashierNo"] == 9999 || $_SESSION["training"] == 1) {
		$mode = 'training';
	} else {
		$mode = 'normal';
	}
echo "\n<TABLE border='0' cellspacing='0' cellpadding='0'>";
echo "\n<TR><TD width='400' height='20' bgcolor='#EEEEEE'><FONT face='arial' size='-2'><B>M E M B E R &nbsp;&nbsp;</B></FONT><FONT face='arial' size='-1'><B>".$strmemberID."</B></FONT></TD>";
echo "\n<TD width='240' align='right'><FONT face='arial' size='-2'>C A S H I E R &nbsp;&nbsp;</FONT><FONT face='arial' size='-1'><B>".$_SESSION["cashier"]."</B></FONT><TD></TR></TABLE>";
echo "\n<TABLE border='0' cellspacing='0' cellpadding='0' class='$mode'>";
echo "\n<TR><TD width='640' bgcolor='black' height='1' colspan='2'></TD></TR>";
echo "\n<TR><TD width='640' height='295' valign='top'>";
echo "\n<TABLE border='0' cellpadding='0' cellspacing='0' class='$mode'>";

}

//-------------------------------------------------------------------//

function printitem($field2, $field3, $total, $field5) {

	if (strlen($total) > 0) {
		$total = number_format($total, 2);
	} else {
		$total = "";
	}

echo "<TR>";
echo "<TD width='350'><FONT color='#004080' face='arial' size='+1'>".$field2."</FONT></TD>";
echo "<TD width='140' align='right'><FONT color='#808080' face='arial' size='+1'>".$field3."</FONT></TD>";
echo "<TD width='100' align='right'><FONT color='#808080' face='arial' size='+1'><B>".$total."</B></FONT></TD>";
echo "<TD width='50' align='right'><FONT color='#808080' face='arial'><B>".$field5."</B></FONT></TD></TR>";

}

//------------------------------------------------------------------//

function printitemcolor($color, $description, $comments, $total, $suffix) {
	if (strlen($total) > 0) {
		$total = number_format($total, 2);
	} else {
		$total = "";
	}
	if ($total == 0 && $color == "408080") $total = "";

echo "<TR>";
echo "<TD width='350'><FONT color='#".$color."' face='arial' size='+1'>".$description."</FONT></TD>";
echo "<TD width='140' align='right' valign='top'><FONT color='#".$color."' face='arial' size='+1'>".$comments."</FONT></TD>";
echo "<TD width='100' align='right' valign='top'><FONT color='#".$color."' face='arial' size='+1'><B>".$total."</B></FONT></TD>";
echo "<TD width='50' align='right' valign='top'><FONT color='#".$color."' face='arial'><B>".$suffix."</B></FONT></TD></TR>";

}

//----------------------------------------------------------------//

function printitemcolorhilite($color, $description, $comments, $total, $suffix) {
	if (strlen($total) > 0) {
		$total = number_format($total, 2);
	} else {
		$total = "";
	}
	if ($total == 0 && $color == "408080") $total = "";

echo "<TR>";
echo "<TD width='350' bgcolor='#".$color."'><FONT color='white' face='arial' size='+1'>".$description."</FONT></TD>";
echo "<TD width='140' align='right' valign='top' bgcolor='#".$color."'><FONT color='white' face='arial' size='+1'>".$comments."</FONT></TD>";
echo "<TD width='100' align='right' valign='top' bgcolor='#".$color."'><FONT color='white' face='arial' size='+1'><B>".$total."</B></FONT></TD>";
echo "<TD width='50' align='right' valign='top' bgcolor='#".$color."'><FONT color='white' face='arial'><B>".$suffix."</B></FONT></TD></TR>";

}

//----------------------------------------------------------------//

function printItemHilite($description, $comments, $total, $suffix) {
	printItemColorHilite("004080", $description, $comments, $total, $suffix);
}


function plainsearch($strmsg) {

echo "<TR><TD height='295' width='640' valign='center' align='center' colspan='3'></CENTER>";
echo "<FONT face='arial' size='+2' color='#004080'><B>".$strmsg."<FORM action='/search.php' method='post' name='searchform'>";
echo "</B></FONT></CENTER></TD></TR>";

}


//---------------------------------------------------------------------//

function membersearchbox($strmsg) {

echo "<TR><TD height='295' width='640' align='center' valign='center'>";
echo "<TABLE border='0' cellpadding='0' cellspacing='0'>";
echo "<TD bgcolor='#004080' height='150' width='260' valign='center' align='center'><CENTER>";
echo "<FONT face='arial' size='-1' color='white'>".$strmsg;
echo "<FORM action='memlist.php' method='post' autocomplete='off' name='searchform'>";
echo "<INPUT Type='text' name='search' size='15' onblur='document.searchform.search.focus();'>";
echo "</FORM>press [enter] to cancel</FONT></CENTER></TD></TR></TABLE></TD></TR>";

}

//--------------------------------------------------------------------//

function productsearchbox($strmsg) {

echo "<TR><TD height='295' width='640' align='center' valign='center'>";
echo "<TABLE border='0' cellpadding='0' cellspacing='0'>";
echo "<TR><TD bgcolor='#004080' height='150' width='260' valign='center' align='center'></CENTER>";
echo "<FONT face='arial' size='-1' color='white'>";
echo $strmsg;
echo "<FORM action='productlist.php' method='post' autocomplete='off' name='searchform'>";
echo "<INPUT Type='text' name='search' size='15' onblur='document.searchform.search.focus();'></FORM>";
echo "press [enter] to cancel";
echo "</FONT></CENTER></TD></TR></TABLE></TD></TR>";

}
//--------------------------------------------------------------------//

function loanenter($strmsg) {

echo "<TR><TD height='295' width='640' align='center' valign='center'>";
echo "<TABLE border='0' cellpadding='0' cellspacing='0'>";
echo "<TR><TD bgcolor='#004080' height='150' width='260' valign='center' align='center'></CENTER>";
echo "<FONT face='arial' size='-1' color='white'>";
echo $strmsg;
echo "<FORM action='loanadd.php' method='post' autocomplete='off' name='loanamt'>";
echo "<INPUT Type='text' name='loan' size='15' onblur='document.loanamt.loan.focus()'></FORM>";
echo "press [enter] to cancel";
echo "</FONT></CENTER></TD></TR></TABLE></TD></TR>";

}
?>
