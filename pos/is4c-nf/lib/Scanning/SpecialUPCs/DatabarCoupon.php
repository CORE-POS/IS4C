<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

namespace COREPOS\pos\lib\Scanning\SpecialUPCs;
use COREPOS\pos\lib\Scanning\SpecialUPC;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;

class DatabarCoupon extends SpecialUPC 
{

    public function isSpecial($upc)
    {
        if (substr($upc,0,4) == "8110" && strlen($upc) > 13) {
            return true;
        }

        return false;
    }

    public function handle($upc,$json)
    {
        $pos = 0;
        $firstReq = array();

        /* STEP 1 - REQUIRED FIELDS */

        // remove prefix 8110
        $pos += 4;

        // grab company prefix length, remove it from barcode
        $prefixLength = ((int)$upc[$pos]) + 6;
        $pos += 1;

        // grab company prefix, remove from barcode
        $firstReq['man_id'] = substr($upc,$pos,$prefixLength);
        $pos += $prefixLength;

        // this way all prefixes map against
        // localtemptrans.upc[2,length]
        if ($prefixLength==6) {
            $firstReq['man_id'] = "0".$firstReq['man_id'];
        }

        // grab offer code, remove from barcode
        $offer = substr($upc,$pos,6);
        $pos += 6;

        // read value length
        $valLength = (int)$upc[$pos];
        $pos += 1;

        // read value
        $redeemValue = (int)substr($upc,$pos,$valLength);
        $pos += $valLength;

        // read primary requirement length
        $reqLength = (int)$upc[$pos];
        $pos += 1;

        // read primary requirement value
        $firstReq['value'] = substr($upc,$pos,$reqLength);
        $pos += $reqLength;

        // read primary requirement type-code
        $firstReq['code'] = $upc[$pos];
        $pos += 1;

        // read primary requirement family code
        $firstReq['family'] = substr($upc,$pos,3);
        $pos += 3;

        /* END REQUIRED FIELDS */

        /* example:
        
           barcode: 8110100707340143853100110110
            company prefix length    => 1 (+6)
            company prefix        => 0070734
            offer code        => 014385
            value length        => 3
            value            => 100
            primary req length    => 1
            primary req value    => 1
            primary req code    => 0
            primary req family    => 110                
        */

        /* STEP 2 - CHECK FOR OPTIONAL FIELDS */

        // second required item
        $secondReq = array();
        $reqRulesCode = 1;
        $dupePrefixFlag = false;
        if (isset($upc[$pos]) && $upc[$pos] == "1") {
            $pos += 1;

            $pos += 1;
        
            $srLength = (int)$upc[$pos];        
            $pos += 1;
            $secondReq['value'] = substr($upc,$pos,$srLength);
            $pos += $srLength;

            $secondReq['code'] = $upc[$pos];
            $pos += 1;

            $secondReq['family'] = substr($upc,$pos,3);
            $pos += 3;

            $smLength = ((int)$upc[$pos]) + 6;
            $pos += 1;
            if ($smLength == 15) { // 9+6
                $secondReq['man_id'] = $firstReq['man_id'];
                $dupePrefixFlag = true;
            } else {
                $secondReq['man_id'] = substr($upc,$pos,$smLength);
                $pos += $smLength;

                if ($smLength == 6) {
                    $secondReq['man_id'] = "0".$secondReq['man_id'];
                }
            }
        }

        // third required item
        $thirdReq = array();
        if (isset($upc[$pos]) && $upc[$pos] == "2") {
            $pos += 1;

            $trLength = (int)$upc[$pos];        
            $pos += 1;
            $thirdReq['value'] = substr($upc,$pos,$trLength);
            $pos += $trLength;

            $thirdReq['code'] = $upc[$pos];
            $pos += 1;

            $thirdReq['family'] = substr($upc,$pos,3);
            $pos += 3;

            $tmLength = ((int)$upc[$pos]) + 6;
            $pos += 1;
            if ($tmLength == 15) { // 9+6
                $thirdReq['man_id'] = $firstReq['man_id'];
                $dupePrefixFlag = true;
            } else {
                $thirdReq['man_id'] = substr($upc,$pos,$tmLength);
                $pos += $tmLength;

                if ($tmLength == 6) {
                    $thirdReq['man_id'] = "0".$thirdReq['man_id'];
                }
            }
        }

        if ($dupePrefixFlag) {
            $firstReq['man_id'] .= $firstReq['family'];
            $secondReq['man_id'] .= $secondReq['family'];
            $thirdReq['man_id'] .= $thirdReq['family'];
        }

        // expiration date
        if (isset($upc[$pos]) && $upc[$pos] == "3") {
            $pos += 1;
            $expires = substr($upc,$pos,6);
            $pos += 6;

            $year = "20".substr($expires,0,2);
            $month = substr($expires,2,2);
            $day = substr($expires,4,2);

            $tstamp = mktime(23,59,59,$month,$day,$year);
            if ($tstamp < time()) {
                $json['output'] = DisplayLib::boxMsg(_("Coupon expired ") . date('m/d/Y', $tstamp));
                return $json;
            }
        }

        // start date
        if (isset($upc[$pos]) && $upc[$pos] == "4") {
            $pos += 1;
            $starts = substr($upc,$pos,6);
            $pos += 6;

            $year = "20".substr($starts,0,2);
            $month = substr($starts,2,2);
            $dday = substr($starts,4,2);

            $tstamp = mktime(0,0,0,$month,$day,$year);
            if ($tstamp > time()) {
                $json['output'] = DisplayLib::boxMsg(sprintf(_("Coupon not valid until %d/%d/%d"), $m, $d, $y));
                return $json;
            }
        }
        
        // serial number
        $serial = false;
        if (isset($upc[$pos]) && $upc[$pos] == "5") {
            $pos += 1;
            $serialLength = ((int)$upc[$pos]) + 6;
            $pos += 1;
            $serial = substr($upc,$pos,$serialLength);
            $pos += $serialLength;
        }

        // retailer
        $retailer = false;
        if (isset($upc[$pos]) && $upc[$pos] == "6") {
            $pos += 1;
            $rtLength = ((int)$upc[$pos]) + 6;
            $pos += 1;
            $retailer = substr($upc,$pos,$rtLength);
            $pos += $rtLength;
        }

        /* END OPTIONAL FIELDS */

        /* STEP 3 - The Miscellaneous Field
           This field is also optional, but filling in
           the default values here will make code
           that validates coupons and calculates values
           consistent 
        */

        $misc = array(
            'value_code' => 0,
            'value_applies' => 0,
            'store_coupon' => 0,
            'no_multiply' => 0
        );
        if (isset($upc[$pos]) && $upc[$pos] == "9") {
            $pos += 1;
            $misc['value_code'] = $upc[$pos];
            $pos += 1;
            $misc['value_applies'] = $upc[$pos];
            $pos += 1;
            $misc['store_coupon'] = $upc[$pos];
            $pos += 1;
            $misc['no_multiply'] = $upc[$pos];
            $pos += 1;
        }

        /* END Miscellaneous Field */

        /* STEP 4 - validate coupon requirements */

        $primary = $this->validateRequirement($firstReq, $json);
        if (!$primary && (count($secondReq) == 0 || $reqRulesCode == 1 || $reqRulesCode == 2)) {
            // if the primary requirement isn't valid and
            //    a) there are no more requirments, or
            //    b) the primary requirement is mandatory
            // return the json. Error message should have been
            // set up by validateRequirement()
            return $json;
        }

        $secondary = $this->validateRequirement($secondReq, $json);
        if (!$secondary && (count($thirdReq) == 0 || $reqRulesCode == 1)) {
            // if the secondary requirment isn't valid and
            //    a) there are no more requirments, or
            //    b) all requirements are mandatory
            // return the json. Error message should have been
            // set up by validateRequirement()
            return $json;
        }

        $tertiary = $this->validateRequirement($thirdReq, $json);

        // compare requirement results with rules
        // return error message if applicable
        switch ($reqRulesCode) {
            case '0': // any requirement can be used
                if (!$primary && !$secondary && !$tertiary) {
                    return $json;
                }
                break;
            case '1': // all required
                if (!$primary || !$secondary || !$tertiary) {
                    return $json;
                }
                break;
            case '2': // primary + second OR third
                if (!$primary) {
                    return $json;
                } elseif (!$secondary && !$tertiary) {
                    return $json;
                }
                break;
            case '3': // either second or third. seems odd, may
                  // be misreading documentation on this one
                if (!$secondary && !$tertiary) {
                    return $json;
                }
                break;
            default:
                $json['output'] = DisplayLib::boxMsg(_("Malformed coupon"));
                return $json;
        }

        /* End requirement validation */
    
        /* STEP 5 - determine coupon value */

        $valArr = $firstReq;
        if ($misc['value_applies'] == 1) {
            $valArr = $secondReq;
        } elseif ($misc['value_applies'] == 2) {
            $valArr = $thirdReq;
        }
            
        $value = 0;
        switch($misc['value_code']) {
            case '0': // value in cents
            case '6':
                $value = MiscLib::truncate2($redeemValue / 100.00);
                break;
            case '1': // free item
                $value = $valArr['price'];
                break;
            case '2': // multiple free items
                $value = MiscLib::truncate2($valArr['price'] * $valArr['value']);
                break;
            case '5': // percent off
                $value = MiscLib::truncate2($valArr['price'] * ($valArr['value']/100.00));
                break;
            default:
                $json['output'] = DisplayLib::boxMsg(_("Error: bad coupon " . $misc['value_code']));
                return $json;
        }

        /* attempt to cram company prefix and offer code
           into 13 characters

           First character is zero
           Next characters are company prefix
           Remaining characters are offer code in base-36

           The first zero is there so that the company
           prefix will "line up" with matching items in
           localtemptrans

           The offer code is converted to base-36 to 
           reduce its character count.

           Offer code won't always fit. This is just best
           effort. I've already seen a real coupon using
           a 10 digit prefix. In theory there could even
           be a 12 digit prefix leaving no room for the
           offer code at all.
        */
        $upcStart = "0".$valArr['man_id'];
        $offer = base_convert($offer,10,36);
        $remaining = 13 - strlen($upcStart);
        if (strlen($offer) < $remaining) {
            $offer = str_pad($offer,$remaining,'0',STR_PAD_LEFT);
        } elseif (strlen($offer) > $remaining) {
            $offer = substr($offer,0,$remaining);
        }
        $couponUPC = $upcStart.$offer;

        TransRecord::addCoupon($couponUPC, $primary['department'], -1*$value);
        $json['output'] = DisplayLib::lastpage();
    
        return $json;
    }

