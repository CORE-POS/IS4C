<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

class UPC extends Parser 
{
    /**
      Defines how the UPC was entered.
      Known good values are:
      - keyed
      - scanned
      - macro
      - hid
    */
    private $source = 'keyed';

    const GENERIC_STATUS = 'NA';

    const SCANNED_PREFIX = '0XA';
    const SCANNED_STATUS = 'SS';

    const MACRO_PREFIX = '0XB';
    const MACRO_STATUS = 'KB';

    const HID_PREFIX = '0XC';
    const HID_STATUS = 'HI';

    /**
      The default case is pretty simple. A numeric string
      is checked as a UPC.
      
      The 0XA prefix indicates a scanned value from the scale.
      This prefix was selected because PHP's validation still
      considers the whole string a [hex] number. That helps with
      overall input validation. A complex entry like:
          5*0XA001234567890
      Is handled correctly because there's a "number" on both 
      sides of asterisk. The prefix is then stripped off by
      this parser to get the actual UPC value.

      The GS1~ prefix is an old artificat of wedge compatibility.
      Using something like 0XB instead would probably be
      an improvement.
    */
    function check($str)
    {
        if (is_numeric($str) && strlen($str) < 16) {
            return true;
        } elseif (substr($str,0,strlen(self::SCANNED_PREFIX)) == self::SCANNED_PREFIX && is_numeric($substr($str, 3))) {
            return true;
        } elseif (substr($str,0,strlen(self::MACRO_PREFIX)) == self::MACRO_PREFIX && is_numeric($substr($str, 3))) {
            return true;
        } elseif (substr($str,0,strlen(self::HID_PREFIX)) == self::HID_PREFIX && is_numeric($substr($str, 3))) {
            return true;
        } elseif (substr($str,0,4) == "GS1~" && is_numeric(substr($str, 4))) {
            return true;
        }

        return false;
    }

    function parse($str)
    {
        if (substr($str,0,4) == "GS1~") {
            $str = $this->fixGS1($str);
            $this->source = self::SCANNED_PREFIX;
        } elseif (substr($str, 0, strlen(self::SCANNED_PREFIX)) == self::SCANNED_PREFIX) {
            $str = substr($str, strlen(self::SCANNED_PREFIX));
            $this->source = self::SCANNED_PREFIX;
        } elseif (substr($str, 0, strlen(self::MACRO_PREFIX)) == self::MACRO_PREFIX) {
            $str = substr($str, strlen(self::MACRO_PREFIX));
            $this->source = self::MACRO_PREFIX;
        } elseif (substr($str, 0, strlen(self::HID_PREFIX)) == self::HID_PREFIX) {
            $str = substr($str, strlen(self::HID_PREFIX));
            $this->source = self::HID_PREFIX;
        }

        /**
          Do not apply scanned items if
          tare has been entered
        */
        if (CoreLocal::get('tare') > 0 && $this->source == self::SCANNED_PREFIX) {
            return $this->default_json();
        }

        return $this->upcscanned($str);
    }

