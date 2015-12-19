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

/**
 @class PaycardLib
 @brief Defines constants and functions for card processing.
*/

class PaycardLib {

    const PAYCARD_MODE_BALANCE       =1;
    const PAYCARD_MODE_AUTH          =2;
    const PAYCARD_MODE_VOID          =3; // for voiding tenders/credits, rung in as T
    const PAYCARD_MODE_ACTIVATE      =4;
    const PAYCARD_MODE_ADDVALUE      =5;
    const PAYCARD_MODE_VOIDITEM      =6; // for voiding sales/addvalues, rung in as I
    const PAYCARD_MODE_CASHOUT       =7; // for cashing out a wedgecard

    const PAYCARD_TYPE_UNKNOWN       =0;
    const PAYCARD_TYPE_CREDIT        =1;
    const PAYCARD_TYPE_GIFT          =2;
    const PAYCARD_TYPE_STORE         =3;
    const PAYCARD_TYPE_ENCRYPTED       =4;

    const PAYCARD_ERR_OK             =1;
    const PAYCARD_ERR_NOSEND        =-1;
    const PAYCARD_ERR_COMM          =-2;
    const PAYCARD_ERR_TIMEOUT       =-3;
    const PAYCARD_ERR_DATA          =-4;
    const PAYCARD_ERR_PROC          =-5;
    const PAYCARD_ERR_CONTINUE        =-6;
    const PAYCARD_ERR_NSF_RETRY        =-7;
    const PAYCARD_ERR_TRY_VERIFY    =-8;

