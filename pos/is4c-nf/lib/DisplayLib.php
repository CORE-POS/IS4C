<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/**
  @class DisplayLib
  Functions for drawing display elements
*/
class DisplayLib extends LibraryClass {

/**
  Print just the form from the regular footer
  but entirely hidden.
  @deprecated
  Only used with the old VB scale driver
*/
static public function printfooterb() {
	$ret = "<form name='hidden'>\n";
	$ret .= "<input Type='hidden' name='alert' value='noBeep'>\n";
	$ret .= "<input Type='hidden' name='scan' value='scan'>\n";
	$ret .= "</form>";
	return $ret;
}

/**
  Get the standard footer with total and
  amount(s) saved
  @param $readOnly don't update any session info
   This would be set when rendering a separate,
   different customer display
  @return A string of HTML
*/
static public function printfooter($readOnly=False) {
	global $CORE_LOCAL;

	$FOOTER_MODULES = $CORE_LOCAL->get("FooterModules");
	// use defaults if modules haven't been configured
	// properly
	if (!is_array($FOOTER_MODULES) || count($FOOTER_MODULES) != 5){
		$FOOTER_MODULES = array(
		'SavedOrCouldHave',
		'TransPercentDiscount',
		'MemSales',
		'EveryoneSales',
		'MultiTotal'
		);
	}
	
	$modchain = array();
	foreach($FOOTER_MODULES as $MOD){
		$modchain[] = new $MOD();
	}

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

	/* 5/11/12
	if (is_numeric($CORE_LOCAL->get("discounttotal"))) {
		$dbldiscounttotal = number_format($CORE_LOCAL->get("discounttotal"), 2);
	}
	else {
		$dbldiscounttotal = 0.00;
		if (!$readOnly)
			$CORE_LOCAL->set("runningTotal",0);
	}
	*/

	/*
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
	*/

	$ret = "<table>";
	$ret .= "<tr class=\"heading\">";
	$label = $modchain[0]->header_content();
	$ret .= sprintf('<td class="first" style="%s">%s</td>',$modchain[0]->header_css,$label);
	$label = $modchain[1]->header_content();
	$ret .= sprintf('<td class="reg" style="%s">%s</td>',$modchain[1]->header_css,$label);
	$label = $modchain[2]->header_content();
	$ret .= sprintf('<td class="reg" style="%s">%s</td>',$modchain[2]->header_css,$label);
	$label = $modchain[3]->header_content();
	$ret .= sprintf('<td class="reg" style="%s">%s</td>',$modchain[3]->header_css,$label);
	$label = $modchain[4]->header_content();
	$ret .= sprintf('<td class="total" style="%s">%s</td>',$modchain[4]->header_css,$label);
	/* 5/11/12
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
	*/
	$ret .= "</tr>";

	$special = $CORE_LOCAL->get("memSpecial") + $CORE_LOCAL->get("staffSpecial");
	$dbldiscounttotal = number_format($CORE_LOCAL->get("discounttotal"), 2);
	if ($CORE_LOCAL->get("isMember") == 1) {
		$dblyousaved = number_format( $CORE_LOCAL->get("transDiscount") + $dbldiscounttotal + $special, 2);
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

	/* 5/11/12
	$strperdiscount = "n/a";
	if ($CORE_LOCAL->get("percentDiscount") != 0) {
		$strperdiscount = number_format($CORE_LOCAL->get("transDiscount"), 2);
	}

	$strmemSpecial = "n/a";
	if ($CORE_LOCAL->get("isMember") == 1) {
		$strmemSpecial = number_format($CORE_LOCAL->get("memSpecial"), 2);
	}
	*/

	if (!$readOnly){
		if ($CORE_LOCAL->get("End") == 1) {
			MiscLib::rePoll();
		}
		if (strlen($CORE_LOCAL->get("endorseType")) > 0 || $CORE_LOCAL->get("waitforScale") == 1) {
			$CORE_LOCAL->set("waitforScale",0);
			$CORE_LOCAL->set("beep","noBeep");
		}
		if ($CORE_LOCAL->get("scale") == 0 && $CORE_LOCAL->get("SNR") == 1) {
			MiscLib::rePoll();
		}
		if ($CORE_LOCAL->get("cashOverAmt") <> 0) {
			MiscLib::twoPairs();
			$CORE_LOCAL->set("cashOverAmt",0);
		}
	}

	$ret .= "<tr class=\"values\">";
	$box = $modchain[0]->display_content();
	$ret .= sprintf('<td class="first" style="%s">%s</td>',$modchain[0]->display_css,$box);
	$box = $modchain[1]->display_content();
	$ret .= sprintf('<td class="reg" style="%s">%s</td>',$modchain[1]->display_css,$box);
	$box = $modchain[2]->display_content();
	$ret .= sprintf('<td class="reg" style="%s">%s</td>',$modchain[2]->display_css,$box);
	$box = $modchain[3]->display_content();
	$ret .= sprintf('<td class="reg" style="%s">%s</td>',$modchain[3]->display_css,$box);
	$box = $modchain[4]->display_content();
	$ret .= sprintf('<td class="total" style="%s">%s</td>',$modchain[4]->display_css,$box);
	/* 5/11/12
	$ret .= "<td class=\"first\">".number_format($dblyousaved,2)."</td>";
	$ret .= "<td class=\"reg\">".$strperdiscount."</td>";
	$ret .= "<td class=\"reg\">".$strmemSpecial."</td>";
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
	*/
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

/**
   Wrap a message in a id="plainmsg" div
   @param $strmsg the message
   @return An HTML string
*/
static public function plainmsg($strmsg) {
	return "<div id=\"plainmsg\">$strmsg</div>";
}


//-------------------------------------------------------------------//

/**
  Get a centered message box
  @param $strmsg the message
  @param $icon graphic icon file
  @param $noBeep don't send a scale beep
  @return An HTML string

  This function will include the header
  printheaderb(). 
*/
static public function msgbox($strmsg, $icon,$noBeep=False) {
	global $CORE_LOCAL;

	$ret = self::printheaderb();
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
		MiscLib::errorBeep();
	//$_SESSION["scan"] = "noScan";
	$CORE_LOCAL->set("msgrepeat",1);
	//$_SESSION["toggletax"] = 0;
	//$_SESSION["togglefoodstamp"] = 0;
	//$_SESSION["away"] = 1;

	return $ret;
}
//--------------------------------------------------------------------//

/**
  Get a centered message box with "crossD" graphic
  @param $strmsg the message
  @return An HTML string

  An alias for msgbox().
*/
static public function xboxMsg($strmsg) {
	return self::msgbox($strmsg, MiscLib::base_url()."graphics/crossD.gif");
}

/**
  Get a centered message box with "exclaimC" graphic
  @param $strmsg the message
  @param $header does nothing...
  @param $noBeep don't beep scale
  @return An HTML string

  An alias for msgbox().
*/
static public function boxMsg($strmsg,$header="",$noBeep=False) {
	return self::msgbox($strmsg, MiscLib::base_url()."graphics/exclaimC.gif",$noBeep);
}

/**
  Get a centered message box with input unknown message.
  @return An HTML string

  An alias for msgbox().
*/
static public function inputUnknown() {
	return self::msgbox("<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			"._("input unknown")."</b>", MiscLib::base_url()."graphics/exclaimC.gif",True);
}

//--------------------------------------------------------------------//

/**
  Get the standard header row with CASHIER
  and MEMBER info
  @return An HTML string
*/
static public function printheaderb() {
	global $CORE_LOCAL;

	$strmemberID = "";
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
			<span class="bigger">'._("M E M B E R").' &nbsp;&nbsp;</span>
			<span class="smaller">
			'.$strmemberID.'
			</span>
		</div>
		<div class="right">
			<span class="bigger">'._("C A S H I E R").' &nbsp;&nbsp;</span>
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

/**
  Get a transaction line item
  @param $field2 typically description
  @param $field3 comment section. Used for things
   like "0.59@1.99" on weight items.
  @param $total the right-hand number
  @param $field5 flags after the number
  @param $trans_id value from localtemptrans. Including
   the trans_id makes the lines selectable via mouseclick
   (or touchscreen).
  @return An HTML string
*/
static public function printitem($field2, $field3, $total, $field5, $trans_id=-1) {
	global $CORE_LOCAL;
	
	$onclick = "";
	if ($trans_id != -1){
		$curID = $CORE_LOCAL->get("currentid");
		$diff = $trans_id - $curID;
		if ($diff > 0){
			$onclick="onclick=\"parseWrapper('D$diff');\"";
		}
		else if ($diff < 0){
			$diff *= -1;
			$onclick="onclick=\"parseWrapper('U$diff');\"";
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

/**
  Get a transaction line item in a specific color
  @param $color is a hex color code (do not include a '#')
  @param $description typically description
  @param $comments comment section. Used for things
   like "0.59@1.99" on weight items.
  @param $total the right-hand number
  @param $suffix flags after the number
  @param $trans_id value from localtemptrans. Including
   the trans_id makes the lines selectable via mouseclick
   (or touchscreen).
  @return An HTML string
*/
static public function printitemcolor($color, $description, $comments, $total, $suffix,$trans_id=-1) {
	global $CORE_LOCAL;
	
	$onclick = "";
	if ($trans_id != -1){
		$curID = $CORE_LOCAL->get("currentid");
		$diff = $trans_id - $curID;
		if ($diff > 0){
			$onclick="onclick=\"parseWrapper('D$diff');\"";
		}
		else if ($diff < 0){
			$diff *= -1;
			$onclick="onclick=\"parseWrapper('U$diff');\"";
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

/**
  Get a transaction line item in a specific color
  @param $color is a hex color code (do not include a '#')
  @param $description typically description
  @param $comments comment section. Used for things
   like "0.59@1.99" on weight items.
  @param $total the right-hand number
  @param $suffix flags after the number
  @return An HTML string

  This could probably be combined with printitemcolor(). They're
  separate because no one has done that yet.
*/
static public function printitemcolorhilite($color, $description, $comments, $total, $suffix) {
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

/**
  Alias for printitemcolorhilite().
*/
static public function printItemHilite($description, $comments, $total, $suffix) {
	return self::printitemcolorhilite("004080", $description, $comments, $total, $suffix);
}

/**
  Get the scale display box
  @param $input message from scale
  @return An HTML string
 
  If $input is specified, weight information
  in the session gets updated before returning
  the current value.

  Known input values are:
   - S11WWWW where WWWW is weight in hundreths
     (i.e., 1lb = 0100)
   - S141 not settled on a weight yet
   - S143 zero weight
   - S145 an error condition
   - S142 an error condition
*/
static public function scaledisplaymsg($input=""){
	global $CORE_LOCAL;
	$reginput = trim(strtoupper($input));

	$scans = '';

	// return early; all other cases simplified
	// by resetting session "weight"
	if (strlen($reginput) == 0) {
		if (is_numeric($CORE_LOCAL->get("weight")))
			return number_format($CORE_LOCAL->get("weight"), 2)." lb";
		else
			return $CORE_LOCAL->get("weight")." lb";
	}

	$display_weight = "";
	$weight = 0;
	$CORE_LOCAL->set("scale",0);
	$CORE_LOCAL->set("weight",0);

	$prefix = "NonsenseThatWillNotEverHappen";
	if (substr($reginput, 0, 3) == "S11")
		$prefix = "S11";
	else if (substr($reginput,0,4)=="S144")	
		$prefix = "S144";

	if (strpos($reginput, $prefix) === 0){
		$len = strlen($prefix);
		if (!substr($reginput, $len) || 
		    !is_numeric(substr($reginput, $len))) {
			$display_weight = "_ _ _ _";
		}
		else {
			$weight = number_format(substr($reginput, $len)/100, 2);
			$CORE_LOCAL->set("weight",$weight);
			$display_weight = $weight." lb";
			$CORE_LOCAL->set("scale",1);
			if ($CORE_LOCAL->get('SNR') != 0 && $weight != 0){
				$scans = $CORE_LOCAL->get('SNR');
			}
		}
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

	$ret = array('display'=>$display_weight);
	if (!empty($scans)) $ret['upc'] = $scans;

	return $ret;
}

/**
  Get the items currently on screen
  @param $top_item is trans_id (localtemptrans)
   of the first item to display
  @param $highlight is the trans_id (localtemptrans)
   of the currently selected item
  @return An HTML string

  If you just want to show the most recently
  scanned items, use lastpage().
*/
static public function listitems($top_item, $highlight) {
	global $CORE_LOCAL;

	Database::getsubtotals();
	$LastID = $CORE_LOCAL->get("LastID");

//----------------Boundary Top ------------------

	if ($highlight < 1) {
		$highlight = 1;
		$top_item = 1;
	}
	
	if ($highlight > $LastID) {
		$highlight = $LastID;
	}

	if ($highlight < $top_item) {
		$top_item = $highlight;
	}

	if ($highlight > ($top_item + 11)) {
		$top_item = ($highlight - 11);
	}

	$CORE_LOCAL->set("currenttopid",$top_item);
	$CORE_LOCAL->set("currentid",$highlight);

//------------------Boundary Bottom----------------

	return self::drawitems($top_item, 11, $highlight);
	$CORE_LOCAL->set("currentid",$highlight);
}


/**
  Show some items and farewell message
  @param $readOnly don't update totals
  @return An HTML string

  Show a few recent items and the 
  "Thank you for shopping" messaging.

  Yes, this function should be renamed. It
  has nothing to do with receipts.
*/
static public function printReceiptfooter($readOnly=False) {
	global $CORE_LOCAL;

	if ($CORE_LOCAL->get("sc") == 1) {
		return self::lastpage();
	}
	else {

		if (!$readOnly)
			Database::getsubtotals();
		$last_id = $CORE_LOCAL->get("LastID");

		if (($last_id - 7) < 0) {
			$top_id = 1;
		}
		else {
			$top_id = $last_id - 7;
		}

		$ret = self::drawitems($top_id, 7, 0);

		$ret .= "<div class=\"farewellMsg\">";
		for($i=0;$i<=$CORE_LOCAL->get("farewellMsgCount");$i++){
			$ret .= $CORE_LOCAL->get("farewellMsg".$i)."<br />";
		}

		$email = CoreState::getCustomerPref('email_receipt');
		$doEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
		if($doEmail) $ret .= 'receipt emailed';

		$ret .= "</div>";
		return $ret;
	}
}


/**
  Get the currently displayed items
  @param $top_item is the trans_id of the first item to display
  @param $rows is the number of items to display
  @param $highlight is the trans_id of the selected item
  @return An HTML string

  This function probably shouldn't be used directly.
  Call listitems() or lastpage() instead.
*/
static public function drawitems($top_item, $rows, $highlight) {
	global $CORE_LOCAL;

	$ret = self::printheaderb();

	$query = "select count(*) as count from localtemptrans";
	$db = Database::tDataConnect();
	$result = $db->query($query);
	$row = $db->fetch_array($result);
	$rowCount = $row["count"];

	$last_item = array();

	if ($rowCount == 0) {
		$ret .= "<div class=\"centerOffset\">";
		$msg_text = "";
		if ($CORE_LOCAL->get("training") != 1) {
			for($i=1; $i<=$CORE_LOCAL->get("welcomeMsgCount");$i++){
				$msg_text .= $CORE_LOCAL->get("welcomeMsg".$i)."<br />";
			}	
		}
		else {
			for($i=1; $i<=$CORE_LOCAL->get("trainingMsgCount");$i++){
				$msg_text .= $CORE_LOCAL->get("trainingMsg".$i)."<br />";
			}	
		}
		$ret .= self::plainmsg($msg_text);
		$ret .= "</div>";
	}
	else {

		$query_range = "select trans_id,description,total,comment,status,lineColor
		       		from screendisplay where trans_id >= ".$top_item." and trans_id <= "
				.($top_item + $rows)." order by trans_id";
		$db_range = Database::tDataConnect();
		$result_range = $db_range->query($query_range);
		$num_rows = $db_range->num_rows($result_range);

		for ($i = 0; $i < $num_rows; $i++) {
			$row = $db_range->fetch_array($result_range);

			$trans_id = $row["trans_id"];
			$description = $row["description"];
			$total = $row["total"];
			$comment = $row["comment"];
			$tf = $row["status"];
			$color = $row["lineColor"];

			
			if ($trans_id == $highlight) {
				if ($color == "004080") {
					$ret .= self::printitemhilite($description, $comment, $total, $tf);
				}
				else {
					$ret .= self::printitemcolorhilite($color, $description, $comment, $total, $tf);
				}
			}
			else
				{
				if ($color == "004080") {
					$ret .= self::printitem($description, $comment, $total, $tf,$trans_id);
				}
				else {
					$ret .= self::printitemcolor($color, $description, $comment, $total, $tf,$trans_id);
				}				
			}

			if (!strstr($description,'Subtotal')){
				$fixed_desc = str_replace(":"," ",$description);
				if (strlen($fixed_desc) > 24){
					$fixed_desc = substr($fixed_desc,0,24);
				}
				$fixed_price = empty($total)?'':sprintf('%.2f',$total);
				$spaces = str_pad('',30-strlen($fixed_desc)-strlen($fixed_price),' ');
				$last_item[] = $fixed_desc.$spaces.$fixed_price;
			}
		}
	}

	$td = SigCapture::term_object();
	if (is_object($td) && !empty($last_item)){
		$due = sprintf('%.2f',$CORE_LOCAL->get("amtdue"));
		$dueline = 'Subtotal'
			.str_pad('',22-strlen($due),' ')
			.$due;
		$items = "";
		$count = 0;
		for($i=count($last_item)-1;$i>=0;$i--){
			$items = ":".$last_item[$i].$items;
			$count++;
			if ($count >= 3) break;
		}
		for($i=$count;$i<3;$i++)
			$items = ": ".$items;
		$td->WriteToScale("display:".$last_item.":".$dueline);
	}

	return $ret;
}


/**
  Get the currently displayed items
  @param $readOnly don't update session
  @return An HTML string

  This will always display the most recently
  scanned items. If you want a specific subset,
  use listitems().
*/
static public function lastpage($readOnly=False) {
	global $CORE_LOCAL;

	if (!$readOnly){
		Database::getsubtotals();
	}
	$last_id = $CORE_LOCAL->get("LastID");

	if (($last_id - 11) < 0) {
		$top_id = 1;
	}
	else {
		$top_id = $last_id - 11;
	}
	
	if (!$readOnly){
		$CORE_LOCAL->set("currentid",$last_id);
		$CORE_LOCAL->set("currenttopid",$top_id);
	}
	return self::drawitems($top_id, 11, $last_id);
}

} // end class DisplayLib

?>
