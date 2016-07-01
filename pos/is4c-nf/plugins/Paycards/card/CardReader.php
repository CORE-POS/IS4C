<?php

namespace COREPOS\pos\plugins\Paycards\card;

use \Exeception;
use \PaycardLib;

class CardReader
{
    private $binRanges = array(
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
    
    private $bin19s = array(
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

    private function identifyBin($binRange, $iin, $ebtAccept)
    {
        $accepted = true;
        $issuer = 'Unknown';
        foreach ($binRange as $range) {
            if ($iin >= $range['min'] && $iin <= $range['max']) {
                $issuer = $range['issuer'];
                $accepted = $range['accepted'];
                if ($accepted === 'ebt') {
                    $accepted = $ebtAccept;
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
    public function cardInfo($pan) 
    {
        $len = strlen($pan);
        $iin = (int)substr($pan,0,7);
        $issuer = "Unknown";
        $type = PaycardLib::PAYCARD_TYPE_UNKNOWN;
        $accepted = false;
        $ebtAccept = true;
        $test = false;
        if ($len >= 13 && $len <= 16) {
            $type = PaycardLib::PAYCARD_TYPE_CREDIT;
            list($accepted, $issuer) = $this->identifyBin($this->binRanges, $iin, $ebtAccept);
        } elseif ($len == 18) {
            if(      $iin>=6008900 && $iin<=6008909) { $issuer="EBT (CT)";   $accepted=$ebtAccept; }
            elseif( $iin>=6008750 && $iin<=6008759) { $issuer="EBT (MA)";   $accepted=$ebtAccept; }
        } elseif ($len == 19) {
            $type = PaycardLib::PAYCARD_TYPE_GIFT;
            list($accepted, $issuer) = $this->identifyBin($this->bin19s, $iin, $ebtAccept);
        } elseif (substr($pan,0,8) == "02E60080" || substr($pan, 0, 5) == "23.0%" || substr($pan, 0, 5) == "23.0;") {
            $type = PaycardLib::PAYCARD_TYPE_ENCRYPTED;
            $accepted = true;
        } elseif (substr($pan,0,2) === '02' && substr($pan,-2) === '03' && strstr($pan, '***')) {
            $type = PaycardLib::PAYCARD_TYPE_ENCRYPTED;
            $accepted = true;
        }
        return array('type'=>$type, 'issuer'=>$issuer, 'accepted'=>$accepted, 'test'=>$test);
    }

    // determine if we accept the card given the number; return 1 if yes, 0 if no
    /**
      Check whether a given card is accepted
      @param $pan the card number
      @return 
       - 1 if accepted
       - 0 if not accepted
    */
    public function accepted($pan)
    {
        $info = $this->cardInfo($pan);
        return ($info['accepted'] ? 1 : 0);
    }

    /**
      Determine card type
      @param $pan the card number
      @return a paycard type constant
    */
    public function type($pan) 
    {
        $info = $this->cardInfo($pan);
        return $info['type'];
    }

    /**
      Get paycard issuer
      @param $pan the card number
      @return string issuer

      Issuers include "Visa", "American Express", "MasterCard",
      and "Discover". Unrecognized cards will return "Unknown".
    */
    public function issuer($pan) 
    {
        $info = $this->cardInfo($pan);
        return $info['issuer'];
    } 

    private function getTracks($data)
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
                } elseif ($tr1 === false) {
                    $tr1 = substr($track,1);
                } else {
                    throw new Exception(-2); // there should only be one track with the track1 start-sentinel
                }
            } elseif (substr($track,0,1) == ';') {  // track2/3 start sentinel
                if ($tr2 === false) {
                    $tr2 = substr($track,1);
                } elseif ($tr3 === false) {
                    $tr3 = substr($track,1);
                } else {
                    throw new Exception(-3); // there should only be one or two tracks with the track2/3 start-sentinel
                }
            }
            // ignore tracks with unrecognized start sentinels
            // readers often put E? or something similar if they have trouble reading,
            // even when they also provide entire usable tracks
        } // foreach magstripe track

        return array($tr1, $tr2, $tr3);
    }

    private function parseTrack1($tr1)
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
        if (count($tr1name) == 1) {
            $name = trim($tr1a[1]);
        } elseif (count($tr1name) > 1) {
            $name = "";
            for( $x=1; isset($tr1name[$x]); $x++)
                $name .= trim($tr1name[$x]) . " ";
            $name = trim($name . trim($tr1name[0]));
        }

        return array($pan, $exp, $name);
    }

    private function parseTrack2($tr2, $tr1)
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
            list($pan, $exp, $name) = $this->parseTrack1($tr1);
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
    public function magstripe($data) 
    {
        // initialize
        try {
            list($tr1, $tr2, $tr3) = $this->getTracks($data);
            $pan = false;
            $exp = false;
            $name = false;
            
            // if we have track1, parse it
            if ($tr1) {
                list($pan, $exp, $name) = $this->parseTrack1($tr1);
            }
            
            // if we have track2, parse it
            if ($tr2) {
                list($pan, $exp, $name) = $this->parseTrack2($tr2, $tr1);
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
    }

    // return a card number with digits replaced by *s, except for some number of leading or tailing digits as requested
    public function maskPAN($pan,$first,$last) 
    {
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
    }
}

