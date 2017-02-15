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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\FooterBoxes\FooterBox;
use \CoreLocal;

/**
  @class DisplayLib
  Functions for drawing display elements
*/
class DisplayLib 
{

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
    
    $modchain = array_map(function($class){ return FooterBox::factory($class); }, $FOOTER_MODULES);

    if (!$readOnly) {
        CoreLocal::set("runningTotal",CoreLocal::get("amtdue"));
    }
    if (CoreLocal::get("End") == 1 && !$readOnly) {
        CoreLocal::set("runningTotal",-1 * CoreLocal::get("change"));
    }
    $weight = "_ _ _ _";
    if (CoreLocal::get("scale") == 1) {
        $weight = number_format(CoreLocal::get("weight"), 2)."lb.";
    }

    $ret = "<table>";
    $ret .= "<tr class=\"heading\">";
    $classes = array('first', 'reg', 'reg', 'reg', 'total');
    for ($i=0; $i<count($modchain); $i++) {
        $label = $modchain[$i]->header_content();
        $ret .= sprintf('<td class="%s %s" style="%s">%s</td>',
                $classes[$i], $modchain[$i]->header_css_class, $modchain[$i]->header_css,$label);
    }
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
    for ($i=0; $i<count($modchain); $i++) {
        $box = $modchain[$i]->display_content();
        $ret .= sprintf('<td class="%s %s" style="%s">%s</td>',
                $classes[$i],$modchain[$i]->display_css_class,$modchain[$i]->display_css,$box);
    }
    $ret .= "</tr>";
    $ret .= "</table>";
    if (CoreLocal::get('Debug_JS')) {
        $ret .= '<div id="jsErrorLog"></div>';
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
    // @hintable
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
    // @hintable
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
    // @hintable
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
    return array(_('[Clear]') => 'parseWrapper(\'CL\');');
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
    if (CoreLocal::get("memberID") != "0") {
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

static private function itemOnClick($transID)
{
    $onclick = "";
    if ($transID != -1) {
        $curID = CoreLocal::get("currentid");
        $diff = $transID - $curID;
        if ($diff > 0) {
            $onclick="onclick=\"parseWrapper('D$diff');\"";
        } elseif ($diff < 0){
            $diff *= -1;
            $onclick="onclick=\"parseWrapper('U$diff');\"";
        }
    } 

    return $onclick;
}

//-------------------------------------------------------------------//

/**
  Get a transaction line item
  @param $fields [array] of entries (left-to-right)
  @param $transID value from localtemptrans. Including
   the transID makes the lines selectable via mouseclick
   (or touchscreen).
  @return An HTML string
*/
    // @hintable
static public function printItem($fields, $transID=-1) 
{
    $onclick = self::itemOnClick($transID);

    $total = self::displayableText($fields[2], false, true);
    $description = self::displayableText($fields[0], false, false);
    $comments = self::displayableText($fields[1], false, false);
    $suffix = self::displayableText($fields[3], false, false);

    $ret = "<div class=\"item\">";
    $ret .= "<div $onclick class=\"desc coloredText\">$description</div>";
    $ret .= "<div $onclick class=\"comments lightestColorText\">$comments</div>";
    $ret .= "<div $onclick class=\"total lightestColorText\">$total</div>";
    $ret .= "<div $onclick class=\"suffix lightestColorText\">$suffix</div>";
    $ret .= "</div>";
    $ret .= "<div style=\"clear:left;\"></div>\n";

    return $ret;
}

//------------------------------------------------------------------//

/**
  Get a transaction line item in a specific color
  @param $color is a hex color code (do not include a '#')
    (see CSS notes)
  @param $fields [array] of entries (left-to-right)
  @param $transID value from localtemptrans. Including
   the transID makes the lines selectable via mouseclick
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
    // @hintable
static public function printItemColor($color, $fields, $transID=-1) 
{
    $onclick = self::itemOnClick($transID);

    $total = self::displayableText($fields[2], true, true);
    $description = self::displayableText($fields[0], true, false);
    $comments = self::displayableText($fields[1], true, false);
    $suffix = self::displayableText($fields[3], true, false);

    list($class, $style) = self::colorToCSS($color, true);

    $ret = "<div class=\"item\">";
    $ret .= "<div $onclick class=\"desc $class\" $style>$description</div>";
    $ret .= "<div $onclick class=\"comments $class\" $style>$comments</div>";
    $ret .= "<div $onclick class=\"total $class\" $style>$total</div>";
    $ret .= "<div $onclick class=\"suffix $class\" $style>$suffix</div>";
    $ret .= "</div>";
    $ret .= "<div style=\"clear:left;\"></div>\n";

    return $ret;
}

private static function colorToCSS($color, $text=true)
{
    if ($color == '004080') {
        return array($text ? 'coloredText' : 'coloredArea', '');
    } elseif ($color == '00000') {
        return array($text ? 'totalLine' : 'totalArea', '');
    } elseif ($color == '800080') {
        return array($text ? 'fsLine' : 'fsArea', '');
    } elseif ($color == '408080') {
        return array($text ? 'lightColorText' : 'lightColorArea', '');
    } elseif ($text) {
        return array('', "style=\"color:#$color;\"");
    }
    return array('', "style=\"background:#$color;color:#ffffff;\"");
}

//----------------------------------------------------------------//

/**
  Get a transaction line item in a specific color
  @param $color is a hex color code (do not include a '#')
    (see CSS notes)
  @param $fields [array] of entries (left-to-right)
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
    // @hintable
static public function printItemColorHilite($color, $fields)
{
    $total = self::displayableText($fields[2], true, true);
    $description = self::displayableText($fields[0], true, false);
    $comments = self::displayableText($fields[1], true, false);
    $suffix = self::displayableText($fields[3], true, false);

    list($class, $style) = self::colorToCSS($color, false);

    $ret = "<div class=\"item\">";
    $ret .= "<div class=\"desc $class\" $style>$description</div>";
    $ret .= "<div class=\"comments $class\" $style>$comments</div>";
    $ret .= "<div class=\"total $class\" $style>$total</div>";
    $ret .= "<div class=\"suffix $class\" $style>$suffix</div>";
    $ret .= "</div>";
    $ret .= "<div style=\"clear:left;\"></div>\n";

    return $ret;
}

private static function displayableText($field, $color=true, $total=true)
{
    if ($total === false) {
        return trim($field) == '' ? '&nbsp;' : $field;
    } elseif ($field == 0 && $color == "408080") {
        return "&nbsp;";
    } elseif (is_numeric($field) && strlen($field) > 0) {
        return number_format($field, 2);
    }
    return $field;
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
        }
        return CoreLocal::get("weight")." lb";
    }

    $display_weight = "? ? ? ?";
    $weight = 0;
    CoreLocal::set("scale",0);
    CoreLocal::set("weight",0);

    $prefix = "NonsenseThatWillNotEverHappen";
    if (substr($reginput, 0, 3) == "S11")
        $prefix = "S11";
    elseif (substr($reginput,0,4)=="S144")    
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
        $display_weight = _("err -0");
    } elseif (substr($reginput, 0, 4) == "S142") {
        $display_weight = _("error");
    }

    $ret = array('display'=>$display_weight);
    if (!empty($scans)) {
        $ret['upc'] = $scans;
    }

    return $ret;
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
  @param $topItem is trans_id (localtemptrans)
   of the first item to display
  @param $highlight is the trans_id (localtemptrans)
   of the currently selected item
  @return An HTML string

  If you just want to show the most recently
  scanned items, use lastpage().
*/
static public function listItems($topItem, $highlight) 
{
    $lines = self::screenLines();

    Database::getsubtotals();
    $LastID = CoreLocal::get("LastID");

//----------------Boundary Top ------------------

    if ($highlight < 1) {
        $highlight = 1;
        $topItem = 1;
    }
    
    if ($highlight > $LastID) {
        $highlight = $LastID;
    }

    if ($highlight < $topItem) {
        $topItem = $highlight;
    }

    if ($highlight > ($topItem + $lines)) {
        $topItem = ($highlight - $lines);
    }

    CoreLocal::set("currenttopid",$topItem);
    CoreLocal::set("currentid",$highlight);

//------------------Boundary Bottom----------------

    CoreLocal::set("currentid",$highlight);

    return self::drawItems($topItem, $lines, $highlight);
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
    $lastID = CoreLocal::get("LastID");

    $topID = $lastID - 7;
    if (($lastID - 7) < 0) {
        $topID = 1;
    }

    $ret = self::drawitems($topID, 7, 0);

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
  @param $topItem is the trans_id of the first item to display
  @param $rows is the number of items to display
  @param $highlight is the trans_id of the selected item
  @return An HTML string

  This function probably shouldn't be used directly.
  Call listitems() or lastpage() instead.
*/
static public function drawItems($topItem, $rows, $highlight) 
{
    $ret = self::printheaderb();

    $query = "select count(*) as count from localtemptrans";
    $dbc = Database::tDataConnect();
    $result = $dbc->query($query);
    $row = $dbc->fetchRow($result);
    $rowCount = $row["count"];

    if ($rowCount == 0) {
        $ret .= "<div class=\"centerOffset\">";
        $msg_text = "";
        $type = CoreLocal::get('training') != 1 ? 'welcomeMsg' : 'trainingMsg';
        for($i=1; $i<=CoreLocal::get($type . "Count");$i++) {
            $msg_text .= CoreLocal::get($type.$i)."<br />";
        }    
        $ret .= self::plainmsg($msg_text);
        $ret .= "</div>";
    } else {

        $query_range = "select trans_id,description,total,comment,status,lineColor
                       from screendisplay where trans_id >= ".$topItem." and trans_id <= "
                .($topItem + $rows)." order by trans_id";
        $db_range = Database::tDataConnect();
        $result_range = $db_range->query($query_range);
        $screenRecords = array();
        while ($row = $db_range->fetchRow($result_range)) {
            $screenRecords[] = $row;
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
        //$screenRecords = self::screenDisplay($topItem, $topItem + $rows);

        foreach ($screenRecords as $row) {

            $transID = $row["trans_id"];
            $description = $row["description"];
            $total = $row["total"];
            $comment = $row["comment"];
            $tfStatus = $row["status"];
            $color = $row["lineColor"];

            
            if ($transID == $highlight) {
                $ret .= self::printItemColorHilite($color, array($description, $comment, $total, $tfStatus));
            } elseif ($color == "004080") {
                $ret .= self::printItem(array($description, $comment, $total, $tfStatus),$transID);
            } else {
                $ret .= self::printItemColor($color, array($description, $comment, $total, $tfStatus),$transID);
            }                
        }
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
static public function lastpage($readOnly=False) 
{
    $lines = self::screenLines();

    if (!$readOnly) {
        Database::getsubtotals();
    }
    $lastID = CoreLocal::get("LastID");

    $topID = $lastID - $lines;
    if (($lastID - $lines) < 0) {
        $topID = 1;
    }
    
    if (!$readOnly) {
        CoreLocal::set("currentid",$lastID);
        CoreLocal::set("currenttopid",$topID);
    }
    return self::drawItems($topID, $lines, $lastID);
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

        $record['description'] = self::screenDisplayDescription($row);
        $record['comment'] = self::screenDisplayComment($row);
        $record['total'] = self::screenDisplayTotal($row);
        $record['status'] = self::screenDisplayStatus($row);
        $record['lineColor'] = self::screenDisplayColor($row);

        $record['discounttype'] = $row['discounttype'];
        $record['trans_type'] = $row['trans_type'];
        $record['trans_status'] = $row['trans_status'];
        $record['voided'] = $row['voided'];
        $record['trans_id'] = $row['trans_id'];
        
        $ret[$row['trans_id']] = $record;
    }

    return $ret;
}

    // @hintable
static private function screenDisplayColor($row)
{
    if ($row['trans_status'] == 'V' || $row['trans_type'] == 'T' || $row['trans_status'] == 'R' || $row['trans_status'] == 'M' || $row['voided'] == 17 || $row['trans_status'] == 'J') {
        return '800000';
    } elseif (($row['discounttype'] != 0 && ($row['matched'] > 0 || $row['volDiscType'] == 0)) 
        || $row['voided'] == 2 || $row['voided'] == 6 || $row['voided'] == 4 || $row['voided'] == 5 || $row['voided'] == 10 || $row['voided'] == 22) {
        return '408080';
    } elseif ($row['voided'] == 3 || $row['voided'] == 11) {
        return '000000';
    } elseif ($row['voided'] == 7) {
        return '800080';
    }
    return '004080';
}

    // @hintable
static private function screenDisplayDescription($row)
{
    if ($row['voided'] == 5 || $row['voided'] == 11 || $row['voided'] == 17 || $row['trans_type'] == 'T') {
        return '';
    }
    return $row['description'];
}

    // @hintable
static private function screenDisplayComment($row)
{
    if ($row['discounttype'] == 3 && $row['trans_status'] == 'V') {
        return $row['ItemQtty'] . ' /' . $row['unitPrice'];
    } elseif ($row['voided'] == 5) {
        return 'Discount';
    } elseif ($row['trans_status'] == 'M') {
        return 'Mbr special';
    } elseif ($row['trans_status'] == 'S') {
        return 'Staff special';
    } elseif ($row['scale'] != 0 && $row['quantity'] != 0 && $row['unitPrice'] != 0.01) {
        return $row['quantity'] . ' @ ' . $row['unitPrice'];
    } elseif (substr($row['upc'], 0, 2) == '002') {
        return $row['ItemQtty'] . ' @ ' . $row['regPrice'];
    } elseif (abs($row['ItemQtty']) > 1 && abs($row['ItemQtty']) > abs($row['quantity']) && $row['discounttype'] != 3 && $row['quantity'] == 1) {
        return $row['volume'] . ' for ' . $row['unitPrice'];
    } elseif (abs($row['ItemQtty']) > 1 && abs($row['ItemQtty']) > abs($row['quantity']) && $row['discounttype'] != 3 && $row['quantity'] != 1) {
        return $row['quantity'] . ' @ ' . $row['volume'] . ' for ' . $row['unitPrice'];
    } elseif (abs($row['ItemQtty']) > 1 && $row['discounttype'] == 3) {
        return $row['ItemQtty'] . ' / ' . $row['unitPrice'];
    } elseif (abs($row['ItemQtty']) > 1) {
        return $row['ItemQtty'] . ' @ ' . $row['unitPrice'];
    } elseif ($row['voided'] == 3) {
        return _('Total ');
    } elseif ($row['voided'] == 5) {
        return _('Discount');
    } elseif ($row['voided'] == 7) {
        return '';
    } elseif ($row['voided'] == 11 || $row['voided'] == 17) {
        return $row['upc'];
    } elseif ($row['matched'] > 0) {
        return _('1 w/ vol adj');
    } elseif ($row['trans_type'] == 'T') {
        return $row['description'];
    }
    return '';
}

    // @hintable
static private function screenDisplayTotal($row)
{
    if ($row['voided'] == 3 || $row['voided'] == 5 || $row['voided'] == 7 || $row['voided'] == 11 || $row['voided'] == 17) {
        return $row['unitPrice'];
    } elseif ($row['trans_status'] == 'D') {
        return '';
    }
    return $row['total'];
}

    // @hintable
static private function screenDisplayStatus($row)
{
    if ($row['trans_status'] == 'V') {
        return 'VD';
    } elseif ($row['trans_status'] == 'R') {
        return 'RF';
    } elseif ($row['trans_status'] == 'C') {
        return 'MC';
    } elseif ($row['trans_type'] == 'T' && $row['charflag'] == 'PT') {
        return 'PC';
    } elseif ($row['tax'] == 1 && $row['foodstamp'] != 0) {
        return 'TF';
    } elseif ($row['tax'] == 1 && $row['foodstamp'] == 0) {
        return 'T';
    } elseif ($row['tax'] > 1 && $row['foodstamp'] != 0) {
        return substr($row['tax_description'], 0 , 1) . 'F';
    } elseif ($row['tax'] > 1 && $row['foodstamp'] == 0) {
        return substr($row['tax_description'], 0 , 1);
    } elseif ($row['tax'] == 0 && $row['foodstamp'] != 0) {
        return 'F';
    }
    return '';
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

static public function screenLines()
{
    $valid = function($var) { return ($var !== '' && is_numeric($var)); };

    return $valid(CoreLocal::get('screenLines')) ? CoreLocal::get('screenLines') : 11;
}

} // end class DisplayLib

