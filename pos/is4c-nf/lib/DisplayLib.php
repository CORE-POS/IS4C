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
  Get the standard footer with total and
  amount(s) saved
  @param $readOnly don't update any session info
   This would be set when rendering a separate,
   different customer display
  @return A string of HTML
*/
static public function printfooter($readOnly=False) 
{
	$FOOTER_MODULES = CoreLocal::get("FooterModules");
	// use defaults if modules haven't been configured
	// properly
	if (!is_array($FOOTER_MODULES) || count($FOOTER_MODULES) != 5) {
		$FOOTER_MODULES = array(
		'SavedOrCouldHave',
		'TransPercentDiscount',
		'MemSales',
		'EveryoneSales',
		'MultiTotal'
		);
	}
	
	$modchain = array();
	foreach($FOOTER_MODULES as $MOD) {
		$modchain[] = new $MOD();
	}

	if (!$readOnly) {
		CoreLocal::set("runningTotal",CoreLocal::get("amtdue"));
	}

	if (CoreLocal::get("End") == 1 && !$readOnly) {
		CoreLocal::set("runningTotal",-1 * CoreLocal::get("change"));
	}
	
	if (CoreLocal::get("scale") == 1) {
		$weight = number_format(CoreLocal::get("weight"), 2)."lb.";
	} else {
		$weight = "_ _ _ _";
	}

	$ret = "<table>";
	$ret .= "<tr class=\"heading\">";
	$label = $modchain[0]->header_content();
	$ret .= sprintf('<td class="first %s" style="%s">%s</td>',
			$modchain[0]->header_css_class, $modchain[0]->header_css,$label);
	$label = $modchain[1]->header_content();
	$ret .= sprintf('<td class="reg %s" style="%s">%s</td>',
			$modchain[1]->header_css_class, $modchain[1]->header_css,$label);
	$label = $modchain[2]->header_content();
	$ret .= sprintf('<td class="reg %s" style="%s">%s</td>',
			$modchain[2]->header_css_class, $modchain[2]->header_css,$label);
	$label = $modchain[3]->header_content();
	$ret .= sprintf('<td class="reg %s" style="%s">%s</td>',
			$modchain[3]->header_css_class, $modchain[3]->header_css,$label);
	$label = $modchain[4]->header_content();
	$ret .= sprintf('<td class="total %s" style="%s">%s</td>',
			$modchain[4]->header_css_class, $modchain[4]->header_css,$label);
	$ret .= "</tr>";

	$special = CoreLocal::get("memSpecial") + CoreLocal::get("staffSpecial");
	$dbldiscounttotal = 0.00;
	if (is_numeric(CoreLocal::get('discounttotal'))) {
		$dbldiscounttotal = number_format(CoreLocal::get("discounttotal"), 2);
    }

	if (CoreLocal::get("isMember") == 1) {
		$dblyousaved = number_format( CoreLocal::get("transDiscount") + $dbldiscounttotal + $special, 2);
		if (!$readOnly) {
			CoreLocal::set("yousaved",$dblyousaved);
			CoreLocal::set("couldhavesaved",0);
			CoreLocal::set("specials",number_format($dbldiscounttotal + $special, 2));
		}
	} else {
		$dblyousaved = 0.00;
		if (is_numeric(CoreLocal::get('memSpecial'))) {
			$dblyousaved = number_format(CoreLocal::get("memSpecial"), 2);
        }
		if (!$readOnly) {
			CoreLocal::set("yousaved",$dbldiscounttotal + CoreLocal::get("staffSpecial"));
			CoreLocal::set("couldhavesaved",$dblyousaved);
			CoreLocal::set("specials",$dbldiscounttotal + CoreLocal::get("staffSpecial"));
		}
	}

	if (!$readOnly) {
		if (CoreLocal::get("End") == 1) {
			MiscLib::rePoll();
		}
		if (CoreLocal::get("scale") == 0 && CoreLocal::get("SNR") != 0) {
			MiscLib::rePoll();
		}
	}

	$ret .= "<tr class=\"values\">";
	$box = $modchain[0]->display_content();
	$ret .= sprintf('<td class="first %s" style="%s">%s</td>',
			$modchain[0]->display_css_class,$modchain[0]->display_css,$box);
	$box = $modchain[1]->display_content();
	$ret .= sprintf('<td class="reg %s" style="%s">%s</td>',
			$modchain[1]->display_css_class,$modchain[1]->display_css,$box);
	$box = $modchain[2]->display_content();
	$ret .= sprintf('<td class="reg %s" style="%s">%s</td>',
			$modchain[2]->display_css_class,$modchain[2]->display_css,$box);
	$box = $modchain[3]->display_content();
	$ret .= sprintf('<td class="reg %s" style="%s">%s</td>',
			$modchain[3]->display_css_class,$modchain[3]->display_css,$box);
	$box = $modchain[4]->display_content();
	$ret .= sprintf('<td class="total %s" style="%s">%s</td>',
			$modchain[4]->display_css_class,$modchain[4]->display_css,$box);
	$ret .= "</tr>";
	$ret .= "</table>";

	if (!$readOnly) {
		$ret .= "<form name='hidden'>\n";
		$ret .= "<input type='hidden' id='ccTermOut' name='ccTermOut' value=\"".CoreLocal::get("ccTermOut")."\">\n";
		$ret .= "</form>";
		CoreLocal::set("ccTermOut","idle");
	}

	return $ret;
}

