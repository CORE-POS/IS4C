<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!function_exists("rePoll")) include($CORE_PATH."lib/lib.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

function printfooterb() {
	$ret = "<form name='hidden'>\n";
	$ret .= "<input Type='hidden' name='alert' value='noBeep'>\n";
	$ret .= "<input Type='hidden' name='scan' value='scan'>\n";
	$ret .= "</form>";
	return $ret;
}

function printfooter($readOnly=False) {
	global $CORE_LOCAL;

	if (!$readOnly) {
		$CORE_LOCAL->set("runningTotal",$CORE_LOCAL->get("amtdue"));
	}

	if ($CORE_LOCAL->get("End") == 1 && !$readOnly) {
		$CORE_LOCAL->set("runningTotal",-1 * $CORE_LOCAL->get("change"));
	}
	
	if ($CORE_LOCAL->get("scale") == 1) {
		$weight = number_format($CORE_LOCAL->get("weight"), 2)."lb.";
	}
	else {
		$weight = "_ _ _ _";
	}

	if (is_numeric($CORE_LOCAL->get("discounttotal"))) {
		$dbldiscounttotal = number_format($CORE_LOCAL->get("discounttotal"), 2);
	}
	else {
		$dbldiscounttotal = 0.00;
		if (!$readOnly)
			$CORE_LOCAL->set("runningTotal",0);
	}

	if ($CORE_LOCAL->get("runningTotal") == "" && !$readOnly) {
		$CORE_LOCAL->set("runningTotal",0);
	}

	if ($CORE_LOCAL->get("isMember") == 1 || $CORE_LOCAL->get("sc") == 1) {
		$labelyousaved = "You Saved";
	}
	else {
		$labelyousaved = "Could Have Saved";
	}

	if ($CORE_LOCAL->get("percentDiscount") == 0) {
		$strpercentdisclabel = "% Discount";
	}
	else {
		$strpercentdisclabel = $CORE_LOCAL->get("percentDiscount")."% Discount";
	}

// -----------------------------------------------------------------------------------------------

	$ret = "<table>";
	$ret .= "<tr class=\"heading\">";
	$ret .= "<td class=\"first\">$labelyousaved</td>";
	$ret .= "<td class=\"reg\">$strpercentdisclabel</td>";
	$ret .= "<td class=\"reg\">Mbr Special</td>";
	$ret .= "<td class=\"reg\">Special</td>";
	if ( $CORE_LOCAL->get("ttlflag") == 1 and $CORE_LOCAL->get("End") != 1 ) {
		if ($CORE_LOCAL->get("fntlflag") == 1) {
			$ret .= "<td class=\"fs\">fs Amount Due</td>";
		} else {
			$ret .= "<td class=\"due\">Amount Due</td>";
		}
	}
	elseif ($CORE_LOCAL->get("ttlflag") == 1  and $CORE_LOCAL->get("End") == 1 ) {
		$ret .= "<td class=\"change\">Change</td>";
	}	
	else {
		$ret .= "<td class=\"total\">Total</td>";
	}
	$ret .= "</tr>";

	$special = $CORE_LOCAL->get("memSpecial") + $CORE_LOCAL->get("staffSpecial");
	if ($CORE_LOCAL->get("isMember") == 1) {
		$dblyousaved = number_format( $CORE_LOCAL->get("transDiscount") + $dbldiscounttotal + $special + $CORE_LOCAL->get("memCouponTTL"), 2);
		if (!$readOnly){
			$CORE_LOCAL->set("yousaved",$dblyousaved);
			$CORE_LOCAL->set("couldhavesaved",0);
			$CORE_LOCAL->set("specials",number_format($dbldiscounttotal + $special, 2));
		}
	}
	else {
		$dblyousaved = number_format($CORE_LOCAL->get("memSpecial"),2);
		if (!$readOnly){
			$CORE_LOCAL->set("yousaved",$dbldiscounttotal + $CORE_LOCAL->get("staffSpecial"));
			$CORE_LOCAL->set("couldhavesaved",$dblyousaved);
			$CORE_LOCAL->set("specials",$dbldiscounttotal + $CORE_LOCAL->get("staffSpecial"));
		}
	}
	if ($CORE_LOCAL->get("sc") == 1) {
		if (!$readOnly){
			$CORE_LOCAL->set("yousaved",$CORE_LOCAL->get("yousaved") + $CORE_LOCAL->get("scDiscount"));
		}
		$dblyousaved = $CORE_LOCAL->get("yousaved");
	}
	if ($CORE_LOCAL->get("percentDiscount") != 0 || $CORE_LOCAL->get("memCouponTTL") > 0) {
		$strperdiscount = number_format($CORE_LOCAL->get("transDiscount") + $CORE_LOCAL->get("memCouponTTL"), 2);
	}
	else {
		$strperdiscount = "n/a";
	}
	if ($CORE_LOCAL->get("isMember") == 1) {
		$strmemSpecial = number_format($CORE_LOCAL->get("memSpecial"), 2);
	} else {
		$strmemSpecial = "n/a";
	}
	if (!$readOnly){
		if ($CORE_LOCAL->get("End") == 1) {
			rePoll();
		}
		if (strlen($CORE_LOCAL->get("endorseType")) > 0 || $CORE_LOCAL->get("waitforScale") == 1) {
			$CORE_LOCAL->set("waitforScale",0);
			$CORE_LOCAL->set("beep","noBeep");
		}
		if ($CORE_LOCAL->get("scale") == 0 && $CORE_LOCAL->get("SNR") == 1) {
			rePoll();
		}
		if ($CORE_LOCAL->get("cashOverAmt") <> 0) {
			twoPairs();
			$CORE_LOCAL->set("cashOverAmt",0);
		}
	}

	$ret .= "<tr class=\"values\">";
	$ret .= "<td class=\"first\">".number_format($dblyousaved,2)."</td>";
	$ret .= "<td class=\"reg\">".number_format((double)$strperdiscount,2)."</td>";
	$ret .= "<td class=\"reg\">".number_format((double)$strmemSpecial,2)."</td>";
	$ret .= "<td class=\"reg\">".number_format($dbldiscounttotal,2)."</td>";
	if ($CORE_LOCAL->get("ttlflag") == 1 && $CORE_LOCAL->get("End") != 1) {
		if ($CORE_LOCAL->get("fntlflag") == 1) {
			$ret .= "<td class=\"fs\">".number_format($CORE_LOCAL->get("fsEligible"), 2)."</td>";
		} else {	
			$ret .= "<td class=\"due\">".number_format($CORE_LOCAL->get("runningTotal"), 2)."</td>";
		}
	}
	elseif ($CORE_LOCAL->get("ttlflag") == 1 && $CORE_LOCAL->get("End") == 1) {
		$ret .= "<td class=\"change\">".number_format($CORE_LOCAL->get("runningTotal"), 2)."</td>";
	}
	else {
		$ret .= "<td class=\"total\">".number_format($CORE_LOCAL->get("runningTotal"), 2)."</td>";
	}
	$ret .= "</tr>";
	$ret .= "</table>";

	if (!$readOnly){
		$ret .= "<form name='hidden'>\n";
		$ret .= "<input type='hidden' id='alert' name='alert' value='".$CORE_LOCAL->get("beep")."'>\n";
		$ret .= "<input type='hidden' id='scan' name='scan' value='".$CORE_LOCAL->get("scan")."'>\n";
		$ret .= "<input type='hidden' id='screset' name='screset' value='".$CORE_LOCAL->get("screset")."'>\n";
		$ret .= "<input type='hidden' id='ccTermOut' name='ccTermOut' value=\"".$CORE_LOCAL->get("ccTermOut")."\">\n";
		$ret .= "</form>";
		$CORE_LOCAL->set("beep","noBeep");
		$CORE_LOCAL->set("scan","scan");
		$CORE_LOCAL->set("screset","stayCool");
		$CORE_LOCAL->set("ccTermOut","idle");
	}

	return $ret;
}

//--------------------------------------------------------------------//

function plainmsg($strmsg) {
return "<div id=\"plainmsg\">$strmsg</div>";

}


//-------------------------------------------------------------------//

function msgbox($strmsg, $icon,$noBeep=False) {
	global $CORE_LOCAL;

	$ret = printheaderb();
	$ret .= "<div id=\"boxMsg\" class=\"centeredDisplay\">";
	$ret .= "<div class=\"boxMsgAlert\">";
	$ret .= $CORE_LOCAL->get("alertBar");
	$ret .= "</div>";
	$ret .= "<div class=\"boxMsgBody\">";
	$ret .= "<div class=\"msgicon\"><img src=\"$icon\" /></div>";
	$ret .= "<div class=\"msgtext\">";
	$ret .= $strmsg;
	$ret .= "</div><div class=\"clear\"></div></div>";
	$ret .= "</div>";

	$CORE_LOCAL->set("strRemembered",$CORE_LOCAL->get("strEntered"));
	if ($CORE_LOCAL->get("warned") == 0 && !$noBeep)
		errorBeep();
	//$_SESSION["scan"] = "noScan";
	$CORE_LOCAL->set("msgrepeat",1);
	//$_SESSION["toggletax"] = 0;
	//$_SESSION["togglefoodstamp"] = 0;
	//$_SESSION["away"] = 1;

	return $ret;
}
//--------------------------------------------------------------------//

function xboxMsg($strmsg) {
	global $CORE_PATH;
	return msgbox($strmsg, $CORE_PATH."graphics/crossD.gif");
}

function boxMsg($strmsg,$header="",$noBeep=False) {
	global $CORE_PATH;
	return msgbox($strmsg, $CORE_PATH."graphics/exclaimC.gif",$noBeep);
}

function inputUnknown() {
	global $CORE_PATH;
	return msgbox("<B>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;input unknown</B>", $CORE_PATH."graphics/exclaimC.gif");
}

//--------------------------------------------------------------------//

function printheaderb() {
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("memberID") == "0") {
		$strmemberID = "";
	}
	else {
		$strmemberID = $CORE_LOCAL->get("memMsg");
		if ($CORE_LOCAL->get("isMember") == 0){
			$strmemberID = str_replace("(0)", "(n/a)", $strmemberID);
		}
	}

	$ret = '
	<div id="headerb">
		<div class="left">
			<span class="bigger">M E M B E R &nbsp;&nbsp;</span>
			<span class="smaller">
			'.$strmemberID.'
			</span>
		</div>
		<div class="right">
			<span class="bigger">C A S H I E R &nbsp;&nbsp;</span>
			<span class="smaller">
			'.$CORE_LOCAL->get("cashier").'
			</span>
		</div>
		<div class="clear"></div>
	</div>
	';
	return $ret;
}

