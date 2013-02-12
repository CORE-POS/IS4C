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

if (!isset($CORE_LOCAL)){
	include(realpath(dirname(__FILE__)."/LS_Access.php"));
	$CORE_LOCAL = new LS_Access();	
}

/**
 @class PaycardLib
 @brief Defines constants and functions for card processing.
*/

class PaycardLib {

	const PAYCARD_MODE_BALANCE   	=1;
	const PAYCARD_MODE_AUTH      	=2;
	const PAYCARD_MODE_VOID      	=3; // for voiding tenders/credits, rung in as T
	const PAYCARD_MODE_ACTIVATE  	=4;
	const PAYCARD_MODE_ADDVALUE  	=5;
	const PAYCARD_MODE_VOIDITEM  	=6; // for voiding sales/addvalues, rung in as I
	const PAYCARD_MODE_CASHOUT   	=7; // for cashing out a wedgecard

	const PAYCARD_TYPE_UNKNOWN   	=0;
	const PAYCARD_TYPE_CREDIT    	=1;
	const PAYCARD_TYPE_GIFT      	=2;
	const PAYCARD_TYPE_STORE     	=3;
	const PAYCARD_TYPE_ENCRYPTED   	=4;

	const PAYCARD_ERR_OK         	=1;
	const PAYCARD_ERR_NOSEND    	=-1;
	const PAYCARD_ERR_COMM      	=-2;
	const PAYCARD_ERR_TIMEOUT   	=-3;
	const PAYCARD_ERR_DATA      	=-4;
	const PAYCARD_ERR_PROC      	=-5;
	const PAYCARD_ERR_CONTINUE	=-6;

// identify payment card type, issuer and acceptance based on card number
// individual functions are based on this one
/**
  Identify card based on number
  @param $pan card number
  @return array with keys:
   - 'type' paycard type constant
   - 'issuer' Vista, MasterCard, etc
   - 'accepted' boolean, whether card is accepted
   - 'test' boolean, whether number is a testing card
*/
static public function paycard_info($pan) {
	$len = strlen($pan);
	$iin = (int)substr($pan,0,7);
	$issuer = "Unknown";
	$type = self::PAYCARD_TYPE_UNKNOWN;
	$accepted = false;
	$test = false;
	if( $len >= 13 && $len <= 16) {
		$type = self::PAYCARD_TYPE_CREDIT;
		if(      $iin>=3000000 && $iin<=3099999) { $issuer="Diners Club"; }
		else if( $iin>=3400000 && $iin<=3499999) { $issuer="American Express"; $accepted=true; }
		else if( $iin>=3528000 && $iin<=3589999) { $issuer="JCB";        $accepted=true; } // Japan Credit Bureau, accepted via Discover
		else if( $iin>=3600000 && $iin<=3699999) { $issuer="MasterCard"; $accepted=true; } // Diners Club issued as MC in the US
		else if( $iin>=3700000 && $iin<=3799999) { $issuer="American Express"; $accepted=true; }
		else if( $iin>=3800000 && $iin<=3899999) { $issuer="Diners Club"; } // might be obsolete?
		else if( $iin>=4000000 && $iin<=4999999) { $issuer="Visa";       $accepted=true; }
		else if( $iin>=5100000 && $iin<=5599999) { $issuer="MasterCard"; $accepted=true; }
		else if( $iin>=6011000 && $iin<=6011999) { $issuer="Discover";   $accepted=true; }
		else if( $iin>=6221260 && $iin<=6229259) { $issuer="UnionPay";   $accepted=true; } // China UnionPay, accepted via Discover
		else if( $iin>=6500000 && $iin<=6599999) { $issuer="Discover";   $accepted=true; }
	} else if( $len == 19) {
		$type = self::PAYCARD_TYPE_GIFT;
		if(      $iin>=7019208 && $iin<=7019208) { $issuer="Co-op Gift"; $accepted=true; } // NCGA gift cards
		else if( $iin>=7018525 && $iin<=7018525) { $issuer="Valutec Gift"; $test=true; } // valutec test cards (linked to test merchant/terminal ID)
		else if ($iin>=6050110 && $iin<=6050110) {
			$issuer="Co-Plus Gift Card"; $accepted=true;
		}
	}
	else if (substr($pan,0,8) == "02E60080"){
		$type = self::PAYCARD_TYPE_ENCRYPTED;
		$accepted = true;
	}
	return array('type'=>$type, 'issuer'=>$issuer, 'accepted'=>$accepted, 'test'=>$test);
} // paycard_info()


// determine if we accept the card given the number; return 1 if yes, 0 if no
/**
  Check whether a given card is accepted
  @param $pan the card number
  @param $acceptTest boolean
  @return 
   - 1 if accepted
   - 0 if not accepted

  $acceptTest controls the behavior with
  testing cards. True makes test cards
  accepted.
*/
static public function paycard_accepted($pan, $acceptTest) {
	$info = self::paycard_info($pan);
	/*
	if( $info['test'] && $acceptTest)
		return 1;
	 */
	return ($info['accepted'] ? 1 : 0);
} // paycard_accepted()


/**
  Determine card type
  @param $pan the card number
  @return a paycard type constant
*/
static public function paycard_type($pan) {
	$info = self::paycard_info($pan);
	return $info['type'];
} // paycard_type()


// determine who issued a payment card given the number; return the issuer as a string or "Unknown"
/**
  Get paycard issuer
  @param $pan the card number
  @return string issuer

  Issuers include "Visa", "American Express", "MasterCard",
  and "Discover". Unrecognized cards will return "Unknown".
*/
static public function paycard_issuer($pan) {
	$info = self::paycard_info($pan);
	return $info['issuer'];
} // paycard_issuer()


/**
  Check whether paycards of a given type are enabled
  @param $type is a paycard type constant
  @return
   - 1 if type is enabled
   - 0 if type is disabled
*/
static public function paycard_live($type = self::PAYCARD_TYPE_UNKNOWN) {
	global $CORE_LOCAL;

	// these session vars require training mode no matter what card type
	if( $CORE_LOCAL->get("training") != 0 || $CORE_LOCAL->get("CashierNo") == 9999)
		return 0;
	// special session vars for each card type
	if( $type === self::PAYCARD_TYPE_CREDIT) {
		if( $CORE_LOCAL->get("ccLive") != 1)
			return 0;
	} else if( $type === self::PAYCARD_TYPE_GIFT) {
		if( $CORE_LOCAL->get("training") == 1)
			return 0;
	} else if( $type === self::PAYCARD_TYPE_STORE) {
		if( $CORE_LOCAL->get("storecardLive") != 1)
			return 0;
	}
	return 1;
} // paycard_live()


/**
  Clear paycard variables from $CORE_LOCAL.
*/
static public function paycard_reset() {
	global $CORE_LOCAL;

	// make sure this matches session.php!!!
	$CORE_LOCAL->set("paycard_manual",0);
	$CORE_LOCAL->set("paycard_amount",0.00);
	$CORE_LOCAL->set("paycard_mode",0);
	$CORE_LOCAL->set("paycard_id",0);
	$CORE_LOCAL->set("paycard_PAN",'');
	$CORE_LOCAL->set("paycard_exp",'');
	$CORE_LOCAL->set("paycard_name",'Customer');
	$CORE_LOCAL->set("paycard_tr1",false);
	$CORE_LOCAL->set("paycard_tr2",false);
	$CORE_LOCAL->set("paycard_tr3",false);
	$CORE_LOCAL->set("paycard_type",0);
	$CORE_LOCAL->set("paycard_issuer",'Unknown');
	$CORE_LOCAL->set("paycard_response",array());
	$CORE_LOCAL->set("paycard_trans",'');
	$CORE_LOCAL->set("paycard_cvv2",'');
} // paycard_reset()

/**
  Clear card data variables from $CORE_LOCAL.

  <b>Storing card data in $CORE_LOCAL is
  not recommended</b>.
*/
static public function paycard_wipe_pan(){
	global $CORE_LOCAL;
	$CORE_LOCAL->set("paycard_tr1",false);
	$CORE_LOCAL->set("paycard_tr2",false);
	$CORE_LOCAL->set("paycard_tr3",false);
	$CORE_LOCAL->set("paycard_PAN",'');
	$CORE_LOCAL->set("paycard_exp",'');
}


/**
  Validate number using Luhn's Algorithm
  @param $pan the card number
  @return
   - 1 if the number is valid
   - 0 if the number is invalid
*/
static public function paycard_validNumber($pan) {
/* Luhn Algorithm <en.wikipedia.org/wiki/Luhn_algorithm>
1. Starting with the rightmost digit (which is the check digit) and moving left, double the value of every second digit.  For any
  digits that thus become 10 or more, add their digits together as if casting out nines. For example, 1111 becomes 2121, while
  8763 becomes 7733 (from 2*6=12 -> 1+2=3 and 2*8=16 -> 1+6=7).
2. Add all these digits together. For example, if 1111 becomes 2121, then 2+1+2+1 is 6; and 8763 becomes 7733, so 7+7+3+3 is 20.
3. If the total ends in 0 (put another way, if the total modulus 10 is congruent to 0), then the number is valid according to the
  Luhn formula; else it is not valid. So, 1111 is not valid (as shown above, it comes out to 6), while 8763 is valid (as shown above,
  it comes out to 20).
*/
	// prepare the doubling-summing conversion array
	$doublesum = array(0=>0,1=>2,2=>4,3=>6,4=>8,5=>1,6=>3,7=>5,8=>7,9=>9);
	// turn the number into a string, reverse it, and split it into an array of characters (which are digits)
	/* php5 */ //$digits = str_split(strrev((string)$pan));
	/* php4 */ $digits = preg_split('//', strrev((string)$pan), -1, PREG_SPLIT_NO_EMPTY);
	// run the sum
	$sum = 0;
	foreach( $digits as $index => $digit) {
		// $index starts at 0 but counts from the right, so we double any digit with an odd index
		if( ($index % 2) == 1)  $sum += $doublesum[(int)$digit];
		else                    $sum += (int)$digit;
	}
	// it has to end in 0 (meaning modulo:10 == 0)
	if( ($sum % 10) != 0)
		return 0;
	// ok
	return 1;
} // paycard_validNumber()


// determine if the expiration date (passed as a string, MMYY) is a valid date and is not in the past
// return 1 if ok, error code < 0 if not
/**
  Validate expiration date
  @param $exp expiration formatted MMYY
  @return
   - 1 if ok
   - -1 if the argument is malformed
   - -2 if the month is smarch-y
   - -3 if the date is in the past
*/
static public function paycard_validExpiration($exp) {
	// verify expiration format (MMYY)
	if( strlen($exp) != 4 || !ctype_digit($exp))
		return -1;
	// extract expiration parts (month, then year)
	$eM = (int)substr($exp,0,2);
	$eY = (int)substr($exp,2,2);
	// check month range
	if( $eM < 1 || $eM > 12)
		return -2;
	// get today's date
	$cM = (int)date('n'); // Numeric representation of a month, without leading zeros (1 through 12)
	$cY = (int)date('y'); // A two digit representation of a year (99 or 03)
	// check date
	if( $eY < $cY)
		return -3;
	if( $eY == $cY && $eM < $cM)
		return -3;
	// ok
	return 1;
} // paycard_validExpiration()


/**
  Extract information from a magnetic stripe
  @param $data the stripe data
  @return An array with keys:
   - 'pan' the card number
   - 'exp' the expiration as MMYY
   - 'name' the cardholder name
   - 'tr1' data from track 1
   - 'tr2' data from track 1
   - 'tr3' data from track 1

  Not all values will be found in every track.
  Keys with no data will be set to False.
  
  If the data is really malformed, the return
  will be an error code instead of an array.
*/
static public function paycard_magstripe($data) {
	global $CORE_LOCAL;

	// initialize
	$tr1 = false;
	$weirdTr1 = false;
	$tr2 = false;
	$tr3 = false;
	$pan = false;
	$exp = false;
	$name = false;
	
	// track types are identified by start-sentinel values, but all track types end in '?'
	$tracks = explode('?', $data);
	foreach( $tracks as $track) {
		if( substr($track,0,1) == '%') {  // track1 start-sentinel
			if( substr($track,1,1) != 'B') {  // payment cards must have format code 'B'
				$weirdTr1 = substr($track,1);
				//return -1; // unknown track1 format code
			} else if( $tr1 === false) {
				$tr1 = substr($track,1);
			} else {
				return -2; // there should only be one track with the track1 start-sentinel
			}
		} else if( substr($track,0,1) == ';') {  // track2/3 start sentinel
			if( $tr2 === false) {
				$tr2 = substr($track,1);
			} else if( $tr3 === false) {
				$tr3 = substr($track,1);
			} else {
				return -3; // there should only be one or two tracks with the track2/3 start-sentinel
			}
		}
		else if (substr($track,0,1) == "T"){
			// tender amount. not really a standard
			// sentinel, but need the value sent
			// from cc-terminal if in case it differs
			$amt = str_pad(substr($track,1),3,'0',STR_PAD_LEFT);
			$amt = substr($amt,0,strlen($amt)-2).".".substr($amt,-2);	
			$CORE_LOCAL->set("paycard_amount",$amt);
		}
		// ignore tracks with unrecognized start sentinels
		// readers often put E? or something similar if they have trouble reading,
		// even when they also provide entire usable tracks
	} // foreach magstripe track
	
	// if we have track1, parse it
	if( $tr1) {
		$tr1a = explode('^', $tr1);
		if( count($tr1a) != 3)
			return -5; // can't parse track1
		$pan = substr($tr1a[0],1);
		$exp = substr($tr1a[2],2,2) . substr($tr1a[2],0,2); // month and year are reversed on the track data
		$tr1name = explode('/', $tr1a[1]);
		if( count($tr1name) == 1) {
			$name = trim($tr1a[1]);
		} else {
			$name = "";
			for( $x=1; isset($tr1name[$x]); $x++)
				$name .= trim($tr1name[$x]) . " ";
			$name = trim($name . trim($tr1name[0]));
		}
	}
	
	// if we have track2, parse it
	if( $tr2) {
		$tr2a = explode('=', $tr2);
		if( count($tr2a) != 2)
			return -6; // can't parse track2
		// if we don't have track1, just use track2's data
		if( !$tr1) {
			$pan = $tr2a[0];
			$exp = substr($tr2a[1],2,2) . substr($tr2a[1],0,2); // month and year are reversed on the track data
			$name = "Customer";
		} else {
			// if we have both, make sure they match
			if( $tr2a[0] != $pan)
				return -7; // PAN mismatch
			else if( (substr($tr2a[1],2,2).substr($tr2a[1],0,2)) != $exp)
				return -8; // exp mismatch
		}
	}

	if ($tr3){
		// format not well documented, very
		// basic check for validity
		if (strstr($tr3,"=")) $tr3 = false;
	}
	
	// if we never got what we need (no track1 or track2), fail
	if( !$pan || !$exp)
		return -4;
	
	// ok
	$output = array();
	$output['pan'] = $pan;
	$output['exp'] = $exp;
	$output['name'] = $name;
	$output['tr1'] = $tr1;
	$output['tr2'] = $tr2;
	$output['tr3'] = $tr3;
	return $output;
} // paycard_magstripe()



// return a card number with digits replaced by *s, except for some number of leading or tailing digits as requested
static public function paycard_maskPAN($pan,$first,$last) {
	$mask = "";
	// sanity check
	$len = strlen($pan);
	if( $first + $last >= $len)
		return $pan;
	// prepend requested digits
	if( $first > 0)
		$mask .= substr($pan, 0, $first);
	// mask middle
	$mask .= str_repeat("*", $len - ($first+$last));
	// append requested digits
	if( $last > 0)
		$mask .= substr($pan, -$last);
	
	return $mask;
} // paycard_maskPAN()


// helper static public function to format money amounts pre-php5
static public function paycard_moneyFormat($amt) {
	$sign = "";
	if( $amt < 0) {
		$sign = "-";
		$amt = -$amt;
	}
	return $sign."$".number_format($amt,2);
} // paycard_moneyFormat


// helper static public function to build error messages
static public function paycard_errorText($title, $code, $text, $retry, $standalone, $refuse, $carbon, $tellIT, $type) {
	global $CORE_LOCAL;

	// pick the icon
	if( $carbon)
		$msg = "<img src='graphics/blacksquare.gif'> ";
	else if( $refuse)
		$msg = "<img src='graphics/bluetri.gif'> ";
	else
		$msg = "<img src='graphics/redsquare.gif'> ";
	// write the text
	$msg .= "<b>".trim($title)."</b>";
	//if( $code)
		$msg .= "<br><font size=-2>(#R.".$code.")</font>";
	$msg .= "<font size=-1><br><br>";
	if( $text)
		$msg .= $text."<br>";
	// write the options
	$opt = "";
	if( $refuse)     { $opt .= ($opt ? ", or" : "") . " request <b>other payment</b>"; }
	if( $retry)      { $opt .= ($opt ? ", or" : "") . " <b>retry</b>";                 }
	if( $standalone) { $opt .= ($opt ? ", or" : "") . " process in <b>standalone</b>"; }
	if( $carbon) {
		if( $type == self::PAYCARD_TYPE_CREDIT) { $opt .= ($opt ? ", or" : "") . " take a <b>carbon</b>"; }
		else { $opt .= ($opt ? ", or" : "") . " process <b>manually</b>"; }
	}
	if( $opt)        { $opt = "Please " . $opt . "."; }
	if( $tellIT)     { $opt = trim($opt." <i>(Notify IT)</i>"); }
	if( $opt)
		$msg .= $opt."<br>";
	$msg .= "<br>";
	// retry option?
	if( $retry) {
		$msg .= "[enter] to retry<br>";
	} else {
		$CORE_LOCAL->set("strEntered","");
		$CORE_LOCAL->set("strRemembered","");
	}
	$msg .= "[clear] to cancel</font>";
	return $msg;
} // paycard_errorText()


// display a paycard-related error due to cashier mistake
static public function paycard_msgBox($type, $title, $msg, $action) {
	global $CORE_LOCAL;
	$header = "IT CORE - Payment Card";
	if( $CORE_LOCAL->get("paycard_type") == self::PAYCARD_TYPE_CREDIT)      $header = "Wedge - Credit Card";
	else if( $CORE_LOCAL->get("paycard_type") == self::PAYCARD_TYPE_GIFT)   $header = "Wedge - Gift Card";
	else if( $CORE_LOCAL->get("paycard_type") == self::PAYCARD_TYPE_STORE)  $header = "Wedge - Wedge Card";
	$boxmsg = "<span class=\"larger\">".trim($title)."</span><p />";
	$boxmsg .= trim($msg)."<p />".trim($action);
	return DisplayLib::boxMsg($boxmsg,$header,True);
} // paycard_msgBox()


// display a paycard-related error due to system, network or other non-cashier mistake
static public function paycard_errBox($type, $title, $msg, $action) {
	global $CORE_LOCAL;

	$header = "Wedge - Payment Card";
	if( $CORE_LOCAL->get("paycard_type") == self::PAYCARD_TYPE_CREDIT)      $header = "Wedge - Credit Card";
	else if( $CORE_LOCAL->get("paycard_type") == self::PAYCARD_TYPE_GIFT)   $header = "Wedge - Gift Card";
	else if( $CORE_LOCAL->get("paycard_type") == self::PAYCARD_TYPE_STORE)  $header = "Wedge - Wedge Card";
	return DisplayLib::xboxMsg("<b>".trim($title)."</b><p><font size=-1>".trim($msg)."<p>".trim($action)."</font>", $header);
} // paycard_errBox()

static public function paycard_db(){
	global $CORE_LOCAL;
	switch($CORE_LOCAL->get("DBMS")){
	case 'mysql':
		$con = mysql_connect("127.0.0.1",$CORE_LOCAL->get("localUser"),$CORE_LOCAL->get("localPass"));
		if ($con===False) return False;
		$test = mysql_select_db($CORE_LOCAL->get("tDatabase"),$con);
		if ($test===False) return False;
		return $con;
		break;
	case 'mssql':
		$con = mssql_connect("127.0.0.1",$CORE_LOCAL->get("localUser"),$CORE_LOCAL->get("localPass"));
		if ($con===False) return False;
		$test = mssql_select_db($CORE_LOCAL->get("tDatabase"),$con);
		if ($test===False) return False;
		return $con;
		break;
	}
	return False;
}

static public function paycard_db_query($query_text,$link){
	global $CORE_LOCAL;
	switch($CORE_LOCAL->get("DBMS")){
	case 'mysql':
		return mysql_query($query_text,$link);
		break;
	case 'mssql':
		return mssql_query($query_text,$link);
		break;
	}
	return False;
}

static public function paycard_db_num_rows($result){
	global $CORE_LOCAL;
	switch($CORE_LOCAL->get("DBMS")){
	case 'mysql':
		return mysql_num_rows($result);
		break;
	case 'mssql':
		return mssql_num_rows($result);
		break;
	}
	return False;
}

static public function paycard_db_fetch_row($result){
	global $CORE_LOCAL;
	switch($CORE_LOCAL->get("DBMS")){
	case 'mysql':
		return mysql_fetch_array($result);
		break;
	case 'mssql':
		return mssql_fetch_row($result);
		break;
	}
	return False;
}

static public function paycard_db_escape($str, $link){
	global $CORE_LOCAL;
	switch($CORE_LOCAL->get("DBMS")){
	case 'mysql':
		return mysql_real_escape_string($str, $link);
		break;
	case 'mssql':
		return str_replace("'","''",$str);
		break;
	}
	return $str;
}

/*
summary of ISO standards for credit card magnetic stripe data tracks:
http://www.cyberd.co.uk/support/technotes/isocards.htm
(hex codes and character representations do not match ASCII - they are defined in the ISO spec)

TRACK 1
	{S} start sentinel: 0x05 '%'
	{C} format code: for credit cards, 0x22 'B'
	{F} field seperator: 0x3F '^'
	{E} end sentinel: 0x1F '?'
	{V} checksum character
	format: {S}{C}cardnumber{F}cardholdername{F}extra{E}{V}
		'extra' begins with expiration date as YYMM, then service code CCC, then unregulated extra data
	length: 79 characters total

TRACK 2
	{S} start sentinel: 0x0B ';'
	{F} field seperator: 0x0D '='
	{E} end sentinel: 0x0F '?'
	{V} checksum character
	format: {S}cardnumber{F}extra{E}{V}
		'extra' begins with expiration date as YYMM, then service code CCC, then unregulated extra data
	length: 40 characters total

TRACK 3
	{S} start sentinel: 0x0B ';'
	{C} format code: varies
	{F} field seperator: 0x0D '='
	{E} end sentinel: 0x0F '?'
	{V} checksum character
	format: {S}{C}{C}data{F}data{E}{V}
	length: 107 characters
*/

}

?>