//--------------------------------------------------------------------//

/**
   Wrap a message in a id="plainmsg" div
   @param $strmsg the message
   @return An HTML string
*/
static public function plainmsg($strmsg) 
{
	return "<div id=\"plainmsg\" class=\"coloredText\">$strmsg</div>";
}


//-------------------------------------------------------------------//

/**
  Get a centered message box
  @param $strmsg the message
  @param $icon graphic icon file
  @param $noBeep don't send a scale beep
  @param $buttons keyed array of touchable/clickable buttons
    - key is the text shown on the button
    - value is javascript executed onclick
  @return An HTML string

  This function will include the header
  printheaderb(). 
*/
static public function msgbox($strmsg, $icon, $noBeep=false, $buttons=array()) 
{
	$ret = self::printheaderb();
	$ret .= "<div id=\"boxMsg\" class=\"centeredDisplay\">";
	$ret .= "<div class=\"boxMsgAlert coloredArea\">";
	$ret .= CoreLocal::get("alertBar");
    if (CoreLocal::get('alertBar') == '') {
        $ret .= 'Alert';
    }
	$ret .= "</div>";
	$ret .= "
        <div class=\"boxMsgBody\">
            <div class=\"msgicon\"><img src=\"$icon\" /></div>
	        <div class=\"msgtext\">"
	            . $strmsg . "
	        </div>
            <div class=\"clear\"></div>
        </div>";
    if (!empty($buttons) && is_array($buttons)) {
        $ret .= '<div class="boxMsgBody boxMsgButtons">';
        foreach ($buttons as $label => $action) {
            $label = preg_replace('/(\[.+?\])/', '<span class="smaller">\1</span>', $label);
            $color = preg_match('/\[clear\]/i', $label) ? 'errorColoredArea' : 'coloredArea';
            $ret .= sprintf('<button type="button" class="pos-button %s" 
                        onclick="%s">%s</button>',
                        $color, $action, $label);
        }
        $ret .= '</div>';
    }
	$ret .= "</div>"; // close #boxMsg

    // input has probably already been marked up for display. 
    // no need to re-wrap in various <div>s
    if (strstr($strmsg, 'id="boxMsg"') && strstr($strmsg, 'class="boxMsgBody"')) {
        $ret = $strmsg;
    }

	if (!$noBeep) {
		MiscLib::errorBeep();
    }

	CoreLocal::set("strRemembered",CoreLocal::get("strEntered"));
	CoreLocal::set("msgrepeat",1);

	return $ret;
}
//--------------------------------------------------------------------//