    static private $bin_ranges = array(
        array('min' => 3000000, 'max' => 3099999, 'issuer'=> "Diners Club", 'accepted'=>false),
        array('min'=>3400000, 'max'=>3499999, 'issuer'=>"American Express",'accepted'=>true),
        array('min'=>3528000, 'max'=>3589999, 'issuer'=>"JCB",       'accepted'=>true), // Japan Credit Bureau, accepted via Discover
        array('min'=>3600000, 'max'=>3699999, 'issuer'=>"MasterCard",'accepted'=>true), // Diners Club issued as MC in the US
        array('min'=>3700000, 'max'=>3799999, 'issuer'=>"American Express",'accepted'=>true),
        array('min'=>3800000, 'max'=>3899999, 'issuer'=>"Diners Club", 'accepted'=>false), // might be obsolete?
        array('min'=>4000000, 'max'=>4999999, 'issuer'=>"Visa",      'accepted'=>true),
        array('min'=>5100000, 'max'=>5599999, 'issuer'=>"MasterCard",'accepted'=>true),
        array('min'=>6011000, 'max'=>6011999, 'issuer'=>"Discover",  'accepted'=>true),
        array('min'=>6221260, 'max'=>6229259, 'issuer'=>"UnionPay",  'accepted'=>true), // China UnionPay, accepted via Discover
        array('min'=>6500000, 'max'=>6599999, 'issuer'=>"Discover",  'accepted'=>true),
        array('min'=>6500000, 'max'=>6599999, 'issuer'=>"Discover",  'accepted'=>true),
        array('min'=>5076800, 'max'=>5076809, 'issuer'=>"EBT (AL)",  'accepted'=>'ebt'),
        array('min'=>5076840, 'max'=>5076849, 'issuer'=>"EBT (AL*)",  'accepted'=>'ebt'),
        array('min'=>5076950, 'max'=>5076959, 'issuer'=>"EBT (AK)",  'accepted'=>'ebt'),
        array('min'=>5077060, 'max'=>5077069, 'issuer'=>"EBT (AZ)",  'accepted'=>'ebt'),
        array('min'=>6100930, 'max'=>6100939, 'issuer'=>"EBT (AR)",  'accepted'=>'ebt'),
        array('min'=>5076850, 'max'=>5076859, 'issuer'=>"EBT (AR*)",  'accepted'=>'ebt'),
        array('min'=>5077190, 'max'=>5077199, 'issuer'=>"EBT (CA)",  'accepted'=>'ebt'),
        array('min'=>5076810, 'max'=>5076819, 'issuer'=>"EBT (CO)",  'accepted'=>'ebt'),
        array('min'=>5077130, 'max'=>5077139, 'issuer'=>"EBT (DE)",  'accepted'=>'ebt'),
        array('min'=>5077070, 'max'=>5077079, 'issuer'=>"EBT (DC)",  'accepted'=>'ebt'),
        array('min'=>5081390, 'max'=>5081399, 'issuer'=>"EBT (FL)",  'accepted'=>'ebt'),
        array('min'=>5076860, 'max'=>5076869, 'issuer'=>"EBT (FL*)",  'accepted'=>'ebt'),
        array('min'=>5081480, 'max'=>5081489, 'issuer'=>"EBT (GA)",  'accepted'=>'ebt'),
        array('min'=>5076870, 'max'=>5076879, 'issuer'=>"EBT (GA*)",  'accepted'=>'ebt'),
        array('min'=>5780360, 'max'=>5780369, 'issuer'=>"EBT (GUAM)",  'accepted'=>'ebt'),
        array('min'=>5076980, 'max'=>5076989, 'issuer'=>"EBT (HI)",  'accepted'=>'ebt'),
        array('min'=>5076920, 'max'=>5076929, 'issuer'=>"EBT (ID)",  'accepted'=>'ebt'),
        array('min'=>5077040, 'max'=>5077049, 'issuer'=>"EBT (IN)",  'accepted'=>'ebt'),
        array('min'=>6014130, 'max'=>6014139, 'issuer'=>"EBT (KS)",  'accepted'=>'ebt'),
        array('min'=>5077090, 'max'=>5077099, 'issuer'=>"EBT (KY)",  'accepted'=>'ebt'),
        array('min'=>5076880, 'max'=>5076889, 'issuer'=>"EBT (KY*)",  'accepted'=>'ebt'),
        array('min'=>5044760, 'max'=>5044769, 'issuer'=>"EBT (LA)",  'accepted'=>'ebt'),
        array('min'=>6005280, 'max'=>6005289, 'issuer'=>"EBT (MD)",  'accepted'=>'ebt'),
        array('min'=>5077110, 'max'=>5077119, 'issuer'=>"EBT (MI)",  'accepted'=>'ebt'),
        array('min'=>6104230, 'max'=>6104239, 'issuer'=>"EBT (MN)",  'accepted'=>'ebt'),
        array('min'=>5077180, 'max'=>5077189, 'issuer'=>"EBT (MS)",  'accepted'=>'ebt'),
        array('min'=>5076830, 'max'=>5076839, 'issuer'=>"EBT (MO)",  'accepted'=>'ebt'),
        array('min'=>5076890, 'max'=>5076899, 'issuer'=>"EBT (MO*)",  'accepted'=>'ebt'),
        array('min'=>5077140, 'max'=>5077149, 'issuer'=>"EBT (MT)",  'accepted'=>'ebt'),
        array('min'=>5077160, 'max'=>5077169, 'issuer'=>"EBT (NE)",  'accepted'=>'ebt'),
        array('min'=>5077150, 'max'=>5077159, 'issuer'=>"EBT (NV)",  'accepted'=>'ebt'),
        array('min'=>5077010, 'max'=>5077019, 'issuer'=>"EBT (NH)",  'accepted'=>'ebt'),
        array('min'=>6104340, 'max'=>6104349, 'issuer'=>"EBT (NJ)",  'accepted'=>'ebt'),
        array('min'=>5866160, 'max'=>5866169, 'issuer'=>"EBT (NM)",  'accepted'=>'ebt'),
        array('min'=>5081610, 'max'=>5081619, 'issuer'=>"EBT (NC)",  'accepted'=>'ebt'),
        array('min'=>5076900, 'max'=>5076909, 'issuer'=>"EBT (NC*)",  'accepted'=>'ebt'),
        array('min'=>5081320, 'max'=>5081329, 'issuer'=>"EBT (ND)",  'accepted'=>'ebt'),
        array('min'=>5077000, 'max'=>5077009, 'issuer'=>"EBT (OH)",  'accepted'=>'ebt'),
        array('min'=>5081470, 'max'=>5081479, 'issuer'=>"EBT (OK)",  'accepted'=>'ebt'),
        array('min'=>5076930, 'max'=>5076939, 'issuer'=>"EBT (OR)",  'accepted'=>'ebt'),
        array('min'=>5076820, 'max'=>5076829, 'issuer'=>"EBT (RI)",  'accepted'=>'ebt'),
        array('min'=>5081320, 'max'=>5081329, 'issuer'=>"EBT (SD)",  'accepted'=>'ebt'),
        array('min'=>5077020, 'max'=>5077029, 'issuer'=>"EBT (TN)",  'accepted'=>'ebt'),
        array('min'=>5076910, 'max'=>5076919, 'issuer'=>"EBT (TN*)",  'accepted'=>'ebt'),
        array('min'=>5077210, 'max'=>5077219, 'issuer'=>"EBT (USVI)",  'accepted'=>'ebt'),
        array('min'=>6010360, 'max'=>6010369, 'issuer'=>"EBT (UT)",  'accepted'=>'ebt'),
        array('min'=>5077050, 'max'=>5077059, 'issuer'=>"EBT (VT)",  'accepted'=>'ebt'),
        array('min'=>6220440, 'max'=>6220449, 'issuer'=>"EBT (VA)",  'accepted'=>'ebt'),
        array('min'=>5077100, 'max'=>5077109, 'issuer'=>"EBT (WA)",  'accepted'=>'ebt'),
        array('min'=>5077200, 'max'=>5077209, 'issuer'=>"EBT (WV)",  'accepted'=>'ebt'),
        array('min'=>5077080, 'max'=>5077089, 'issuer'=>"EBT (WI)",  'accepted'=>'ebt'),
        array('min'=>5053490, 'max'=>5053499, 'issuer'=>"EBT (WY)",  'accepted'=>'ebt'),
    );
    