    /* method takes an requirement array and a json array,
       both by reference

       item price is added to the requirements array as it
       can be needed to calculate coupon value later

       json array is updated to include error messages if
       the requirement isn't met, but these are not necessarily
       fatal errors when there are multiple requirements

       return true/false based on whether requirement is met
    */
    private function validateRequirement(&$req, &$json)
    {
        // non-existant requirement is treated as valid
        if (count($req) == 0) {
            return true;
        }
        $dbc = Database::tDataConnect();

        /* simple case first; just wants total transaction value 
           no company prefixing
        */
        if ($req['code'] == 2) {
            return $this->validateTransactionTotal($req, $json);
        }

        $query = sprintf("SELECT
            max(CASE WHEN trans_status<>'C' THEN unitPrice ELSE 0 END) as price,
            sum(CASE WHEN trans_status<>'C' THEN total ELSE 0 END) as total,
            max(department) as department,
            sum(CASE WHEN trans_status<>'C' THEN ItemQtty ELSE 0 END) as qty,
            sum(CASE WHEN trans_status='C' THEN 1 ELSE 0 END) as couponqtty
            FROM localtemptrans WHERE
            substring(upc,2,%d) = '%s'",
            strlen($req['man_id']),$req['man_id']);
        $result = $dbc->query($query);

        if ($dbc->numRows($result) <= 0) {
            $json['output'] = DisplayLib::boxMsg(_("Coupon requirements not met"));
            return false;
        }
        $row = $dbc->fetchRow($result);
        $req['price'] = $row['price'];
        $req['department'] = $row['department'];

        switch($req['code']) {
            case '0': // various qtty requirements
            case '3':
            case '4':
                return $this->validateQty($row['qty'], $row['couponqtty'], $req, $json);
            case '1':
                return $this->validateQty($row['total'], $row['couponqtty'], $req, $json);
            case '9':
                $json['output'] = DisplayLib::boxMsg(_("Tender coupon manually"));
                return false;
            default:
                $json['output'] = DisplayLib::boxMsg(_("Error: bad coupon"));
                return false; 
        }

        return true; // requirement validated
    }