/**
  Get a centered message box with "crossD" graphic
  @param $strmsg the message
  @param $buttons see msgbox()
  @return An HTML string

  An alias for msgbox().
*/
static public function xboxMsg($strmsg, $buttons=array()) 
{
	return self::msgbox($strmsg, MiscLib::base_url()."graphics/crossD.gif", false, $buttons);
}

/**
  Get a centered message box with "exclaimC" graphic
  @param $strmsg the message
  @param $header title for the box
  @param $noBeep don't beep scale
  @param $buttons see msgbox()
  @return An HTML string

  An alias for msgbox().
*/
static public function boxMsg($strmsg, $header="", $noBeep=false, $buttons=array()) 
{
    $default = CoreLocal::get('alertBar');
    if (!empty($header)) {
        CoreLocal::set('alertBar', $header);
    }
	$ret = self::msgbox($strmsg, MiscLib::base_url()."graphics/exclaimC.gif", $noBeep, $buttons);
    CoreLocal::set('alertBar', $default);

    return $ret;
}

/**
  Get a centered message box with input unknown message.
  @return An HTML string

  An alias for msgbox().
*/
static public function inputUnknown() 
{
	return self::msgbox(
        "<b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . _("input unknown") . "</b>", 
        MiscLib::base_url()."graphics/exclaimC.gif", 
        true,
        self::standardClearButton()
    );
}

static public function standardClearButton()
{
    return array('[Clear]' => 'parseWrapper(\'CL\');');
}

//--------------------------------------------------------------------//