    static private $bin19s = array(
        array('min'=>7019208, 'max'=>7019208,  'issuer'=>"Co-op Gift", 'accepted'=>true), // NCGA gift cards
        array('min'=>7018525, 'max'=>7018525,  'issuer'=>"Valutec Gift", 'accepted'=>false), // valutec test cards (linked to test merchant/terminal ID)
        array('min'=>6050110, 'max'=>6050110,  'issuer'=>"Co-Plus Gift Card", 'accepted'=>true),
        array('min'=>6014530, 'max'=>6014539,  'issuer'=>"EBT (IL)",   'accepted'=>'ebt'),
        array('min'=>6274850, 'max'=>6274859,  'issuer'=>"EBT (IA)",   'accepted'=>'ebt'),
        array('min'=>5077030, 'max'=>5077039,  'issuer'=>"EBT (ME)",   'accepted'=>'ebt'),
        array('min'=>6004860, 'max'=>6004869,  'issuer'=>"EBT (NY)",   'accepted'=>'ebt'),
        array('min'=>6007600, 'max'=>6007609,  'issuer'=>"EBT (PA)",   'accepted'=>'ebt'),
        array('min'=>6104700, 'max'=>6104709,  'issuer'=>"EBT (SC)",   'accepted'=>'ebt'),
        array('min'=>6100980, 'max'=>6100989,  'issuer'=>"EBT (TX)",   'accepted'=>'ebt'),
    );

static private function identifyBin($bin_range, $iin, $ebt_accept)
{
    $accepted = true;
    $issuer = 'Unknown';
    foreach ($bin_range as $range) {
        if ($iin >= $range['min'] && $iin <= $range['max']) {
            $issuer = $range['issuer'];
            $accepted = $range['accepted'];
            if ($accepted === 'ebt') {
                $accepted = $ebt_accept;
            }
            break;
        }
    }

    return array($accepted, $issuer);
}

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

   EBT-Specific Notes:
   EBT BINs added 20Mar14 by Andy
   Based on NACHA document; that document claims to be current
   as of 30Sep10.