//-------------------------------------------------------------------//

function printitem($field2, $field3, $total, $field5, $trans_id=-1) {
	global $CORE_LOCAL;
	
	$onclick = "";
	if ($trans_id != -1){
		$curID = $CORE_LOCAL->get("currentid");
		$diff = $trans_id - $curID;
		if ($diff > 0){
			$onclick="onclick=\"\$('#reginput').val('D$diff');submitWrapper();\"";
		}
		else if ($diff < 0){
			$diff *= -1;
			$onclick="onclick=\"\$('#reginput').val('U$diff');submitWrapper();\"";
		}
	}	

	if (strlen($total) > 0) {
		$total = number_format($total, 2);
	} else {
		$total = "&nbsp;";
	}
	if ($field2 == "") $field2 = "&nbsp;";
	if (trim($field3) == "") $field3 = "&nbsp;";
	if ($field5 == "") $field5 = "&nbsp;";

	$ret = "<div class=\"item\">";
	$ret .= "<div $onclick class=\"desc\">$field2</div>";
	$ret .= "<div $onclick class=\"comments\">$field3</div>";
	$ret .= "<div $onclick class=\"total\">$total</div>";
	$ret .= "<div $onclick class=\"suffix\">$field5</div>";
	$ret .= "</div>";
	$ret .= "<div style=\"clear:left;\"></div>\n";
	return $ret;
}

