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
        global $CORE_LOCAL;

        $pos = 0;
        $first_req = array();

        /* STEP 1 - REQUIRED FIELDS */

        // remove prefix 8110
        $pos += 4;

        // grab company prefix length, remove it from barcode
        $prefix_length = ((int)$upc[$pos]) + 6;
        $pos += 1;

        // grab company prefix, remove from barcode
        $first_req['man_id'] = substr($upc,$pos,$prefix_length);
        $pos += $prefix_length;

        // this way all prefixes map against
        // localtemptrans.upc[2,length]
        if ($prefix_length==6) {
            $first_req['man_id'] = "0".$first_req['man_id'];
        }

        // grab offer code, remove from barcode
        $offer = substr($upc,$pos,6);
        $pos += 6;

        // read value length
        $val_length = (int)$upc[$pos];
        $pos += 1;

        // read value
        $value = (int)substr($upc,$pos,$val_length);
        $pos += $val_length;

        // read primary requirement length
        $req_length = (int)$upc[$pos];
        $pos += 1;

        // read primary requirement value
        $first_req['value'] = substr($upc,$pos,$req_length);
        $pos += $req_length;

        // read primary requirement type-code
        $first_req['code'] = $upc[$pos];
        $pos += 1;

        // read primary requirement family code
        $first_req['family'] = substr($upc,$pos,3);
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
        $second_req = array();
        $req_rules_code = 1;
        $duplicate_prefix_flag = false;
        if (isset($upc[$pos]) && $upc[$pos] == "1") {
            $pos += 1;

            $rules_code = $upc[$pos];
            $pos += 1;
        
            $sr_length = (int)$upc[$pos];        
            $pos += 1;
            $second_req['value'] = substr($upc,$pos,$sr_length);
            $pos += $sr_length;

            $second_req['code'] = $upc[$pos];
            $pos += 1;

            $second_req['family'] = substr($upc,$pos,3);
            $pos += 3;

            $sm_length = ((int)$upc[$pos]) + 6;
            $pos += 1;
            if ($sm_length == 15) { // 9+6
                $second_req['man_id'] = $first_req['man_id'];
                $duplicate_prefix_flag = true;
            } else {
                $second_req['man_id'] = substr($upc,$pos,$sm_length);
                $pos += $sm_length;

                if ($sm_length == 6) {
                    $second_req['man_id'] = "0".$second_req['man_id'];
                }
            }
        }

        // third required item
        $third_req = array();
        if (isset($upc[$pos]) && $upc[$pos] == "2") {
            $pos += 1;

            $tr_length = (int)$upc[$pos];        
            $pos += 1;
            $third_req['value'] = substr($upc,$pos,$tr_length);
            $pos += $tr_length;

            $third_req['code'] = $upc[$pos];
            $pos += 1;

            $third_req['family'] = substr($upc,$pos,3);
            $pos += 3;

            $tm_length = ((int)$upc[$pos]) + 6;
            $pos += 1;
            if ($tm_length == 15) { // 9+6
                $third_req['man_id'] = $first_req['man_id'];
                $duplicate_prefix_flag = true;
            } else {
                $third_req['man_id'] = substr($upc,$pos,$tm_length);
                $pos += $tm_length;

                if ($tm_length == 6) {
                    $third_req['man_id'] = "0".$third_req['man_id'];
                }
            }
        }

        if ($duplicate_prefix_flag) {
            $first_req['man_id'] .= $first_req['family'];
            $second_req['man_id'] .= $second_req['family'];
            $third_req['man_id'] .= $third_req['family'];
        }

        // expiration date
        if (isset($upc[$pos]) && $upc[$pos] == "3") {
            $pos += 1;
            $expires = substr($upc,$pos,6);
            $pos += 6;

            $y = "20".substr($expires,0,2);
            $m = substr($expires,2,2);
            $d = substr($expires,4,2);

            $tstamp = mktime(23,59,59,$m,$d,$y);
            if ($tstamp < time()) {
                $json['output'] = DisplayLib::boxMsg("Coupon expired $m/$d/$y");
                return $json;
            }
        }

        // start date
        if (isset($upc[$pos]) && $upc[$pos] == "4") {
            $pos += 1;
            $starts = substr($upc,$pos,6);
            $pos += 6;

            $y = "20".substr($starts,0,2);
            $m = substr($starts,2,2);
            $d = substr($starts,4,2);

            $tstamp = mktime(0,0,0,$m,$d,$y);
            if ($tstamp > time()) {
                $json['output'] = DisplayLib::boxMsg("Coupon not valid until $m/$d/$y");
                return $json;
            }
        }
        
        // serial number
        $serial = false;
        if (isset($upc[$pos]) && $upc[$pos] == "5") {
            $pos += 1;
            $serial_length = ((int)$upc[$pos]) + 6;
            $pos += 1;
            $serial = substr($upc,$pos,$serial_length);
            $pos += $serial_length;
        }

        // retailer
        $retailer = false;
        if (isset($upc[$pos]) && $upc[$pos] == "6") {
            $pos += 1;
            $rt_length = ((int)$upc[$pos]) + 6;
            $pos += 1;
            $retailer = substr($upc,$pos,$rt_length);
            $pos += $rt_length;
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

        $primary = $this->validateRequirement($first_req, $json);
        if (!$primary && (count($second_req) == 0 || $req_rules_code == 1 || $req_rules_code == 2)) {
            // if the primary requirement isn't valid and
            //    a) there are no more requirments, or
            //    b) the primary requirement is mandatory
            // return the json. Error message should have been
            // set up by validateRequirement()
            return $json;
        }

        $secondary = $this->validateRequirement($second_req, $json);
        if (!$secondary && (count($third_req) == 0 || $req_rules_code == 1)) {
            // if the secondary requirment isn't valid and
            //    a) there are no more requirments, or
            //    b) all requirements are mandatory
            // return the json. Error message should have been
            // set up by validateRequirement()
            return $json;
        }

        $tertiary = $this->validateRequirement($third_req, $json);

        // compare requirement results with rules
        // return error message if applicable
        switch ($req_rules_code) {
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
                } else if (!$secondary && !$tertiary) {
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
                $json['output'] = DisplayLib::boxMsg("Malformed coupon");
                return $json;
        }

        /* End requirement validation */
    
        /* STEP 5 - determine coupon value */

        $val_arr = $first_req;
        if ($misc['value_applies'] == 1) {
            $val_arr = $second_req;
        } else if ($misc['value_applies'] == 2) {
            $val_arr = $third_req;
        }
            
        $value = 0;
        switch($misc['value_code']) {
            case '0': // value in cents
            case '6':
                $value = MiscLib::truncate2($val_arr['value'] / 100.00);
                break;
            case '1': // free item
                $value = $val_arr['price'];
                break;
            case '2': // multiple free items
                $value = MiscLib::truncate2($val_arr['price'] * $val_arr['value']);
                break;
            case '5': // percent off
                $value = MiscLib::truncate2($val_arr['price'] * ($val_arr['value']/100.00));
                break;
            default:
                $json['output'] = DisplayLib::boxMsg("Error: bad coupon");
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
        $upc_start = "0".$val_arr['man_id'];
        $offer = base_convert($offer,10,36);
        $remaining = 13 - strlen($upc_start);
        if (strlen($offer) < $remaining) {
            $offer = str_pad($offer,$remaining,'0',STR_PAD_LEFT);
        } elseif (strlen($offer) > $remaining) {
            $offer = substr($offer,0,$remaining);
        }
        $coupon_upc = $upc_start.$offer;

        TransRecord::addCoupon($coupon_upc, $row['department'], -1*$value);
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
        $db = Database::tDataConnect();

        /* simple case first; just wants total transaction value 
           no company prefixing
        */
        if ($req['code'] == 2) {
            $q = "SELECT SUM(total) FROM localtemptrans WHERE
                trans_type IN ('I','D','M')";
            $r = $db->query($q);
            $ttl_required = MiscLib::truncate2($req['value'] / 100.00);
            if ($db->num_rows($r) == 0) {
                $json['output'] = DisplayLib::boxMsg("Coupon requires transaction of at least \$$ttl_required");
                return false;
            }

            $w = $dbc->fetch_row($r);
            if ($w[0] < $ttl_required) {
                $json['output'] = DisplayLib::boxMsg("Coupon requires transaction of at least \$$ttl_required");
                return false;
            }
            return true;
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
        $result = $db->query($query);

        if ($db->num_rows($result) <= 0) {
            $json['output'] = DisplayLib::boxMsg("Coupon requirements not met");
            return false;
        }
        $row = $db->fetch_row($result);
        $req['price'] = $row['price'];

        switch($req['code']) {
            case '0': // various qtty requirements
            case '3':
            case '4':
                $available_qty = $row['qty'] - ($row['couponqtty'] * $req['value']);
                if ($available_qty < $req['value']) {
                    // Coupon requirement not met
                    if ($row['couponqtty'] > 0) {
                        $json['output'] = DisplayLib::boxMsg("Coupon already applied");
                    } else {
                        $json['output'] = DisplayLib::boxMsg("Coupon requires ".$req['value']." items");
                    }
                    return false;
                }
                break;
            case '1':
                $available_ttl = $row['total'] - ($row['couponqtty'] * $req['value']);
                if ($available_ttl < $req['value']) {
                    // Coupon requirement not met
                    if ($row['couponqtty'] > 0) {
                        $json['output'] = DisplayLib::boxMsg("Coupon already applied");
                    } else {
                        $json['output'] = DisplayLib::boxMsg("Coupon requires ".$req['value']." items");
                    }
                    return false;
                }
                break;
            case '9':
                $json['output'] = DisplayLib::boxMsg("Tender coupon manually");
                return false;
            default:
                $json['output'] = DisplayLib::boxMsg("Error: bad coupon");
                return false; 
        }

        return true; // requirement validated
    }

}

/*
$obj = new DatabarCoupon();
$obj->handle("8110100707340143853100110110",array());
$obj->handle("811010041570000752310011020096000",array());
$obj->handle("8110007487303085831001200003101130",array());
*/

