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

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\ItemNotFound;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\Scanning\DiscountType;
use COREPOS\pos\lib\Scanning\PriceMethod;
use COREPOS\pos\lib\Scanning\SpecialDept;
use COREPOS\pos\lib\Scanning\SpecialUPC;
use COREPOS\pos\parser\Parser;
use COREPOS\pos\plugins\Plugin;
use \CoreLocal;

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

    const GS1_PREFIX = 'GS1~RX';
    const GS1_STATUS = 'GS';

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
    public function check($str)
    {
        if (is_numeric($str) && strlen($str) < 16) {
            return true;
        } elseif ($this->getPrefix($str) !== false) { 
            return true;
        }

        return false;
    }

    private function prefixes()
    {
        return array(
            self::SCANNED_STATUS => self::SCANNED_PREFIX, 
            self::MACRO_STATUS => self::MACRO_PREFIX, 
            self::HID_STATUS => self::HID_PREFIX, 
            self::GS1_STATUS => self::GS1_PREFIX,
        );
    }

    private function getPrefix($str)
    {
        foreach ($this->prefixes() as $prefix) {
            $len = strlen($prefix);
            if (substr($str,0,$len) == $prefix && is_numeric(substr($str, $len))) {
                return $prefix;
            }
        }

        return false;
    }

    private function getStatus($source)
    {
        foreach ($this->prefixes() as $status => $prefix) {
            if ($source == $prefix) {
                return $status;
            }
        }

        return self::GENERIC_STATUS;
    }

    function parse($str)
    {
        $this->source = $this->getPrefix($str);
        if ($this->source == self::GS1_PREFIX) {
            $str = $this->fixGS1($str);
        }
        $this->status = self::GENERIC_STATUS;
        if ($this->source !== false) {
            $this->status = $this->getStatus($this->source);
        }

        /**
          Do not apply scanned items if
          tare has been entered
        */
        if ($this->session->get('tare') > 0 && $this->source === self::SCANNED_PREFIX) {
            return $this->default_json();
        }

        return $this->upcscanned($str);
    }

    private function upcscanned($entered) 
    {
        $ret = $this->default_json();

        $ret = $this->genericSecurity($ret);
        if ($ret['main_frame'] != '') {
            return $ret;
        }

        $upc = $this->sanitizeUPC($entered);

        list($upc,$scaleStickerItem,$scalepriceUPC,$scalepriceEAN) = $this->rewriteScaleSticker($upc);

        $row = $this->lookupItem($upc);

        /* check for special upcs that aren't really products */
        if (!$row) {
            return $this->nonProductUPCs($upc, $ret);
        }

        return $this->handleItem($upc, $row, $scaleStickerItem, $scalepriceUPC, $scalepriceEAN);
    }

    private function handleItem($upc, $row, $scaleStickerItem, $scalepriceUPC, $scalepriceEAN)
    {
        $ret = $this->default_json();
        $myUrl = MiscLib::baseURL();
        $dbc = Database::pDataConnect();

        $quantity = $this->session->get("quantity");
        if ($this->session->get("quantity") == 0 && $this->session->get("multiple") == 0) {
            $quantity = 1;
        }

        /* product exists
           BEGIN error checking round #1
        */

        /**
          If formatted_name is present, copy it directly over
          products.description. This way nothing further down
          the process has to worry about the distinction between
          two potential naming fields.
        */
        if ($row['formatted_name'] != '') {
            $row['description'] = $row['formatted_name'];
        }

        $this->checkInUse($row);

        /**
          Apply special department handlers
          based on item's department
        */
        $deptmods = $this->session->get('SpecialDeptMap');
        if (!is_array($deptmods) && ($this->session->get('NoCompat') == 1 || $dbc->table_exists('SpecialDeptMap'))) {
            $model = new \COREPOS\pos\lib\models\op\SpecialDeptMapModel($dbc);
            $deptmods = $model->buildMap();
            $this->session->set('SpecialDeptMap', $deptmods);
        }
        if (is_array($deptmods) && isset($deptmods[$row['department']])){
            foreach($deptmods[$row['department']] as $mod){
                $obj = SpecialDept::factory($mod, $this->session);
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
        if ($this->duplicateWeight($row, $scaleStickerItem)) {
            $this->session->set("strEntered",$row["upc"]);
            $this->session->set("boxMsg",_("<b>Same weight as last item</b>"));
            $this->session->set('boxMsgButtons', array(
                _('Confirm Weight [enter]') => '$(\'#reginput\').val(\'\');submitWrapper();',
                _('Cancel [clear]') => '$(\'#reginput\').val(\'CL\');submitWrapper();',
            ));
            $ret['main_frame'] = $myUrl."gui-modules/boxMsg2.php?quiet=1";
            return $ret;
        }

        if ($row["idEnforced"] > 0){

            if ($this->isDateRestricted($row)) {
                $ret['output'] = DisplayLib::boxMsg(
                    _('product cannot be sold right now'),
                    _('Date Restriction'),
                    false,
                    DisplayLib::standardClearButton()
                );
                return $ret;
            }

            list($badAge, $ret) = PrehLib::ageCheck($row['idEnforced'], $ret);
            if ($badAge === true) {
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
        } elseif ($row['scale'] != 0 && !$this->session->get("tare") && Plugin::isEnabled('PromptForTare') && !$this->session->get("tarezero")) {
            $ret['main_frame'] = $myUrl.'plugins/PromptForTare/TarePromptInputPage.php?item='.$upc;
            return $ret;
        } else {
            $this->session->set('tarezero', False);
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
        $qttyEnforced = $row["qttyEnforced"];
        /* use scaleprice bit column to indicate 
           whether values should be interpretted as 
           UPC or EAN */ 
        $scaleprice = ($row['scaleprice'] == 0) ? $scalepriceUPC : $scalepriceEAN;

        /* need a weight with this item
           retry the UPC in a few milliseconds and see
        */
        if ($scale != 0 && $this->session->get("weight") == 0 && $qttyEnforced == 0
            && $this->session->get("quantity") == 0 && !$scaleStickerItem) {

            $this->session->set("SNR",$this->session->get('strEntered'));
            $ret['output'] = DisplayLib::boxMsg(
                _("please put item on scale"),
                _('Weighed Item'),
                true,
                DisplayLib::standardClearButton()
            );
            
            return $ret;
        }

        /* quantity required for this item. Send to
           entry page if one wasn't provided */
        if (($qttyEnforced == 1) && ($this->session->get("multiple") == 0) && ($this->session->get("msgrepeat" == 0) || $this->session->get('qttyvalid') == 0)) {
            $ret['main_frame'] = 
                    $myUrl . 'gui-modules/QuantityEntryPage.php'
                    . '?entered-item=' . $this->session->get('strEntered')
                    . '&qty-mode=' . $scale;
            return $ret;
        } 

        /* got a scale weight, make sure the tare
           is valid */
        if ($scale != 0 && !$scaleStickerItem) {
            $quantity = $this->session->get("weight") - $this->session->get("tare");
            if ($this->session->get("quantity") != 0) 
                $quantity = $this->session->get("quantity") - $this->session->get("tare");

            if ($quantity <= 0) {
                $ret['output'] = DisplayLib::boxMsg(
                    _("item weight must be greater than tare weight"),
                    _('Invalid Weight'),
                    false,
                    DisplayLib::standardClearButton()
                );
                return $ret;
            }
            $this->session->set("tare",0);
        }

        /* non-scale items need integer quantities */    
        if ($row["scale"] == 0 && (int) $this->session->get("quantity") != $this->session->get("quantity") ) {
            $ret['output'] = DisplayLib::boxMsg(
                _("fractional quantity cannot be accepted for this item"),
                _('Invalid Quantity'),
                false,
                DisplayLib::standardClearButton()
            );
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

        list($tax, $foodstamp, $discountable) = PrehLib::applyToggles($row['tax'], $row['foodstamp'], $row['discount']);
        $row['tax'] = $tax;
        $row['foodstamp'] = $foodstamp;
        $row['discount'] = $discountable;

        $this->enforceSaleLimit($dbc, $row, $quantity);

        /*
            BEGIN: figure out discounts by type
        */

        $discountObject = DiscountType::getObject($row['discounttype'], $this->session);

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
            if ($discountObject->isSale() && $scale == 1 && $row['normal_price'] != 0) {
                $quantity = MiscLib::truncate2($scaleprice / $row["normal_price"]);
            } elseif ($scale == 1 && $row['normal_price'] != 0) {
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

        $row['trans_subtype'] = $this->status;
        $pricemethod = MiscLib::nullwrap($discountObject->isSale() ? $row['specialpricemethod'] : $row["pricemethod"]);
        $priceMethodObject = PriceMethod::getObject($pricemethod, $this->session);

        // prefetch: otherwise object members 
        // pass out of scope in addItem()
        $prefetch = $discountObject->priceInfo($row,$quantity);
        $added = $priceMethodObject->addItem($row, $quantity, $discountObject);

        if (!$added) {
            $ret['output'] = DisplayLib::boxMsg(
                $priceMethodObject->errorInfo(),
                '',
                false,
                DisplayLib::standardClearButton()
            );
            return $ret;
        }

        /* add discount notifications lines, if applicable */
        $discountObject->addDiscountLine();

        // cleanup, reset flags and beep
        if ($quantity != 0) {

            $this->session->set("msgrepeat",0);
            $this->session->set("qttyvalid",0);

            $ret['udpmsg'] = 'goodBeep';
        }

        /* reset various flags and variables */
        if ($this->session->get("tare") != 0) $this->session->set("tare",0);
        $this->session->set("ttlflag",0);
        $this->session->set("fntlflag",0);
        $this->session->set("quantity",0);
        $this->session->set("itemPD",0);
        Database::setglobalflags(0);

        /* output item list, update totals footer */
        $ret['redraw_footer'] = True;
        $ret['output'] = DisplayLib::lastpage();

        if ($prefetch['unitPrice']==0 && $row['discounttype'] == 0){
            $ret['main_frame'] = $myUrl.'gui-modules/priceOverride.php';
        }

        return $ret;
    }

    private function addDeposit($upc)
    {
        $upc = str_pad($upc,13,'0',STR_PAD_LEFT);

        $dbc = Database::pDataConnect();
        $query = "select description,scale,tax,foodstamp,discounttype,
            discount,department,normal_price
                   from products where upc='".$upc."'";
        $result = $dbc->query($query);

        if ($dbc->num_rows($result) <= 0) return;

        $row = $dbc->fetchRow($result);
        
        $description = $row["description"];
        $description = str_replace("'", "", $description);
        $description = str_replace(",", "", $description);

        $scale = 0;
        if ($row["scale"] != 0) $scale = 1;

        list($tax, $foodstamp, $discountable) = PrehLib::applyToggles($row['tax'], $row['foodstamp'], $row['discount']);

        $discounttype = MiscLib::nullwrap($row["discounttype"]);

        $quantity = 1;
        if ($this->session->get("quantity") != 0) {
            $quantity = $this->session->get("quantity");
        }

        $saveRefund = $this->session->get("refund");

        TransRecord::addRecord(array(
            'upc' => $upc,
            'description' => $description,
            'trans_type' => 'I',
            'trans_subtype' => 'AD',
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

        $this->session->set("refund",$saveRefund);
    }

    function fixGS1($str){
        // remove GS1~ prefix
        $str = substr($str, 6);

        // check application identifier

        // coupon; return whole thing
        if (substr($str,0,4) == "8110")
            return $str;

        // GTIN-14; return w/o check digit,
        // ignore any other fields for now
        if (substr($str,0,2) == "10")
            return substr($str,2,13);
        
        // application identifier not recognized
        // will likely cause no such item error
        return $str; 
    }

    public function expandUPCE($entered)
    {
        $par6 = substr($entered, -1);
        if ($par6 == 0) $entered = substr($entered, 0, 3)."00000".substr($entered, 3, 3);
        elseif ($par6 == 1) $entered = substr($entered, 0, 3)."10000".substr($entered, 3, 3);
        elseif ($par6 == 2) $entered = substr($entered, 0, 3)."20000".substr($entered, 3, 3);
        elseif ($par6 == 3) $entered = substr($entered, 0, 4)."00000".substr($entered, 4, 2);
        elseif ($par6 == 4) $entered = substr($entered, 0, 5)."00000".substr($entered, 5, 1);
        else $entered = substr($entered, 0, 6)."0000".$par6;

        return $entered;
    }

    public function sanitizeUPC($entered)
    {
        // leading/trailing whitespace creates issues
        $entered = trim($entered);

        // leave GS1 barcodes alone otherwise
        if ($this->source == self::GS1_PREFIX) {
            return $entered;
        }

        /* exapnd UPC-E */
        if (substr($entered, 0, 1) == 0 && strlen($entered) == 7) {
            $entered = $this->expandUPCE($entered);
        }

        /* make sure upc length is 13 */
        $upc = "";
        if ($this->session->get('EanIncludeCheckDigits') != 1) {
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

        return $upc;
    }

    /* extract scale-sticker prices 
       Mixed check digit settings do not work here. 
       Scale UPCs and EANs must uniformly start w/
       002 or 02.
       @return [array] 
        boolean is-scale-sticker
        number UPC-price
        number EAN-price
    */
    private function rewriteScaleSticker($upc)
    {
        if ($upc == '0028491108110' || $upc == '0028491108310' || $upc == '0028491108010' || $upc == '0028491108210') {
            return array($upc, false, 0, 0);
        }
        $scalePrefix = '002';
        $scaleStickerItem = false;
        $scaleCheckDigits = false;
        if ($this->session->get('UpcIncludeCheckDigits') == 1) {
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
            $scalepriceUPC = MiscLib::truncate2(substr($upc, -4)/100);
            $scalepriceEAN = MiscLib::truncate2(substr($upc, -5)/100);
            if ($scaleCheckDigits) {
                $scalepriceUPC = MiscLib::truncate2(substr($upc, 8, 4)/100);
                $scalepriceEAN = MiscLib::truncate2(substr($upc, 7, 5)/100);
            }
            $rewriteClass = $this->session->get('VariableWeightReWriter');
            if ($rewriteClass != '' && class_exists('COREPOS\\pos\\lib\\Scanning\\VariableWeightReWrites\\' . $rewriteClass)) {
                $rewriteClass = 'COREPOS\\pos\\lib\\Scanning\\VariableWeightReWrites\\' . $rewriteClass;
            } elseif ($rewriteClass === '' || !class_exists($rewriteClass)) {
                $rewriteClass = 'COREPOS\\pos\\lib\\Scanning\\VariableWeightReWrites\\ZeroedPriceReWrite';
            }
            $rewriter = new $rewriteClass();
            $upc = $rewriter->translate($upc, $scaleCheckDigits);
            // I think this is WFC special casing; needs revising.
            if ($upc == "0020006000000" || $upc == "0020010000000") $scalepriceUPC *= -1;
        }

        return array($upc, $scaleStickerItem, $scalepriceUPC, $scalepriceEAN);
    }

    public static $requestInfoHeader = 'customer age';
    public static $requestInfoMsg = 'Type customer birthdate YYYYMMDD';
    public static function requestInfoCallback($info)
    {
        if ((is_numeric($info) && strlen($info)==8) || $info == 1){
            CoreLocal::set("memAge",$info);
            $inp = urlencode(CoreLocal::get('strEntered'));
            return MiscLib::baseURL() . 'gui-modules/pos2.php?reginput=' . $inp . '&repeat=1';
        }
        return False;
    }

    private function genericSecurity($ret)
    {
        $myUrl = MiscLib::baseURL();
        /* force cashiers to enter a comment on refunds */
        if ($this->session->get("refund")==1 && $this->session->get("refundComment") == ""){
            $ret['udpmsg'] = 'twoPairs';
            $ret['main_frame'] = $myUrl.'gui-modules/refundComment.php';
            if ($this->session->get("SecurityRefund") > 20){
                $ret['main_frame'] = $myUrl."gui-modules/adminlogin.php?class=COREPOS-pos-lib-adminlogin-RefundAdminLogin";
            }
            $this->session->set("refundComment",$this->session->get("strEntered"));
            return $ret;
        }
        if ($this->session->get('itemPD') > 0 && $this->session->get('SecurityLineItemDiscount') == 30 && $this->session->get('msgrepeat')==0){
            $ret['main_frame'] = $myUrl."gui-modules/adminlogin.php?class=COREPOS-pos-lib-adminlogin-LineItemDiscountAdminLogin";
            return $ret;
        }

        return $ret;
    }

    private function duplicateWeight($row, $scaleStickerItem)
    {
        if ($row['scale'] == 1 
            && $this->session->get("lastWeight") > 0 && $this->session->get("weight") > 0
            && abs($this->session->get("weight") - $this->session->get("lastWeight")) < 0.0005
            && !$scaleStickerItem && abs($row['normal_price']) > 0.01
            && $this->session->get('msgrepeat') == 0) {
            return true;
        }

        return false;
    }

    private function isDateRestricted($row)
    {
        $dbc = Database::pDataConnect();
        $restrictQ = "SELECT upc,dept_ID FROM dateRestrict WHERE
            ( upc='{$row['upc']}' AND
              ( ".$dbc->datediff($dbc->now(),'restrict_date')."=0 OR
                ".$dbc->dayofweek($dbc->now())."=restrict_dow
              ) AND
              ( (restrict_start IS NULL AND restrict_end IS NULL) OR
                ".$dbc->curtime()." BETWEEN restrict_start AND restrict_end
              )
             ) OR 
            ( dept_ID='{$row['department']}' AND
              ( ".$dbc->datediff($dbc->now(),'restrict_date')."=0 OR
                ".$dbc->dayofweek($dbc->now())."=restrict_dow
              ) AND
              ( (restrict_start IS NULL AND restrict_end IS NULL) OR
                ".$dbc->curtime()." BETWEEN restrict_start AND restrict_end
              )
            )";
        $restrictR = $dbc->query($restrictQ);
        return $dbc->numRows($restrictR) > 0 ? true : false;
    }

    private function nonProductUPCs($upc, $ret)
    {
        $dbc = Database::pDataConnect();
        $objs = is_array($this->session->get("SpecialUpcClasses")) ? $this->session->get('SpecialUpcClasses') : array();
        foreach($objs as $className){
            $instance = SpecialUPC::factory($className, $this->session);
            if ($instance->isSpecial($upc)){
                return $instance->handle($upc,$ret);
            }
        }

        // no match; not a product, not special
        if ($this->session->get('NoCompat') == 1 || $dbc->table_exists('IgnoredBarcodes')) {
            // lookup UPC in tabe of ignored barcodes
            // this just suppresses any error message from
            // coming back
            $query = 'SELECT upc FROM IgnoredBarcodes WHERE upc=\'' . $upc . "'";
            $result = $dbc->query($query);
            if ($result && $dbc->num_rows($result)) {
                return $this->default_json();
            }
        }
        
        $obj = ItemNotFound::factory($this->session->get('ItemNotFound'));
        $ret = $obj->handle($upc, $ret);

        return $ret;
    }

    private function lookupItem($upc)
    {
        $dbc = Database::pDataConnect();
        $query = "SELECT inUse,upc,description,normal_price,scale,deposit,
            qttyEnforced,department,local,tax,foodstamp,discount,
            discounttype,specialpricemethod,special_price,groupprice,
            pricemethod,quantity,specialgroupprice,specialquantity,
            mixmatchcode,idEnforced,tareweight,scaleprice";
        if ($this->session->get('NoCompat') == 1) {
            $query .= ', 
                line_item_discountable, 
                formatted_name, 
                special_limit,
                CASE 
                    WHEN received_cost <> 0 AND received_cost IS NOT NULL
                        THEN received_cost
                    WHEN discounttype > 0 AND special_cost <> 0 AND special_cost IS NOT NULL 
                        THEN special_cost 
                    ELSE cost END AS cost';
        } else {
            $table = $dbc->tableDefinition('products');
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
            // New column 20Oct16
            if (isset($table['special_cost']) && isset($table['received_cost'])) {
                $query .= ', CASE WHEN received_cost <> 0 AND received_cost IS NOT NULL THEN received_cost
                    WHEN discounttype > 0 AND special_cost <> 0 AND special_cost IS NOT NULL 
                    THEN special_cost ELSE cost END AS cost';
            } else {
                $query .= ', cost';
            }
        }
        $query .= " FROM products WHERE upc = '".$upc."'";
        $result = $dbc->query($query);
        $row = $dbc->fetchRow($result);

        return $row;
    }

    private function checkInUse($row)
    {
        /* Implementation of inUse flag
         *   if the flag is not set, display a warning dialog noting this
         *   and allowing the sale to be confirmed or canceled
         */
        if ($row["inUse"] == 0) {
            TransRecord::addLogRecord(array(
                'upc' => $row['upc'],
                'description' => $row['description'],
                'department' => $row['department'],
                'charflag' => 'IU',
            ));
        }
    }

    private function enforceSaleLimit($dbc, $row, $quantity)
    {
        /**
          Enforce per-transaction sale limits
        */
        if ($row['special_limit'] > 0) {
            $appliedQ = "
                SELECT SUM(quantity) AS saleQty
                FROM " . $this->session->get('tDatabase') . $dbc->sep() . "localtemptrans
                WHERE discounttype <> 0
                    AND (
                        upc='{$row['upc']}'
                        OR (mixMatch='{$row['mixmatchcode']}' AND mixMatch<>''
                            AND mixMatch<>'0' AND mixMatch IS NOT NULL)
                    )";
            $appliedR = $dbc->query($appliedQ);
            if ($appliedR && $dbc->num_rows($appliedR)) {
                $appliedW = $dbc->fetch_row($appliedR);
                if (($appliedW['saleQty']+$quantity) > $row['special_limit']) {
                    $row['discounttype'] = 0;
                    $row['special_price'] = 0;
                    $row['specialpricemethod'] = 0;
                    $row['specialquantity'] = 0;
                    $row['specialgroupprice'] = 0;
                }
            }
        }

        return $row;
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

