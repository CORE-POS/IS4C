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
use \stdClass;

class DatabarCoupon extends SpecialUPC 
{

    public function isSpecial($upc)
    {
        if (substr($upc,0,4) == "8110" && strlen($upc) > 13) {
            return true;
        }

        return false;
    }

    /**
      Coupon information is parsed into a large object reflecting all the various
      potential properties. These properties are:

      * firstReq - An object representing a set of purchase requirements
      * secondReq - An object representing a set of purchase requirements
      * thirdReq - An object representing a set of purchase requirements
      * offerCode - An identifier string [probably] unique to this coupon
      * redeemValue - A baseline value, in cents, for simple coupons
      * requiredRulesCode - integer value indicating which combination of the
        first, second, and third requirements must be met before using the coupon
      * dupePrefixFlag - boolean flag. When set to true, each requirement's family
        code is appended to the requirement's prefix. This causes each requirement
        to look for a specific subset of items from the manufacturer prefix.
      * serial - Another embedded identifier. Not used for anything
      * retailer - Another embedded identifier. Not used for anything
      * valueCode - integer value indicating how the coupon information should
        be translated into a dollar value when redeeming the coupon
      * valueApplies - integer value indicating whether the value given in
        the first, second, or third requirement object should be used when
        determining final redemption dollar value
      * storeCoupon - integer flag. Not used for anything
      * noMultiply - integer flag. Not used for anything

      The requirement object mentioned above has the following properties:

      * valid - boolean indicating the requirement is met. This is the ONLY property
        that's guaranteed to exist for all requirement objects. All other fields are
        only populated if present in the coupon.
      * prefix - string manufacturer barcode prefix
      * code - integer indicating how to calculate the requirement's dollar value
      * value - integer value used in calculating the requirement's dollar value
      * family - string family code associated with the requirement
      * price - retail price of an item in the transaction that meets this requirement
      * department - POS department of an item in the transaction that meets this requirement
    */