    function upcscanned($entered) 
    {
        $my_url = MiscLib::base_url();
        $ret = $this->default_json();

        /* force cashiers to enter a comment on refunds */
        if (CoreLocal::get("refund")==1 && CoreLocal::get("refundComment") == ""){
            $ret['udpmsg'] = 'twoPairs';
            if (CoreLocal::get("SecurityRefund") > 20){
                $ret['main_frame'] = $my_url."gui-modules/adminlogin.php?class=RefundAdminLogin";
            }
            else
                $ret['main_frame'] = $my_url.'gui-modules/refundComment.php';
            CoreLocal::set("refundComment",CoreLocal::get("strEntered"));
            return $ret;
        }
        if (CoreLocal::get('itemPD') > 0 && CoreLocal::get('SecurityLineItemDiscount') == 30 && CoreLocal::get('msgrepeat')==0){
            $ret['main_frame'] = $my_url."gui-modules/adminlogin.php?class=LineItemDiscountAdminLogin";
            return $ret;
        }

        /**
          11Sep14 Andy
          Disabled until keypress double form submission is
          fixed on paycard confirmation screen. Depending on
          sequence can case flag to be raised, cleared, and
          re-raised leading to spurrious error notifications
        */
        if (false && CoreLocal::get('paycardTendered')) {
            if (CoreLocal::get('msgrepeat') == 0 || CoreLocal::get('lastRepeat') != 'paycardAlreadyApplied') {
                CoreLocal::set('boxMsg', 'Card already tendered<br />
                                            Confirm adding more items');
                CoreLocal::set('lastRepeat', 'paycardAlreadyApplied');
                $ret['main_frame'] = $my_url . 'gui-modules/boxMsg2.php';

                return $ret;
            } else if (CoreLocal::get('lastRepeat') == 'paycardAlreadyApplied') {
                CoreLocal::set('lastRepeat', '');
                CoreLocal::set('paycardTendered', false);
            }
        }

        // leading/trailing whitespace creates issues
        $entered = trim($entered);

        // 6Jan14 - I have no idea why this is here
        // unless some else does, it's probably legacy
        // cruft. spaces in UPCs are bad.
        //$entered = str_replace(".", " ", $entered);

        $quantity = CoreLocal::get("quantity");
        if (CoreLocal::get("quantity") == 0 && CoreLocal::get("multiple") == 0) $quantity = 1;

        /* exapnd UPC-E */
        if (substr($entered, 0, 1) == 0 && strlen($entered) == 7) {
            $p6 = substr($entered, -1);
            if ($p6 == 0) $entered = substr($entered, 0, 3)."00000".substr($entered, 3, 3);
            elseif ($p6 == 1) $entered = substr($entered, 0, 3)."10000".substr($entered, 3, 3);
            elseif ($p6 == 2) $entered = substr($entered, 0, 3)."20000".substr($entered, 3, 3);
            elseif ($p6 == 3) $entered = substr($entered, 0, 4)."00000".substr($entered, 4, 2);
            elseif ($p6 == 4) $entered = substr($entered, 0, 5)."00000".substr($entered, 5, 1);
            else $entered = substr($entered, 0, 6)."0000".$p6;
        }

        /* make sure upc length is 13 */
        $upc = "";
        if (CoreLocal::get('EanIncludeCheckDigits') != 1) {
            /** 
              If EANs do not include check digits, the value is 13 digits long,
              and the value does not begin with a zero, it most likely
              represented a hand-keyed EAN13 value w/ check digit. In this configuration
              it's probably a miskey so trim the last digit.
            */
            if (strlen($entered) == 13 && substr($entered, 0, 1) != 0) {
                $upc = "0".substr($entered, 0, 12);
            }
        }
        // pad value to 13 digits
        $upc = substr("0000000000000".$entered, -13);

        /* extract scale-sticker prices 
           Mixed check digit settings do not work here. 
           Scale UPCs and EANs must uniformly start w/
           002 or 02.
        */
        $scalePrefix = '002';
        $scaleStickerItem = false;
        $scaleCheckDigits = false;
        if (CoreLocal::get('UpcIncludeCheckDigits') == 1) {
            $scalePrefix = '02';
            $scaleCheckDigits = true;
        }
        $scalepriceUPC = 0;
        $scalepriceEAN = 0;
        // prefix indicates it is a scale-sticker
        if (substr($upc, 0, strlen($scalePrefix)) == $scalePrefix) {
            $scaleStickerItem = true;
            // extract price portion of the barcode
            // position varies depending whether a check
            // digit is present in the upc
            if ($scaleCheckDigits) {
                $scalepriceUPC = MiscLib::truncate2(substr($upc, 8, 4)/100);
                $scalepriceEAN = MiscLib::truncate2(substr($upc, 7, 5)/100);
            } else {
                $scalepriceUPC = MiscLib::truncate2(substr($upc, -4)/100);
                $scalepriceEAN = MiscLib::truncate2(substr($upc, -5)/100);
            }
            $rewrite_class = CoreLocal::get('VariableWeightReWriter');
            if ($rewrite_class === '' || !class_exists($rewrite_class)) {
                $rewrite_class = 'ZeroedPriceReWrite';
            }
            $rewriter = new $rewrite_class();
            $upc = $rewriter->translate($upc, $scaleCheckDigits);
            // I think this is WFC special casing; needs revising.
            if ($upc == "0020006000000" || $upc == "0020010000000") $scalepriceUPC *= -1;
        }

        $db = Database::pDataConnect();
        $table = $db->table_definition('products');
        $query = "SELECT inUse,upc,description,normal_price,scale,deposit,
            qttyEnforced,department,local,cost,tax,foodstamp,discount,
            discounttype,specialpricemethod,special_price,groupprice,
            pricemethod,quantity,specialgroupprice,specialquantity,
            mixmatchcode,idEnforced,tareweight,scaleprice";
        // New column 16Apr14
        if (isset($table['line_item_discountable'])) {
            $query .= ', line_item_discountable';
        } else {
            $query .= ', 1 AS line_item_discountable';
        }
        // New column 16Apr14
        if (isset($table['formatted_name'])) {
            $query .= ', formatted_name';
        } else {
            $query .= ', \'\' AS formatted_name';
        }
        // New column 25Nov14
        if (isset($table['special_limit'])) {
            $query .= ', special_limit';
        } else {
            $query .= ', 0 AS special_limit';
        }
        $query .= " FROM products WHERE upc = '".$upc."'";
        $result = $db->query($query);
        $num_rows = $db->num_rows($result);

        /* check for special upcs that aren't really products */
        if ($num_rows == 0){
            $objs = CoreLocal::get("SpecialUpcClasses");
            foreach($objs as $class_name){
                $instance = new $class_name();
                if ($instance->isSpecial($upc)){
                    return $instance->handle($upc,$ret);
                }
            }
            // no match; not a product, not special
            
            $handler = CoreLocal::get('ItemNotFound');
            if ($handler === '' || !class_exists($handler)) {
                $handler = 'ItemNotFound';
            }
            $obj = new $handler();
            $ret = $obj->handle($upc, $ret);

            return $ret;
        }

        /* product exists
           BEGIN error checking round #1
        */
        $row = $db->fetch_array($result);

        /**
          If formatted_name is present, copy it directly over
          products.description. This way nothing further down
          the process has to worry about the distinction between
          two potential naming fields.
        */
        if ($row['formatted_name'] != '') {
            $row['description'] = $row['formatted_name'];
        }

        /* Implementation of inUse flag
         *   if the flag is not set, display a warning dialog noting this
         *   and allowing the sale to be confirmed or canceled
         */
        if ($row["inUse"] == 0){
            if (CoreLocal::get('msgrepeat') == 0){
                CoreLocal::set("strEntered",$row["upc"]);
                CoreLocal::set("boxMsg","<b>".$row["upc"]." - ".$row["description"]."</b>
                    <br />"._("Item not for sale"));
                CoreLocal::set('boxMsgButtons', array(
                    'Continue Sale [enter]' => '$(\'#reginput\').val(\'\');submitWrapper();',
                    'Cancel [clear]' => '$(\'#reginput\').val(\'CL\');submitWrapper();',
                ));
                $ret['main_frame'] = $my_url."gui-modules/boxMsg2.php?quiet=1";
                return $ret;
            }
        }

        /**
          Apply special department handlers
          based on item's department
        */
        $deptmods = CoreLocal::get('SpecialDeptMap');
        if (is_array($deptmods) && isset($deptmods[$row['department']])){
            foreach($deptmods[$row['department']] as $mod){
                $obj = new $mod();
                $ret = $obj->handle($row['department'],$row['normal_price'],$ret);
                if ($ret['main_frame'])
                    return $ret;
            }
        }

        /**
          Detect if a by-weight item has the same weight as the last by-weight
          item. This can indicate a stuck scale.
          The giant if determines whether the item is scalable, that we
          know the weight, and that we know the previous weight (lastWeight)
        
          Pre-weighed items (upc starts with 002) are ignored because they're not
          weighed here. Scalable items that cost one cent are ignored as a special
          case; they're normally entered by keying a quantity multiplier
        */
        if ($num_rows > 0 && $row['scale'] == 1 
            && CoreLocal::get("lastWeight") > 0 && CoreLocal::get("weight") > 0
            && abs(CoreLocal::get("weight") - CoreLocal::get("lastWeight")) < 0.0005
            && !$scaleStickerItem && abs($row['normal_price']) > 0.01){
            if (CoreLocal::get('msgrepeat') == 0){
                CoreLocal::set("strEntered",$row["upc"]);
                CoreLocal::set("boxMsg","<b>Same weight as last item</b>");
                CoreLocal::set('boxMsgButtons', array(
                    'Confirm Weight [enter]' => '$(\'#reginput\').val(\'\');submitWrapper();',
                    'Cancel [clear]' => '$(\'#reginput\').val(\'CL\');submitWrapper();',
                ));
                $ret['main_frame'] = $my_url."gui-modules/boxMsg2.php?quiet=1";
                return $ret;
            }
        }

        if ($row["idEnforced"] > 0){

            $restrictQ = "SELECT upc,dept_ID FROM dateRestrict WHERE
                ( upc='{$row['upc']}' AND
                  ( ".$db->datediff($db->now(),'restrict_date')."=0 OR
                    ".$db->dayofweek($db->now())."=restrict_dow
                  ) AND
                  ( (restrict_start IS NULL AND restrict_end IS NULL) OR
                    ".$db->curtime()." BETWEEN restrict_start AND restrict_end
                  )
                 ) OR 
                ( dept_ID='{$row['department']}' AND
                  ( ".$db->datediff($db->now(),'restrict_date')."=0 OR
                    ".$db->dayofweek($db->now())."=restrict_dow
                  ) AND
                  ( (restrict_start IS NULL AND restrict_end IS NULL) OR
                    ".$db->curtime()." BETWEEN restrict_start AND restrict_end
                  )
                )";
            $restrictR = $db->query($restrictQ);
            if ($db->num_rows($restrictR) > 0){
                $ret['output'] = DisplayLib::boxMsg(
                    _('product cannot be sold right now'),
                    _('Date Restriction'),
                    false,
                    DisplayLib::standardClearButton()
                );
                return $ret;
            }

            if (CoreLocal::get("cashierAge") < 18 && CoreLocal::get("cashierAgeOverride") != 1){
                $ret['main_frame'] = $my_url."gui-modules/adminlogin.php?class=AgeApproveAdminLogin";
                return $ret;
            }

            if (CoreLocal::get("memAge")=="") {
                CoreLocal::set("memAge",date('Ymd'));
            }
            $ts = strtotime(CoreLocal::get("memAge"));
            $required_age = $row['idEnforced'];
            $of_age_on_day = mktime(0, 0, 0, date('n', $ts), date('j', $ts), date('Y', $ts) + $required_age);
            $today = strtotime( date('Y-m-d') );
            if ($of_age_on_day > $today) {
                $ret['udpmsg'] = 'twoPairs';
                $ret['main_frame'] = $my_url.'gui-modules/requestInfo.php?class=UPC';
                return $ret;
            }
        }

        /**
          Apply automatic tare weight
        */
        if ($row['tareweight'] > 0){
            $peek = PrehLib::peekItem();
            if (strstr($peek,"** Tare Weight") === False)
                TransRecord::addTare($row['tareweight']*100);
        } elseif ($row['scale'] != 0 && !CoreLocal::get("tare") && Plugin::isEnabled('PromptForTare') && !CoreLocal::get("tarezero")) {
            $ret['main_frame'] = $my_url.'plugins/PromptForTare/TarePromptInputPage.php?class=UPC&item='.$entered;
            return $ret;
        } else {
            CoreLocal::set('tarezero', False);
        }

        /* sanity check - ridiculous price 
           (can break db column if it doesn't fit
        */
        if (strlen($row["normal_price"]) > 8){
            $ret['output'] = DisplayLib::boxMsg(
                $upc . '<br />' . _("Claims to be more than $100,000"),
                _('Invalid Item'),
                false,
                DisplayLib::standardClearButton()
            );
            return $ret;
        }

        $scale = ($row["scale"] == 0) ? 0 : 1;
        /* use scaleprice bit column to indicate 
           whether values should be interpretted as 
           UPC or EAN */ 
        $scaleprice = ($row['scaleprice'] == 0) ? $scalepriceUPC : $scalepriceEAN;

        /* need a weight with this item
           retry the UPC in a few milliseconds and see
        */
        if ($scale != 0 && CoreLocal::get("weight") == 0 && 
            CoreLocal::get("quantity") == 0 && !$scaleStickerItem) {

            CoreLocal::set("SNR",CoreLocal::get('strEntered'));
            $ret['output'] = DisplayLib::boxMsg(
                _("please put item on scale"),
                'Weighed Item',
                true,
                DisplayLib::standardClearButton()
            );
            
            return $ret;
        }

        /* got a scale weight, make sure the tare
           is valid */
        if ($scale != 0 && !$scaleStickerItem) {
            $quantity = CoreLocal::get("weight") - CoreLocal::get("tare");
            if (CoreLocal::get("quantity") != 0) 
                $quantity = CoreLocal::get("quantity") - CoreLocal::get("tare");

            if ($quantity <= 0) {
                $ret['output'] = DisplayLib::boxMsg(
                    _("item weight must be greater than tare weight"),
                    _('Invalid Weight'),
                    false,
                    DisplayLib::standardClearButton()
                );
                return $ret;
            }
            CoreLocal::set("tare",0);
        }

        /* non-scale items need integer quantities */    
        if ($row["scale"] == 0 && (int) CoreLocal::get("quantity") != CoreLocal::get("quantity") ) {
            $ret['output'] = DisplayLib::boxMsg(
                _("fractional quantity cannot be accepted for this item"),
                _('Invalid Quantity'),
                false,
                DisplayLib::standardClearButton()
            );
            return $ret;
        }

        /* quantity required for this item. Send to
           entry page if one wasn't provided */
        $qttyEnforced = $row["qttyEnforced"];
        if (($qttyEnforced == 1) && (CoreLocal::get("multiple") == 0) && (CoreLocal::get("msgrepeat" == 0) || CoreLocal::get('qttyvalid') == 0)) {
            $ret['main_frame'] = $my_url."gui-modules/qtty2.php";
            return $ret;
        }
        else
            CoreLocal::set("qttyvalid",1); // this may be unnecessary

        /* wedge I assume
           I don't like this being hard-coded, but since these UPCs
           are entries in products they can't go in a SpecialUPC
           object (unless SpecialUPC checks take place on every
           scan, but that's more overhead than I want on such a common
           operation
        */
        if ($upc == "0000000008010" && CoreLocal::get("msgrepeat") == 0) {
            CoreLocal::set("boxMsg","<b>".$total." gift certificate</b><br />
                "._("insert document"));
            CoreLocal::set('boxMsgButtons', array(
                'Endorse [enter]' => '$(\'#reginput\').val(\'\');submitWrapper();',
                'Cancel [clear]' => '$(\'#reginput\').val(\'CL\');submitWrapper();',
            ));
            $ret["main_frame"] = $my_url."gui-modules/boxMsg2.php?endorse=giftcert&endorseAmt=".$total;
            return $ret;
        }

        /* wedge I assume
           see 0000000008010 above
        */
        if ($upc == "0000000008011" && CoreLocal::get("msgrepeat") == 0) {
            CoreLocal::set("boxMsg","<b>".$total." class registration</b><br />
                "._("insert form"));
            CoreLocal::set('boxMsgButtons', array(
                'Endorse [enter]' => '$(\'#reginput\').val(\'\');submitWrapper();',
                'Cancel [clear]' => '$(\'#reginput\').val(\'CL\');submitWrapper();',
            ));
            $ret["main_frame"] = $my_url."gui-modules/boxMsg2.php?endorse=classreg&endorseAmt=".$total;
            return $ret;
        }

        /*
           END error checking round #1
        */    

        // wfc uses deposit field to link another upc
        if (isset($row["deposit"]) && $row["deposit"] > 0){
            $dupc = (int)$row["deposit"];
            $this->addDeposit($dupc);
        }

        $upc = $row["upc"];
        $row['numflag'] = isset($row["local"])?$row["local"]:0;
        $row['description'] = str_replace("'","",$row['description']);

        /* do tax shift */
        $tax = $row['tax'];
        if (CoreLocal::get("toggletax") != 0) {
            $tax = ($tax==0) ? 1 : 0;
            CoreLocal::set("toggletax",0);
        }
        $row['tax'] = $tax;

        /* do foodstamp shift */
        $foodstamp = $row["foodstamp"];
        if (CoreLocal::get("togglefoodstamp") != 0){
            CoreLocal::set("togglefoodstamp",0);
            $foodstamp = ($foodstamp==0) ? 1 : 0;
        }
        $row['foodstamp'] = $foodstamp;

        /* do discount shifts */
        $discountable = $row["discount"];
        if (CoreLocal::get("toggleDiscountable") == 1) {
            CoreLocal::set("toggleDiscountable",0);
            $discountable = ($discountable == 0) ? 1 : 0;
        }
        $row['discount'] = $discountable;

        /**
          Enforce per-transaction sale limits
        */
        if ($row['special_limit'] > 0) {
            $appliedQ = "
                SELECT SUM(quantity) AS saleQty
                FROM " . CoreLocal::get('tDatabase') . $db->sep() . "localtemptrans
                WHERE discounttype <> 0
                    AND (
                        upc='{$row['upc']}'
                        OR (mixMatch='{$row['mixmatchcode']}' AND mixMatch<>''
                            AND mixMatch<>'0' AND mixMatch IS NOT NULL)
                    )";
            $appliedR = $db->query($appliedQ);
            if ($appliedR && $db->num_rows($appliedR)) {
                $appliedW = $db->fetch_row($appliedR);
                if (($appliedW['saleQty']+$quantity) > $row['special_limit']) {
                    $row['discounttype'] = 0;
                    $row['special_price'] = 0;
                    $row['specialpricemethod'] = 0;
                    $row['specialquantity'] = 0;
                    $row['specialgroupprice'] = 0;
                }
            }
        }

        /*
            BEGIN: figure out discounts by type
        */

        /* get discount object 

           CORE reserves values 0 through 63 in 
           DiscountType::$MAP for default options.

           Additional discounts provided by plugins
           can use values 64 through 127. Because
           the DiscountTypeClasses array is zero-indexed,
           subtract 64 as an offset  
        */
        $discounttype = MiscLib::nullwrap($row["discounttype"]);
        $DiscountObject = null;
        $DTClasses = CoreLocal::get("DiscountTypeClasses");
        if ($row['discounttype'] < 64 && isset(DiscountType::$MAP[$row['discounttype']])) {
            $class = DiscountType::$MAP[$row['discounttype']];
            $DiscountObject = new $class();
        } else if ($row['discounttype'] >= 64 && isset($DTClasses[($row['discounttype']-64)])) {
            $class = $DTClasses[($row['discounttype'])-64];
            $DiscountObject = new $class();
        } else {
            // If the requested discounttype isn't available,
            // fallback to normal pricing. Debatable whether
            // this should be a hard error.
            $DiscountObject = new NormalPricing();
        }

        /* add in sticker price and calculate a quantity
           if the item is stickered, scaled, and on sale. 

           otherwise, if the item is sticked, scaled, and
           not on sale but has a non-zero price attempt
           to calculate a quantity. this makes the quantity
           field more consistent for reporting purposes.
           however, if the calculated quantity somehow
           introduces a rounding error fall back to the
           sticker's price. for non-sale items, the price
           the customer pays needs to match the sticker
           price exactly.

           items that are not scaled do not need a fractional
           quantity and items that do not have a normal_price
           assigned cannot calculate a proper quantity.
        */
        if ($scaleStickerItem) {
            if ($DiscountObject->isSale() && $scale == 1 && $row['normal_price'] != 0) {
                $quantity = MiscLib::truncate2($scaleprice / $row["normal_price"]);
            } else if ($scale == 1 && $row['normal_price'] != 0) {
                $quantity = MiscLib::truncate2($scaleprice / $row["normal_price"]);
                if (round($scaleprice, 2) != round($quantity * $row['normal_price'], 2)) {
                    $quantity = 1.0;
                    $row['normal_price'] = $scaleprice;
                } 
            } else {
                $row['normal_price'] = $scaleprice;
            }
        }

        /*
            END: figure out discounts by type
        */

        /* get price method object  & add item
        
           CORE reserves values 0 through 99 in 
           PriceMethod::$MAP for default methods.

           Additional methods provided by plugins
           can use values 100 and up. Because
           the PriceMethodClasses array is zero-indexed,
           subtract 100 as an offset  
        */
        $pricemethod = MiscLib::nullwrap($row["pricemethod"]);
        if ($DiscountObject->isSale())
            $pricemethod = MiscLib::nullwrap($row["specialpricemethod"]);
        $PMClasses = CoreLocal::get("PriceMethodClasses");
        $PriceMethodObject = null;

        $status = self::GENERIC_STATUS;
        switch ($this->source) {
            case self::SCANNED_PREFIX:
                $status = self::SCANNED_STATUS;
                break;
            case self::MACRO_PREFIX:
                $status = self::MACRO_STATUS;
                break;
            case self::HID_PREFIX:
                $status = self::HID_STATUS;
                break;
        }
        $row['trans_subtype'] = $status;

        if ($pricemethod < 100 && isset(PriceMethod::$MAP[$pricemethod])) {
            $class = PriceMethod::$MAP[$pricemethod];
            $PriceMethodObject = new $class();
        } else if ($pricemethod >= 100 && isset($PMClasses[($pricemethod-100)])) {
            $class = $PMClasses[($pricemethod-100)];
            $PriceMethodObject = new $class();
        } else {
            $PriceMethodObject = new BasicPM();
        }
        // prefetch: otherwise object members 
        // pass out of scope in addItem()
        $prefetch = $DiscountObject->priceInfo($row,$quantity);
        $added = $PriceMethodObject->addItem($row, $quantity, $DiscountObject);

        if (!$added) {
            $ret['output'] = DisplayLib::boxMsg(
                $PriceMethodObject->errorInfo(),
                '',
                false,
                DisplayLib::standardClearButton()
            );
            return $ret;
        }

        /* add discount notifications lines, if applicable */
        $DiscountObject->addDiscountLine();

        // cleanup, reset flags and beep
        if ($quantity != 0) {

            CoreLocal::set("msgrepeat",0);
            CoreLocal::set("qttyvalid",0);

            $ret['udpmsg'] = 'goodBeep';
        }

        /* reset various flags and variables */
        if (CoreLocal::get("tare") != 0) CoreLocal::set("tare",0);
        CoreLocal::set("ttlflag",0);
        CoreLocal::set("fntlflag",0);
        CoreLocal::set("quantity",0);
        CoreLocal::set("itemPD",0);
        Database::setglobalflags(0);

        /* output item list, update totals footer */
        $ret['redraw_footer'] = True;
        $ret['output'] = DisplayLib::lastpage();

        if ($prefetch['unitPrice']==0 && $discounttype == 0){
            $ret['main_frame'] = $my_url.'gui-modules/priceOverride.php';
        }

        return $ret;
    }

    private function addDeposit($upc)
    {
        $upc = str_pad($upc,13,'0',STR_PAD_LEFT);

        $db = Database::pDataConnect();
        $query = "select description,scale,tax,foodstamp,discounttype,
            discount,department,normal_price
                   from products where upc='".$upc."'";
        $result = $db->query($query);

        if ($db->num_rows($result) <= 0) return;

        $row = $db->fetch_array($result);
        
        $description = $row["description"];
        $description = str_replace("'", "", $description);
        $description = str_replace(",", "", $description);

        $scale = 0;
        if ($row["scale"] != 0) $scale = 1;

        $tax = 0;
        if ($row["tax"] > 0 && CoreLocal::get("toggletax") == 0) {
            $tax = $row["tax"];
        } else if ($row["tax"] > 0 && CoreLocal::get("toggletax") == 1) {
            $tax = 0;
            CoreLocal::set("toggletax",0);
        } else if ($row["tax"] == 0 && CoreLocal::get("toggletax") == 1) {
            $tax = 1;
            CoreLocal::set("toggletax",0);
        }
                        
        $foodstamp = 0;
        if ($row["foodstamp"] != 0 && CoreLocal::get("togglefoodstamp") == 0) {
            $foodstamp = 1;
        } else if ($row["foodstamp"] != 0 && CoreLocal::get("togglefoodstamp") == 1) {
            $foodstamp = 0;
            CoreLocal::set("togglefoodstamp",0);
        } else if ($row["foodstamp"] == 0 && CoreLocal::get("togglefoodstamp") == 1) {
            $foodstamp = 1;
            CoreLocal::set("togglefoodstamp",0);
        }

        $discounttype = MiscLib::nullwrap($row["discounttype"]);
        $discountable = $row["discount"];

        $quantity = 1;
        if (CoreLocal::get("quantity") != 0) {
            $quantity = CoreLocal::get("quantity");
        }

        $save_refund = CoreLocal::get("refund");

        TransRecord::addRecord(array(
            'upc' => $upc,
            'description' => $description,
            'trans_type' => 'I',
            'department' => $row['department'],
            'quantity' => $quantity,
            'ItemQtty' => $quantity,
            'unitPrice' => $row['normal_price'],
            'total' => $quantity * $row['normal_price'],
            'regPrice' => $row['normal_price'],
            'scale' => $scale,
            'tax' => $tax,
            'foodstamp' => $foodstamp,
            'discountable' => $discountable,
            'discounttype' => $discounttype,
        ));

        CoreLocal::set("refund",$save_refund);
    }

    function fixGS1($str){
        // remove GS1~ prefix + two additional characters
        $str = substr($str,6);

        // check application identifier

        // coupon; return whole thing
        if (substr($str,0,4) == "8110")
            return $str;

        // GTIN-14; return w/o check digit,
        // ignore any other fields for now
        if (substr($str,0,1) == "10")
            return substr($str,2,13);
        
        // application identifier not recognized
        // will likely cause no such item error
        return $str; 
    }

    public static $requestInfoHeader = 'customer age';
    public static $requestInfoMsg = 'Type customer birthdate YYYYMMDD';
    public static function requestInfoCallback($info)
    {
        if ((is_numeric($info) && strlen($info)==8) || $info == 1){
            CoreLocal::set("memAge",$info);
            CoreLocal::set('strRemembered', CoreLocal::get('strEntered'));
            CoreLocal::set('msgrepeat', 1);
            return True;
        }
        return False;
    }

    public static $requestTareHeader = 'Enter Tare';
    public static $requestTareMsg = 'Type tare weight or eneter for default';
    public static function requestTareCallback($tare, $in_item) {
        if (is_numeric($tare)) {
            TransRecord::addTare($tare);
            $ret_url = '../../gui-modules/pos2.php?reginput='.$in_item;
            return $ret_url;
        }
        return False;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td><i>product number</i></td>
                <td>Try to ring up the specified product.
                Coupon handling is included here</td>
            </tr>
            </table>";
    }
}

?>