//------------------------------------------------------------------//

function printitemcolor($color, $description, $comments, $total, $suffix,$trans_id=-1) {
	global $CORE_LOCAL;
	
	$onclick = "";
	if ($trans_id != -1){
		$curID = $CORE_LOCAL->get("currentid");
		$diff = $trans_id - $curID;
		if ($diff > 0){
			$onclick="onclick=\"\$('#reginput').val('D$diff');submitWrapper();\"";
		}
		else if ($diff < 0){
			$diff *= -1;
			$onclick="onclick=\"\$('#reginput').val('U$diff');submitWrapper();\"";
		}
	}	

	if (strlen($total) > 0) {
		$total = number_format($total, 2);
	} else {
		$total = "&nbsp;";
	}
	if ($total == 0 && $color == "408080") $total = "&nbsp;";
	if ($description == "") $description = "&nbsp;";
	if (trim($comments) == "") $comments = "&nbsp;";
	if ($suffix == "") $suffix = "&nbsp;";

	$style = "style=\"color:#$color;\"";
	$ret = "<div class=\"item\">";
	$ret .= "<div $onclick class=\"desc\" $style>$description</div>";
	$ret .= "<div $onclick class=\"comments\" $style>$comments</div>";
	$ret .= "<div $onclick class=\"total\" $style>$total</div>";
	$ret .= "<div $onclick class=\"suffix\" $style>$suffix</div>";
	$ret .= "</div>";
	$ret .= "<div style=\"clear:left;\"></div>\n";
	return $ret;
}