    private function validateTransactionTotal(&$req, &$json)
    {
        $dbc = Database::tDataConnect();
        $chkQ = "SELECT SUM(total) FROM localtemptrans WHERE
            trans_type IN ('I','D','M')";
        $chkR = $dbc->query($chkQ);
        $ttlRequired = MiscLib::truncate2($req['value'] / 100.00);
        if ($dbc->num_rows($chkR) == 0) {
            $json['output'] = DisplayLib::boxMsg(_(sprintf("Coupon requires transaction of at least \$%.2f"), $ttlRequired));
            return false;
        }

        $chkW = $dbc->fetch_row($chkR);
        if ($chkW[0] < $ttlRequired) {
            $json['output'] = DisplayLib::boxMsg(_(sprintf("Coupon requires transaction of at least \$%.2f"), $ttlRequired));
            return false;
        }
        return true;
    }

    private function validateQty($qty, $couponqtty, &$req, &$json)
    {
        $available_qty = $qty - ($couponqtty * $req['value']);
        if ($available_qty < $req['value']) {
            // Coupon requirement not met
            if ($couponqtty > 0) {
                $json['output'] = DisplayLib::boxMsg(_("Coupon already applied"));
            } else {
                $json['output'] = DisplayLib::boxMsg(sprintf(_("Coupon requires %d items"), $req['value']));
            }
            return false;
        }

        return true;
    }

}

/*
$obj = new DatabarCoupon();
$obj->handle("8110100707340143853100110110",array());
$obj->handle("811010041570000752310011020096000",array());
$obj->handle("8110007487303085831001200003101130",array());
*/