   Issuer is normally give as EBT (XX) where XX is the
   two character state postal abbreviation. GUAM is Guam
   and USVI is US Virgin Islands. A few states list both
   a state BIN number and a federal BIN number. In these
   cases there's an asterisk after the postal abbreviation.
   Maine listed both a state and federal BIN but they're 
   identical so I don't know how to distinguish. The PAN
   length is not listed for Wyoming. I guessed 16 since 
   that's most common.
*/
static public function paycard_info($pan) 
{
    $len = strlen($pan);
    $iin = (int)substr($pan,0,7);
    $issuer = "Unknown";
    $type = self::PAYCARD_TYPE_UNKNOWN;
    $accepted = false;
    $ebt_accept = true;
    $test = false;
    if ($len >= 13 && $len <= 16) {
        $type = self::PAYCARD_TYPE_CREDIT;
        list($accepted, $issuer) = self::identifyBin(self::$bin_ranges, $iin, $ebt_accept);
    } elseif ($len == 18) {
        if(      $iin>=6008900 && $iin<=6008909) { $issuer="EBT (CT)";   $accepted=$ebt_accept; }
        else if( $iin>=6008750 && $iin<=6008759) { $issuer="EBT (MA)";   $accepted=$ebt_accept; }
    } elseif ($len == 19) {
        $type = self::PAYCARD_TYPE_GIFT;
        list($accepted, $issuer) = self::identifyBin(self::$bin19s, $iin, $ebt_accept);
    } elseif (substr($pan,0,8) == "02E60080" || substr($pan, 0, 5) == "23.0%" || substr($pan, 0, 5) == "23.0;") {
        $type = self::PAYCARD_TYPE_ENCRYPTED;
        $accepted = true;
    }
    return array('type'=>$type, 'issuer'=>$issuer, 'accepted'=>$accepted, 'test'=>$test);
} // paycard_info()


// determine if we accept the card given the number; return 1 if yes, 0 if no
/**
  Check whether a given card is accepted
  @param $pan the card number
  @param $ebt [boolean, default true] 
  @return 
   - 1 if accepted
   - 0 if not accepted
*/
static public function paycard_accepted($pan, $ebt=true) {
    $info = self::paycard_info($pan);
    if (!$ebt && substr($info['issuer'], 0, 3) == 'EBT') {
        return 0;
    } else {
        return ($info['accepted'] ? 1 : 0);
    }
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
static public function paycard_live($type = self::PAYCARD_TYPE_UNKNOWN) 
{
    // these session vars require training mode no matter what card type
    if( CoreLocal::get("training") != 0 || CoreLocal::get("CashierNo") == 9999)
        return 0;
    // special session vars for each card type
    if( $type === self::PAYCARD_TYPE_CREDIT) {
        if( CoreLocal::get("CCintegrate") != 1)
            return 0;
    } else if( $type === self::PAYCARD_TYPE_GIFT) {
        if( CoreLocal::get("training") == 1)
            return 0;
    } else if( $type === self::PAYCARD_TYPE_STORE) {
        if( CoreLocal::get("storecardLive") != 1)
            return 0;
    }
    return 1;
} // paycard_live()


/**
  Clear paycard variables from session
*/
static public function paycard_reset() 
{

    // make sure this matches session.php!!!
    CoreLocal::set("paycard_manual",0);
    CoreLocal::set("paycard_amount",0.00);
    CoreLocal::set("paycard_mode",0);
    CoreLocal::set("paycard_id",0);
    CoreLocal::set("paycard_PAN",'');
    CoreLocal::set("paycard_exp",'');
    CoreLocal::set("paycard_name",'Customer');
    CoreLocal::set("paycard_tr1",false);
    CoreLocal::set("paycard_tr2",false);
    CoreLocal::set("paycard_tr3",false);
    CoreLocal::set("paycard_type",0);
    CoreLocal::set("paycard_issuer",'Unknown');
    CoreLocal::set("paycard_response",array());
    CoreLocal::set("paycard_trans",'');
    CoreLocal::set("paycard_cvv2",'');
    CoreLocal::set('PaycardRetryBalanceLimit', 0);
} // paycard_reset()

/**
  Clear card data variables from session

  <b>Storing card data in session is
  not recommended</b>.
*/
static public function paycard_wipe_pan()
{
    CoreLocal::set("paycard_tr1",false);
    CoreLocal::set("paycard_tr2",false);
    CoreLocal::set("paycard_tr3",false);
    CoreLocal::set("paycard_PAN",'');
    CoreLocal::set("paycard_exp",'');
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
    $expireM = (int)substr($exp,0,2);
    $expireY = (int)substr($exp,2,2);
    // check month range
    if( $expireM < 1 || $expireM > 12)
        return -2;
    // get today's date
    $cardM = (int)date('n'); // Numeric representation of a month, without leading zeros (1 through 12)
    $cardY = (int)date('y'); // A two digit representation of a year (99 or 03)
    // check date
    if( $expireY < $cardY)
        return -3;
    if( $expireY == $cardY && $expireM < $cardM)
        return -3;
    // ok
    return 1;
} // paycard_validExpiration()


static private function getTracks($data)
{
    $tr1 = false;
    $weirdTr1 = false;
    $tr2 = false;
    $tr3 = false;

    // track types are identified by start-sentinel values, but all track types end in '?'
    $tracks = explode('?', $data);
    foreach( $tracks as $track) {
        if (substr($track,0,1) == '%') {  // track1 start-sentinel
            if (substr($track,1,1) != 'B') {  // payment cards must have format code 'B'
                $weirdTr1 = substr($track,1);
                //return -1; // unknown track1 format code
            } else if( $tr1 === false) {
                $tr1 = substr($track,1);
            } else {
                throw new Exception(-2); // there should only be one track with the track1 start-sentinel
            }
        } else if( substr($track,0,1) == ';') {  // track2/3 start sentinel
            if( $tr2 === false) {
                $tr2 = substr($track,1);
            } else if( $tr3 === false) {
                $tr3 = substr($track,1);
            } else {
                throw new Exception(-3); // there should only be one or two tracks with the track2/3 start-sentinel
            }
        } else if (substr($track,0,1) == "T"){
            // tender amount. not really a standard
            // sentinel, but need the value sent
            // from cc-terminal if in case it differs
            $amt = str_pad(substr($track,1),3,'0',STR_PAD_LEFT);
            $amt = substr($amt,0,strlen($amt)-2).".".substr($amt,-2);    
            CoreLocal::set("paycard_amount",$amt);
        }
        // ignore tracks with unrecognized start sentinels
        // readers often put E? or something similar if they have trouble reading,
        // even when they also provide entire usable tracks
    } // foreach magstripe track

    return array($tr1, $tr2, $tr3);
}

static private function parseTrack1($tr1)
{
    $pan = false;
    $exp = false;
    $name = false;
    $tr1a = explode('^', $tr1);
    if( count($tr1a) != 3)
        throw new Exception(-5); // can't parse track1
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

    return array($pan, $exp, $name);
}

static private function parseTrack2($tr2, $tr1)
{
    $pan = false;
    $exp = false;
    $name = false;
    $tr2a = explode('=', $tr2);
    if( count($tr2a) != 2)
        throw new Exception(-6); // can't parse track2
    // if we don't have track1, just use track2's data
    if( !$tr1) {
        $pan = $tr2a[0];
        $exp = substr($tr2a[1],2,2) . substr($tr2a[1],0,2); // month and year are reversed on the track data
        $name = "Customer";
    } else {
        // if we have both, make sure they match
        if( $tr2a[0] != $pan)
            throw new Exception(-7); // PAN mismatch
        else if( (substr($tr2a[1],2,2).substr($tr2a[1],0,2)) != $exp)
            throw new Exception(-8); // exp mismatch
    }

    return array($pan, $exp, $name);
}

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
static public function paycard_magstripe($data) 
{
    // initialize
    try {
        list($tr1, $tr2, $tr3) = self::getTracks($data);
        $pan = false;
        $exp = false;
        $name = false;
        
        // if we have track1, parse it
        if ($tr1) {
            list($pan, $exp, $name) = self::parseTrack1($tr1);
        }
        
        // if we have track2, parse it
        if ($tr2) {
            list($pan, $exp, $name) = self::parseTrack2($tr2, $tr1);
        }
    } catch (Exception $ex) {
        return $ex->getMessage();
    }

    if ($tr3) {
        // format not well documented, very
        // basic check for validity
        if (strstr($tr3,"=")) $tr3 = false;
    }
    
    // if we never got what we need (no track1 or track2), fail
    if (!$pan || !$exp)
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
static public function paycard_errorText($title, $code, $text, $retry, $standalone, $refuse, $carbon, $tellIT, $type) 
{
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
        CoreLocal::set("strEntered","");
        CoreLocal::set("strRemembered","");
    }
    $msg .= "[clear] to cancel</font>";
    return $msg;
} // paycard_errorText()


// display a paycard-related error due to cashier mistake
static public function paycard_msgBox($type, $title, $msg, $action) 
{
    $header = "IT CORE - Payment Card";
    $boxmsg = "<span class=\"larger\">".trim($title)."</span><p />";
    $boxmsg .= trim($msg)."<p />".trim($action);
    return DisplayLib::boxMsg($boxmsg,$header,True);
} // paycard_msgBox()


// display a paycard-related error due to system, network or other non-cashier mistake
static public function paycard_errBox($type, $title, $msg, $action) 
{
    return DisplayLib::xboxMsg("<b>".trim($title)."</b><p><font size=-1>".trim($msg)."<p>".trim($action)."</font>");
} // paycard_errBox()

static private $paycardDB = null;

static public function paycard_db()
{
    if (self::$paycardDB === null) {
        self::$paycardDB = Database::tDataConnect();
    }

    return self::$paycardDB;
}

static public function paycard_db_query($query_text,$link){
    return self::$paycardDB->query($query_text);
}

static public function paycard_db_num_rows($result){
    return self::$paycardDB->num_rows($result);
}

static public function paycard_db_fetch_row($result){
    return self::$paycardDB->fetch_row($result);
}

static private function getIssuerOverride($issuer)
{
    if (CoreLocal::get('PaycardsTenderCodeVisa') && $issuer == 'Visa') {
        return array(CoreLocal::get('PaycardsTenderCodeVisa'));
    } elseif (CoreLocal::get('PaycardsTenderCodeMC') && $issuer == 'MasterCard') {
        return array(CoreLocal::get('PaycardsTenderCodeMC'));
    } elseif (CoreLocal::get('PaycardsTenderCodeDiscover') && $issuer == 'Discover') {
        return array(CoreLocal::get('PaycardsTenderCodeDiscover'));
    } elseif (CoreLocal::get('PaycardsTenderCodeAmex') && $issuer == 'American Express') {
        return array(CoreLocal::get('PaycardsTenderCodeAmex'));
    } else {
        return false;
    }
}

static private function getTenderConfig($type)
{ 
    switch ($type) {
        case 'DEBIT':
            return array(
                array(CoreLocal::get('PaycardsTenderCodeDebit')),
                'DC',
                'Debit Card',
            );
        case 'EBTCASH':
            return array(
                array(CoreLocal::get('PaycardsTenderCodeEbtCash')),
                'EC',
                'EBT Cash',
            );
        case 'EBTFOOD':
            return array(
                array(CoreLocal::get('PaycardsTenderCodeEbtFood')),
                'EF',
                'EBT Food',
            );
        case 'EMV':
            return array(
                array(CoreLocal::get('PaycardsTenderCodeEmv')),
                'CC',
                'Credit Card',
            );
        case 'CREDIT':
        default:
            return array(
                array(CoreLocal::get('PaycardsTenderCodeCredit')),
                'CC',
                'Credit Card',
            );
    }
}

/**
  Lookup user-configured tender
  Failover to defaults if tender does not exist
  Since we already have an authorization at this point,
  adding a default tender record to the transaction
  is better than issuing an error message
*/
static public function getTenderInfo($type, $issuer)
{
    $dbc = Database::pDataConnect();
    $lookup = $dbc->prepare('
        SELECT TenderName,
            TenderCode
        FROM tenders
        WHERE TenderCode = ?');
    
    list($args, $default_code, $default_description) = self::getTenderConfig($type);
    $override = self::getIssuerOverride($issuer);
    if ($override !== false) {
        $args = $override;
    }
    
    $found = $dbc->execute($lookup, $args);
    if ($found === false || $dbc->num_rows($found) == 0) {
        return array($default_code, $default_description);
    } else {
        $row = $dbc->fetch_row($found);
        return array($row['TenderCode'], $row['TenderName']);
    }
}

static public function setupAuthJson($json)
{
    if (CoreLocal::get("paycard_amount") == 0) {
        CoreLocal::set("paycard_amount",CoreLocal::get("amtdue"));
    }
    CoreLocal::set("paycard_id",CoreLocal::get("LastID")+1); // kind of a hack to anticipate it this way..
    $plugin_info = new Paycards();
    $json['main_frame'] = $plugin_info->pluginUrl().'/gui/paycardboxMsgAuth.php';
    $json['output'] = '';

    return $json;
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