//----------------------------------------------------------------//

function printitemcolorhilite($color, $description, $comments, $total, $suffix) {
	if (strlen($total) > 0) {
		$total = number_format($total, 2);
	} else {
		$total = "&nbsp;";
	}
	if ($total == 0 && $color == "408080") $total = "&nbsp;";
	if ($description == "") $description="&nbsp;";
	if (trim($comments) == "") $comments="&nbsp;";
	if ($suffix == "") $suffix="&nbsp;";

	$style = "style=\"background:#$color;color:#ffffff;\"";
	$ret = "<div class=\"item\">";
	$ret .= "<div class=\"desc\" $style>$description</div>";
	$ret .= "<div class=\"comments\" $style>$comments</div>";
	$ret .= "<div class=\"total\" $style>$total</div>";
	$ret .= "<div class=\"suffix\" $style>$suffix</div>";
	$ret .= "</div>";
	$ret .= "<div style=\"clear:left;\"></div>\n";
	return $ret;
}

//----------------------------------------------------------------//

function printItemHilite($description, $comments, $total, $suffix) {
	return printItemColorHilite("004080", $description, $comments, $total, $suffix);
}

function scaledisplaymsg($input=""){
	global $CORE_LOCAL;
	$reginput = trim(strtoupper($input));

	// return early; all other cases simplified
	// by resetting session "weight"
	if (strlen($reginput) == 0) {
		return number_format($CORE_LOCAL->get("weight"), 2)." lb";
	}

	$display_weight = "";
	$weight = 0;
	$CORE_LOCAL->set("scale",0);
	$CORE_LOCAL->set("weight",0);

	if (substr($reginput, 0, 3) == "S11") {
		if (!substr($reginput, 3) || 
		    !is_numeric(substr($reginput, 3))) {
			$display_weight = "_ _ _ _";
		}
		else {
			$weight = number_format(substr($reginput, 3)/100, 2);
			$CORE_LOCAL->set("weight",$weight);
			$display_weight = $weight." lb";
			$CORE_LOCAL->set("scale",1);
		}

		/*
		if ($CORE_LOCAL->get("SNR") == 1 && $weight > 0 ) {
			clearbox();
		}
		 */
	}
	elseif (substr($reginput, 0, 4) == "S143") {
		$display_weight = "0.00 lb";
		$CORE_LOCAL->set("scale",1);
	}
	elseif (substr($reginput, 0, 4) == "S141") {
		$display_weight = "_ _ _ _";
	}
	elseif (substr($reginput, 0, 4) == "S145") {
		$display_weight = "err -0";
	}
	elseif (substr($reginput, 0, 4) == "S142") {
		$display_weight = "error";
	}
	else {
		$display_weight = "? ? ? ?";
	}

	return $display_weight;
}

?>