/**
  Get the standard header row with CASHIER
  and MEMBER info
  @return An HTML string
*/
static public function printheaderb() 
{

	$strmemberID = "";
	if (CoreLocal::get("memberID") == "0") {
		$strmemberID = "";
	} else {
		$strmemberID = CoreLocal::get("memMsg");
		if (CoreLocal::get("isMember") == 0) {
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
			'.CoreLocal::get("cashier").'
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
static public function printItem($field2, $field3, $total, $field5, $trans_id=-1) 
{
	$onclick = "";
	if ($trans_id != -1) {
		$curID = CoreLocal::get("currentid");
		$diff = $trans_id - $curID;
		if ($diff > 0) {
			$onclick="onclick=\"parseWrapper('D$diff');\"";
		} else if ($diff < 0) {
			$diff *= -1;
			$onclick="onclick=\"parseWrapper('U$diff');\"";
		}
	}	

	if (strlen($total) > 0) {
		$total = number_format($total, 2);
	} else {
		$total = "&nbsp;";
	}
	if ($field2 == "") {
        $field2 = "&nbsp;";
    }
	if (trim($field3) == "") {
        $field3 = "&nbsp;";
    }
	if ($field5 == "") {
        $field5 = "&nbsp;";
    }

	$ret = "<div class=\"item\">";
	$ret .= "<div $onclick class=\"desc coloredText\">$field2</div>";
	$ret .= "<div $onclick class=\"comments lightestColorText\">$field3</div>";
	$ret .= "<div $onclick class=\"total lightestColorText\">$total</div>";
	$ret .= "<div $onclick class=\"suffix lightestColorText\">$field5</div>";
	$ret .= "</div>";
	$ret .= "<div style=\"clear:left;\"></div>\n";

	return $ret;
}

//------------------------------------------------------------------//

/**
  Get a transaction line item in a specific color
  @param $color is a hex color code (do not include a '#')
	(see CSS notes)
  @param $description typically description
  @param $comments comment section. Used for things
   like "0.59@1.99" on weight items.
  @param $total the right-hand number
  @param $suffix flags after the number
  @param $trans_id value from localtemptrans. Including
   the trans_id makes the lines selectable via mouseclick
   (or touchscreen).
  @return An HTML string

  CSS Notes:
  In an effort to replace hard-coded colors, some values are
  re-written as CSS classes rather than inline styles.
  Current mapping:
  - 004080 => coloredText
  - 408080 => lightColorText
  - 000000 => totalLine
  - 800080 => fsLine
*/
static public function printItemColor($color, $description, $comments, $total, $suffix,$trans_id=-1) 
{
	$onclick = "";
	if ($trans_id != -1) {
		$curID = CoreLocal::get("currentid");
		$diff = $trans_id - $curID;
		if ($diff > 0) {
			$onclick="onclick=\"parseWrapper('D$diff');\"";
		} else if ($diff < 0){
			$diff *= -1;
			$onclick="onclick=\"parseWrapper('U$diff');\"";
		}
	}	

	if (strlen($total) > 0) {
		$total = number_format($total, 2);
	} else {
		$total = "&nbsp;";
	}
	if ($total == 0 && $color == "408080") {
        $total = "&nbsp;";
    }
	if ($description == "") {
        $description = "&nbsp;";
    }
	if (trim($comments) == "") {
        $comments = "&nbsp;";
    }
	if ($suffix == "") {
        $suffix = "&nbsp;";
    }

	$style = '';
	$class = '';
	if ($color == '004080') {
		$class = 'coloredText';
	} else if ($color == '000000') {
		$class = 'totalLine';
	} else if ($color == '800080') {
		$class = 'fsLine';
	} else if ($color == '408080') {
		$class = 'lightColorText';
	} else {
		$style = "style=\"color:#$color;\"";
    }

	$ret = "<div class=\"item\">";
	$ret .= "<div $onclick class=\"desc $class\" $style>$description</div>";
	$ret .= "<div $onclick class=\"comments $class\" $style>$comments</div>";
	$ret .= "<div $onclick class=\"total $class\" $style>$total</div>";
	$ret .= "<div $onclick class=\"suffix $class\" $style>$suffix</div>";
	$ret .= "</div>";
	$ret .= "<div style=\"clear:left;\"></div>\n";

	return $ret;
}

//----------------------------------------------------------------//

/**
  Get a transaction line item in a specific color
  @param $color is a hex color code (do not include a '#')
	(see CSS notes)
  @param $description typically description
  @param $comments comment section. Used for things
   like "0.59@1.99" on weight items.
  @param $total the right-hand number
  @param $suffix flags after the number
  @return An HTML string

  CSS Notes:
  In an effort to replace hard-coded colors, some values are
  re-written as CSS classes rather than inline styles.
  Current mapping:
  - 004080 => coloredArea
  - 408080 => lightColorArea
  - 000000 => totalArea
  - 800080 => fsArea
*/
static public function printItemColorHilite($color, $description, $comments, $total, $suffix) 
{
	if (strlen($total) > 0) {
		$total = number_format($total, 2);
	} else {
		$total = "&nbsp;";
	}
	if ($total == 0 && $color == "408080") {
        $total = "&nbsp;";
    }
	if ($description == "") {
        $description="&nbsp;";
    }
	if (trim($comments) == "") {
        $comments="&nbsp;";
    }
	if ($suffix == "") {
        $suffix="&nbsp;";
    }

	$style = '';
	$class = '';
	if ($color == '004080') {
		$class = 'coloredArea';
	} else if ($color == '000000') {
		$class = 'totalArea';
	} else if ($color == '800080') {
		$class = 'fsArea';
	} else if ($color == '408080') {
		$class = 'lightColorArea';
	} else {
		$style = "style=\"background:#$color;color:#ffffff;\"";
    }

	$ret = "<div class=\"item\">";
	$ret .= "<div class=\"desc $class\" $style>$description</div>";
	$ret .= "<div class=\"comments $class\" $style>$comments</div>";
	$ret .= "<div class=\"total $class\" $style>$total</div>";
	$ret .= "<div class=\"suffix $class\" $style>$suffix</div>";
	$ret .= "</div>";
	$ret .= "<div style=\"clear:left;\"></div>\n";

	return $ret;
}

//----------------------------------------------------------------//

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
static public function scaledisplaymsg($input="")
{
	$reginput = trim(strtoupper($input));

	$scans = '';

	// return early; all other cases simplified
	// by resetting session "weight"
	if (strlen($reginput) == 0) {
		if (is_numeric(CoreLocal::get("weight"))) {
			return number_format(CoreLocal::get("weight"), 2)." lb";
		} else {
			return CoreLocal::get("weight")." lb";
        }
	}

	$display_weight = "";
	$weight = 0;
	CoreLocal::set("scale",0);
	CoreLocal::set("weight",0);

	$prefix = "NonsenseThatWillNotEverHappen";
	if (substr($reginput, 0, 3) == "S11")
		$prefix = "S11";
	else if (substr($reginput,0,4)=="S144")	
		$prefix = "S144";

	if (strpos($reginput, $prefix) === 0) {
		$len = strlen($prefix);
		if (!substr($reginput, $len) || 
		    !is_numeric(substr($reginput, $len))) {
			$display_weight = "_ _ _ _";
		} else {
			$weight = number_format(substr($reginput, $len)/100, 2);
			CoreLocal::set("weight",$weight);
			$display_weight = $weight." lb";
			CoreLocal::set("scale",1);
			if (CoreLocal::get('SNR') != 0 && $weight != 0) {
				$scans = CoreLocal::get('SNR');
			}
		}
	} elseif (substr($reginput, 0, 4) == "S143") {
		$display_weight = "0.00 lb";
		CoreLocal::set("scale",1);
	} elseif (substr($reginput, 0, 4) == "S141") {
		$display_weight = "_ _ _ _";
	} elseif (substr($reginput, 0, 4) == "S145") {
		$display_weight = "err -0";
	} elseif (substr($reginput, 0, 4) == "S142") {
		$display_weight = "error";
	} else {
		$display_weight = "? ? ? ?";
	}

	$ret = array('display'=>$display_weight);
	if (!empty($scans)) {
        $ret['upc'] = $scans;
    }

	return $ret;
}

/**
  Display CC terminal state
  @return HTML string
*/
static public function termdisplaymsg()
{
	if (!in_array("Paycards",CoreLocal::get("PluginList"))) {
		return '';
	} elseif(CoreLocal::get("PaycardsCashierFacing")=="1") {
		return '';
    }
	// style box to look like a little screen
	$ret = '<div style="background:#ccc;border:solid 1px black;padding:7px;text-align:center;font-size:120%;">';
	$rdy = '<div style="background:#0c0;border:solid 1px black;padding:7px;text-align:center;font-size:120%;">';
	switch(CoreLocal::get('ccTermState')) {
        case 'swipe':
            return $ret.'Slide<br />Card</div>';
            break;
        case 'ready':
            return $rdy.'Ready</div>';
            break;
        case 'pin':
            return $ret.'Enter<br />PIN</div>';
            break;
        case 'type':
            return $ret.'Card<br />Type</div>';
            break;
        case 'cashback':
            return $ret.'Cash<br />Back</div>';
            break;
	}

	return '';
}

/**
  Use the right side of the screen to show various
  notifications
*/
static public function drawNotifications()
{
    if (!is_array(CoreLocal::get('Notifiers'))) {
        CoreLocal::set('Notifiers', array());
    }

    $ret = '';
    foreach(CoreLocal::get('Notifiers') as $class) {
        if (!class_exists($class)) continue;

        $obj = new $class();
        $ret .= $obj->draw();
    }

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
static public function listItems($top_item, $highlight) 
{
    $lines = CoreLocal::get('screenLines');
    if (!$lines === '' || !is_numeric($lines)) {
        $lines = 11;
    }

	Database::getsubtotals();
	$LastID = CoreLocal::get("LastID");

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

	if ($highlight > ($top_item + $lines)) {
		$top_item = ($highlight - $lines);
	}

	CoreLocal::set("currenttopid",$top_item);
	CoreLocal::set("currentid",$highlight);

//------------------Boundary Bottom----------------

	CoreLocal::set("currentid",$highlight);

	return self::drawItems($top_item, $lines, $highlight);
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
static public function printReceiptfooter($readOnly=False) 
{
	if (!$readOnly) {
		Database::getsubtotals();
    }
	$last_id = CoreLocal::get("LastID");

	if (($last_id - 7) < 0) {
		$top_id = 1;
	} else {
		$top_id = $last_id - 7;
	}

	$ret = self::drawitems($top_id, 7, 0);

	$ret .= "<div class=\"farewellMsg coloredText\">";
	for($i=0;$i<=CoreLocal::get("farewellMsgCount");$i++) {
		$ret .= CoreLocal::get("farewellMsg".$i)."<br />";
	}

	$email = CoreState::getCustomerPref('email_receipt');
	$doEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
	if($doEmail) {
        $ret .= 'receipt emailed';
    }

	$ret .= "</div>";

	return $ret;
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
static public function drawItems($top_item, $rows, $highlight) 
{
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
		if (CoreLocal::get("training") != 1) {
			for($i=1; $i<=CoreLocal::get("welcomeMsgCount");$i++) {
				$msg_text .= CoreLocal::get("welcomeMsg".$i)."<br />";
			}	
		} else {
			for($i=1; $i<=CoreLocal::get("trainingMsgCount");$i++) {
				$msg_text .= CoreLocal::get("trainingMsg".$i)."<br />";
			}	
		}
		$ret .= self::plainmsg($msg_text);
		$ret .= "</div>";
	} else {

		$query_range = "select trans_id,description,total,comment,status,lineColor
		       		from screendisplay where trans_id >= ".$top_item." and trans_id <= "
				.($top_item + $rows)." order by trans_id";
		$db_range = Database::tDataConnect();
		$result_range = $db_range->query($query_range);
		$num_rows = $db_range->num_rows($result_range);
        $screenRecords = array();
		for ($i = 0; $i < $num_rows; $i++) {
			$screenRecords[] = $db_range->fetch_array($result_range);
        }
        /**
          30Oct2014 Andy
          Idea here is to look up the currently displayed items and
          perform any necessary transformations of the text in PHP instead
          of tucking that logic inside the screendisplay view. I'm
          leaving the query above for reference in case I screwed something
          up and the old method of drawing the screen needs to be
          quickly re-enabled.

          14Nov2014 Andy
          Disabled for the sake of putting together a release.
          Will go into testing following the release and be included in
          the next one

          03Dec2014 Andy
          Axing screen display isn't a super high priority right now
          With better unit testing in place, I want to write more tests
          to verify this method behaves the same as the screendisplay via.
          No ETA at this point.
        */
        //$screenRecords = self::screenDisplay($top_item, $top_item + $rows);

        foreach ($screenRecords as $row) {

			$trans_id = $row["trans_id"];
			$description = $row["description"];
			$total = $row["total"];
			$comment = $row["comment"];
			$tf = $row["status"];
			$color = $row["lineColor"];

			
			if ($trans_id == $highlight) {
				$ret .= self::printItemColorHilite($color, $description, $comment, $total, $tf);
			} else {
				if ($color == "004080") {
					$ret .= self::printItem($description, $comment, $total, $tf,$trans_id);
				} else {
					$ret .= self::printItemColor($color, $description, $comment, $total, $tf,$trans_id);
				}				
			}

			if (!strstr($description,'Subtotal')) {
				$fixed_desc = str_replace(":"," ",$description);
				if (strlen($fixed_desc) > 24) {
					$fixed_desc = substr($fixed_desc,0,24);
				}
				$fixed_price = empty($total)?'':sprintf('%.2f',$total);
				$spaces = str_pad('',30-strlen($fixed_desc)-strlen($fixed_price),' ');
				$last_item[] = $fixed_desc.$spaces.$fixed_price;
			}
		}
	}

    /** 11Mar14 Andy
        Ancient idea about displaying transaction line-items
        on credit card terminal screen. Current terminal
        does not even support this functionality.

        I'm leaving the "get last relevant line" implementation
        for reference.
	if (is_object($td) && !empty($last_item)) {
		$due = sprintf('%.2f',CoreLocal::get("amtdue"));
		$dueline = 'Subtotal'
			.str_pad('',22-strlen($due),' ')
			.$due;
		$items = "";
		$count = 0;
		for($i=count($last_item)-1;$i>=0;$i--) {
			$items = ":".$last_item[$i].$items;
			$count++;
			if ($count >= 3) break;
		}
		for($i=$count;$i<3;$i++) {
			$items = ": ".$items;
        }
		$td->WriteToScale("display:".$last_item.":".$dueline);
	}
    */

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
static public function lastpage($readOnly=False) 
{
    $lines = CoreLocal::get('screenLines');
    if (!$lines === '' || !is_numeric($lines)) {
        $lines = 11;
    }

	if (!$readOnly) {
		Database::getsubtotals();
	}
	$last_id = CoreLocal::get("LastID");

	if (($last_id - $lines) < 0) {
		$top_id = 1;
	} else {
		$top_id = $last_id - $lines;
	}
	
	if (!$readOnly) {
		CoreLocal::set("currentid",$last_id);
		CoreLocal::set("currenttopid",$top_id);
	}
	return self::drawItems($top_id, $lines, $last_id);
}

/**
  Select items from the transaction with formatting for on screen display
  @param $min [int] minimum localtemptrans.trans_id
  @param $max [int] maximum localtemtprans.trans_id
  @return array of records

  Each record contains the following keys:
  - description
  - comment
  - total
  - status
  - discounttype
  - trans_status
  - trans_type
  - voided
  - trans_id

  Note: the outer array is indexed by localtemptrans.trans_id
  instead of zero through array.length.
*/
static public function screenDisplay($min, $max)
{
    $dbc = Database::tDataConnect();
    $query = "SELECT l.*, t.description AS tax_description
              FROM localtemptrans AS l
                LEFT JOIN taxrates AS t ON l.tax=t.id
              WHERE l.trans_type <> 'L'
                AND l.trans_id BETWEEN ? AND ?
              ORDER BY l.trans_id";
    $prep = $dbc->prepare($query);
    $result = $dbc->execute($prep, array($min, $max));
    $ret = array();
    while ($row = $dbc->fetch_row($result)) {
        $record = array();

        if ($row['voided'] == 5 || $row['voided'] == 11 || $row['voided'] == 17 || $row['trans_type'] == 'T') {
            $record['description'] = '';
        } else {
            $record['description'] = $row['description'];
        }

        if ($row['discounttype'] == 3 && $row['trans_status'] == 'V') {
            $record['comment'] = $row['ItemQtty'] . ' /' . $row['unitPrice'];
        } elseif ($row['voided'] == 5) {
            $record['comment'] = 'Discount';
        } elseif ($row['trans_status'] == 'M') {
            $record['comment'] = 'Mbr special';
        } elseif ($row['trans_status'] == 'S') {
            $record['comment'] = 'Staff special';
        } elseif ($row['scale'] != 0 && $row['quantity'] != 0 && $row['unitPrice'] != 0.01) {
            $record['comment'] = $row['quantity'] . ' @ ' . $row['unitPrice'];
        } elseif (substr($row['upc'], 0, 2) == '002') {
            $record['comment'] = $row['ItemQtty'] . ' @ ' . $row['regPrice'];
        } elseif (abs($row['ItemQtty']) > 1 && abs($row['ItemQtty']) > abs($row['quantity']) && $row['discounttype'] != 3 && $row['quantity'] == 1) {
            $record['comment'] = $row['volume'] . ' for ' . $row['unitPrice'];
        } elseif (abs($row['ItemQtty']) > 1 && abs($row['ItemQtty']) > abs($row['quantity']) && $row['discounttype'] != 3 && $row['quantity'] != 1) {
            $record['comment'] = $row['quantity'] . ' @ ' . $row['volume'] . ' for ' . $row['unitPrice'];
        } elseif (abs($row['ItemQtty']) > 1 && $row['discounttype'] == 3) {
            $record['comment'] = $row['ItemQtty'] . ' / ' . $row['unitPrice'];
        } elseif (abs($row['ItemQtty']) > 1) {
            $record['comment'] = $row['ItemQtty'] . ' @ ' . $row['unitPrice'];
        } elseif ($row['voided'] == 3) {
            $record['comment'] = _('Total ');
        } elseif ($row['voided'] == 5) {
            $record['comment'] = _('Discount');
        } elseif ($row['voided'] == 7) {
            $record['comment'] = '';
        } elseif ($row['voided'] == 11 || $row['voided'] == 17) {
            $record['comment'] = $row['upc'];
        } elseif ($row['matched'] > 0) {
            $record['comment'] = _('1 w/ vol adj');
        } elseif ($row['trans_type'] == 'T') {
            $record['comment'] = $row['description'];
        } else {
            $record['comment'] = '';
        }

        if ($row['voided'] == 3 || $row['voided'] == 5 || $row['voided'] == 7 || $row['voided'] == 11 || $row['voided'] == 17) {
            $record['total'] = $row['unitPrice'];
        } elseif ($row['trans_status'] == 'D') {
            $record['total'] = '';
        } else {
            $record['total'] = $row['total'];
        }

        if ($row['trans_status'] == 'V') {
            $record['status'] = 'VD';
        } elseif ($row['trans_status'] == 'R') {
            $record['status'] = 'RF';
        } elseif ($row['trans_status'] == 'C') {
            $record['status'] = 'MC';
        } elseif ($row['trans_type'] == 'T' && $row['charflag'] == 'PT') {
            $record['status'] = 'PC';
        } elseif ($row['tax'] == 1 && $row['foodstamp'] != 0) {
            $record['status'] = 'TF';
        } elseif ($row['tax'] == 1 && $row['foodstamp'] == 0) {
            $record['status'] = 'T';
        } elseif ($row['tax'] > 1 && $row['foodstamp'] != 0) {
            $record['status'] = substr($row['tax_description'], 0 , 1) . 'F';
        } elseif ($row['tax'] > 1 && $row['foodstamp'] == 0) {
            $record['status'] = substr($row['tax_description'], 0 , 1);
        } elseif ($row['tax'] == 0 && $row['foodstamp'] != 0) {
            $record['status'] = 'F';
        } else {
            $record['status'] = '';
        }

        if ($row['trans_status'] == 'V' || $row['trans_type'] == 'T' || $row['trans_status'] == 'R' || $row['trans_status'] == 'M' || $row['voided'] == 17 || $row['trans_status'] == 'J') {
            $record['lineColor'] = '800000';
        } elseif (($row['discounttype'] != 0 && ($row['matched'] > 0 || $row['volDiscType'] == 0)) 
            || $row['voided'] == 2 || $row['voided'] == 6 || $row['voided'] == 4 || $row['voided'] == 5 || $row['voided'] == 10 || $row['voided'] == 22) {
            $record['lineColor'] = '408080';
        } elseif ($row['voided'] == 3 || $row['voided'] == 11) {
            $record['lineColor'] = '000000';
        } elseif ($row['voided'] == 7) {
            $record['lineColor'] = '800080';
        } else {
            $record['lineColor'] = '004080';
        }

        $record['discounttype'] = $row['discounttype'];
        $record['trans_type'] = $row['trans_type'];
        $record['trans_status'] = $row['trans_status'];
        $record['voided'] = $row['voided'];
        $record['trans_id'] = $row['trans_id'];
        
        $ret[$row['trans_id']] = $record;
    }

    return $ret;
}

static public function touchScreenScrollButtons($selector='#search')
{
    $stem = MiscLib::baseURL() . 'graphics/';
    return '
        <button type="button" class="pos-button coloredArea"
            onclick="pageUp(\''. $selector . '\');">
            <img src="' . $stem . 'pageup.png" width="16" height="16" />
        </button>
        <br /><br />
        <button type="button" class="pos-button coloredArea"
            onclick="scrollUp(\''. $selector . '\');">
            <img src="' . $stem . 'up.png" width="16" height="16" />
        </button>
        <br /><br />
        <button type="button" class="pos-button coloredArea"
            onclick="scrollDown(\''. $selector . '\');">
            <img src="' . $stem . 'down.png" width="16" height="16" />
        </button>
        <br /><br />
        <button type="button" class="pos-button coloredArea"
            onclick="pageDown(\''. $selector . '\');">
            <img src="' . $stem . 'pagedown.png" width="16" height="16" />
        </button>';
}

} // end class DisplayLib