    public function handle($upc,$json)
    {
        $pos = 0;
        $coupon = new stdClass();
        $coupon->firstReq = new stdClass();

        /* STEP 1 - REQUIRED FIELDS */

        // remove prefix 8110
        $pos += 4;

        // grab company prefix length, remove it from barcode
        $prefixLength = ((int)$upc[$pos]) + 6;
        $pos += 1;

        // grab company prefix, remove from barcode
        $coupon->firstReq->prefix = substr($upc,$pos,$prefixLength);
        $pos += $prefixLength;

        // this way all prefixes map against
        // localtemptrans.upc[2,length]
        if ($prefixLength==6) {
            $coupon->firstReq->prefix = '0' . $coupon->firstReq->prefix;
        }

        // grab offer code, remove from barcode
        $offer = substr($upc,$pos,6);
        $coupon->offerCode = substr($upc,$pos,6);
        $pos += 6;

        // read value length
        $valLength = (int)$upc[$pos];
        $pos += 1;

        // read value
        $coupon->redeemValue = (int)substr($upc,$pos,$valLength);
        $pos += $valLength;

        // read primary requirement length
        $reqLength = (int)$upc[$pos];
        $pos += 1;

        // read primary requirement value
        $coupon->firstReq->value = substr($upc,$pos,$reqLength);
        $pos += $reqLength;

        // read primary requirement type-code
        $coupon->firstReq->code = $upc[$pos];
        $pos += 1;

        // read primary requirement family code
        $coupon->firstReq->family = substr($upc,$pos,3);
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
        $coupon->secondReq = new stdClass();
        $coupon->requiredRulesCode = 1;
        $coupon->dupePrefixFlag = false;
        if (isset($upc[$pos]) && $upc[$pos] == "1") {
            $pos += 1;

            $srLength = (int)$upc[$pos];        
            $pos += 1;
            $coupon->secondReq->value = substr($upc,$pos,$srLength);
            $pos += $srLength;

            $coupon->secondReq->code = $upc[$pos];
            $pos += 1;

            $coupon->secondReq->family = substr($upc,$pos,3);
            $pos += 3;

            $smLength = ((int)$upc[$pos]) + 6;
            $pos += 1;
            if ($smLength == 15) { // 9+6
                $coupon->secondReq->prefix = $coupon->firstReq->prefix;
                $coupon->dupePrefixFlag = true;
            } else {
                $coupon->secondReq->prefix = substr($upc,$pos,$smLength);
                $pos += $smLength;

                if ($smLength == 6) {
                    $coupon->secondReq->prefix = '0' . $coupon->secondReq->prefix;
                }
            }
        }

        // third required item
        $coupon->thirdReq = new stdClass();
        if (isset($upc[$pos]) && $upc[$pos] == "2") {
            $pos += 1;

            $trLength = (int)$upc[$pos];        
            $pos += 1;
            $coupon->thirdReq->value = substr($upc,$pos,$trLength);
            $pos += $trLength;

            $coupon->thirdReq->code = $upc[$pos];
            $pos += 1;

            $coupon->thirdReq->family = substr($upc,$pos,3);
            $pos += 3;

            $tmLength = ((int)$upc[$pos]) + 6;
            $pos += 1;
            if ($tmLength == 15) { // 9+6
                $coupon->thirdReq->prefix = $coupon->firstReq->prefix;
                $coupon->dupePrefixFlag = true;
            } else {
                $coupon->thirdReq->prefix = substr($upc,$pos,$tmLength);
                $pos += $tmLength;

                if ($tmLength == 6) {
                    $coupon->thirdReq->prefix = '0' . $coupon->thirdReq->prefix;
                }
            }
        }

        if ($coupon->dupePrefixFlag) {
            $coupon->firstReq->prefix .= $coupon->firstReq->family;
            $coupon->secondReq->prefix .= $coupon->secondReq->family;
            $coupon->thirdReq->prefix .= $coupon->thirdReq->family;
        }

        // expiration date
        if (isset($upc[$pos]) && $upc[$pos] == "3") {
            $pos += 1;
            $expires = substr($upc,$pos,6);
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
        $coupon->serial = false;
        if (isset($upc[$pos]) && $upc[$pos] == "5") {
            $pos += 1;
            $serialLength = ((int)$upc[$pos]) + 6;
            $pos += 1;
            $coupon->serial = substr($upc,$pos,$serialLength);
            $pos += $serialLength;
        }

        // retailer
        $coupon->retailer = false;
        if (isset($upc[$pos]) && $upc[$pos] == "6") {
            $pos += 1;
            $rtLength = ((int)$upc[$pos]) + 6;
            $pos += 1;
            $coupon->retailer = substr($upc,$pos,$rtLength);
            $pos += $rtLength;
        }

        /* END OPTIONAL FIELDS */

        /* STEP 3 - The Miscellaneous Field
           This field is also optional, but filling in
           the default values here will make code
           that validates coupons and calculates values
           consistent 
        */

        $coupon->valueCode = 0;
        $coupon->valueApplies = 0;
        $coupon->storeCoupon = 0;
        $coupon->noMultiply = 0;
        if (isset($upc[$pos]) && $upc[$pos] == "9") {
            $pos += 1;
            $coupon->valueCode = $upc[$pos];
            $pos += 1;
            $coupon->valueApplies = $upc[$pos];
            $pos += 1;
            $coupon->storeCoupon = $upc[$pos];
            $pos += 1;
            $coupon->noMultiply = $upc[$pos];
            $pos += 1;
        }

        /* END Miscellaneous Field */

        /* STEP 4 - validate coupon requirements */

        list($coupon->firstReq, $json) = $this->validateRequirement($coupon->firstReq, $json);
        if (!$coupon->firstReq->valid && (!property_exists($coupon->secondReq, 'value') || $coupon->requiredRulesCode == 1 || $coupon->requiredRulesCode == 2)) {
            // if the primary requirement isn't valid and
            //    a) there are no more requirments, or
            //    b) the primary requirement is mandatory
            // return the json. Error message should have been
            // set up by validateRequirement()
            return $json;
        }

        list($coupon->secondReq, $json) = $this->validateRequirement($coupon->secondReq, $json);
        if (!$coupon->secondReq->valid && (!property_exists($coupon->thirdReq, 'value') || $coupon->requiredRulesCode == 1)) {
            // if the secondary requirment isn't valid and
            //    a) there are no more requirments, or
            //    b) all requirements are mandatory
            // return the json. Error message should have been
            // set up by validateRequirement()
            return $json;
        }

        list($coupon->thirdReq, $json) = $this->validateRequirement($coupon->thirdReq, $json);

        // compare requirement results with rules
        // return error message if applicable
        switch ($coupon->requiredRulesCode) {
            case '0': // any requirement can be used
                if (!$coupon->firstReq->valid && !$coupon->secondReq->valid && !$coupon->thirdReq->valid) {
                    return $json;
                }
                break;
            case '1': // all required
                if (!$coupon->firstReq->valid || !$coupon->secondReq->valid || !$coupon->thirdReq->valid) {
                    return $json;
                }
                break;
            case '2': // primary + second OR third
                if (!$coupon->firstReq->valid) {
                    return $json;
                } elseif (!$coupon->secondReq->valid && !$coupon->thirdReq->valid) {
                    return $json;
                }
                break;
            case '3': // either second or third. seems odd, may
                  // be misreading documentation on this one
                if (!$coupon->secondReq->valid && !$coupon->thirdReq->valid) {
                    return $json;
                }
                break;
            default:
                $json['output'] = DisplayLib::boxMsg(_("Malformed coupon"));
                return $json;
        }

        /* End requirement validation */
    
        /* STEP 5 - determine coupon value */

        $valReq = $coupon->firstReq;
        if ($coupon->valueApplies == 1) {
            $valReq = $coupon->secondReq;
        } elseif ($coupon->valueApplies == 2) {
            $valReq = $coupon->thirdReq;
        }
            
        $value = 0;
        switch($coupon->valueCode) {
            case '0': // value in cents
            case '6':
                $value = MiscLib::truncate2($coupon->redeemValue / 100.00);
                break;
            case '1': // free item
                $value = $valReq->price;
                break;
            case '2': // multiple free items
                $value = MiscLib::truncate2($valReq->price * $valReq->value);
                break;
            case '5': // percent off
                $value = MiscLib::truncate2($valReq->price * ($valReq->value/100.00));
                break;
            default:
                $json['output'] = DisplayLib::boxMsg(_("Error: bad coupon " . $coupon->valueCode));
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
        $upcStart = "0" . $valReq->prefix;
        $offer = base_convert($offer,10,36);
        $remaining = 13 - strlen($upcStart);
        if (strlen($offer) < $remaining) {
            $offer = str_pad($offer,$remaining,'0',STR_PAD_LEFT);
        } elseif (strlen($offer) > $remaining) {
            $offer = substr($offer,0,$remaining);
        }
        $couponUPC = $upcStart.$offer;

        TransRecord::addCoupon($couponUPC, $coupon->firstReq->department, -1*$value);
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
    private function validateRequirement($req, $json)
    {
        // non-existant requirement is treated as valid
        if (!property_exists($req, 'value')) {
            $req->valid = true;
            return array($req, $json);
        }
        $dbc = Database::tDataConnect();

        /* simple case first; just wants total transaction value 
           no company prefixing
        */
        if ($req->code == 2) {
            return $this->validateTransactionTotal($req, $json);
        }

        $req->valid = false;

        $query = sprintf("SELECT
            max(CASE WHEN trans_status<>'C' THEN unitPrice ELSE 0 END) as price,
            sum(CASE WHEN trans_status<>'C' THEN total ELSE 0 END) as total,
            max(department) as department,
            sum(CASE WHEN trans_status<>'C' THEN ItemQtty ELSE 0 END) as qty,
            sum(CASE WHEN trans_status='C' THEN 1 ELSE 0 END) as couponqtty
            FROM localtemptrans WHERE
            substring(upc,2,%d) = '%s'",
            strlen($req->prefix),$req->prefix);
        $result = $dbc->query($query);

        if ($dbc->numRows($result) <= 0) {
            $json['output'] = DisplayLib::boxMsg(_("Coupon requirements not met"));
            return array($req, $json);
        }
        $row = $dbc->fetchRow($result);
        $req->price = $row['price'];
        $req->department = $row['department'];

        switch($req->code) {
            case '0': // various qtty requirements
            case '3':
            case '4':
                return $this->validateQty($row['qty'], $row['couponqtty'], $req, $json);
            case '1':
                return $this->validateQty($row['total'], $row['couponqtty'], $req, $json);
            case '9':
                $json['output'] = DisplayLib::boxMsg(_("Tender coupon manually"));
                return array($req, $json);
            default:
                $json['output'] = DisplayLib::boxMsg(_("Error: bad coupon"));
                return array($req, $json);
        }

        $req->valid = true;

        return array($req, $json); // requirement validated
    }

    private function validateTransactionTotal($req, $json)
    {
        $dbc = Database::tDataConnect();
        $req->valid = false;
        $chkQ = "SELECT SUM(total) FROM localtemptrans WHERE
            trans_type IN ('I','D','M')";
        $chkR = $dbc->query($chkQ);
        $ttlRequired = MiscLib::truncate2($req->value / 100.00);
        if ($dbc->num_rows($chkR) == 0) {
            $json['output'] = DisplayLib::boxMsg(_(sprintf("Coupon requires transaction of at least \$%.2f"), $ttlRequired));
            return array($req, $json);
        }

        $chkW = $dbc->fetch_row($chkR);
        if ($chkW[0] < $ttlRequired) {
            $json['output'] = DisplayLib::boxMsg(_(sprintf("Coupon requires transaction of at least \$%.2f"), $ttlRequired));
            return array($req, $json);
        }

        $req->valid = true;
        return array($req, $json);
    }

    private function validateQty($qty, $couponqtty, $req, $json)
    {
        $available_qty = $qty - ($couponqtty * $req->value);
        if ($available_qty < $req->value) {
            // Coupon requirement not met
            if ($couponqtty > 0) {
                $json['output'] = DisplayLib::boxMsg(_("Coupon already applied"));
            } else {
                $json['output'] = DisplayLib::boxMsg(sprintf(_("Coupon requires %d items"), $req->value));
            }
            $req->valid = false;
            return array($req, $json);
        }

        $req->valid = true;
        return array($req, $json);
    }

}

/*
$obj = new DatabarCoupon();
$obj->handle("8110100707340143853100110110",array());
$obj->handle("811010041570000752310011020096000",array());
$obj->handle("8110007487303085831001200003101130",array());
*/

