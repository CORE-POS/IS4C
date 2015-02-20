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

class Void extends Parser 
{
    private $discounttype = 0;
    private $discountable = 0;
    private $caseprice = 0;
    private $scaleprice = 0;

    public function check($str)
    {
        if (substr($str,0,2) == "VD" && strlen($str) <= 15) {
            return true;
        } else {
            return false;
        }
    }

    public function parse($str)
    {
        $ret = $this->default_json();
    
        if (is_numeric(CoreLocal::get('VoidLimit')) && CoreLocal::get('VoidLimit') > 0){
            Database::getsubtotals();
            if (CoreLocal::get('voidTotal') > CoreLocal::get('VoidLimit') && CoreLocal::get('voidOverride') != 1) {
                CoreLocal::set('strRemembered', CoreLocal::get('strEntered'));
                CoreLocal::set('voidOverride', 0);
                $ret['main_frame'] = MiscLib::base_url().'gui-modules/adminlogin.php?class=Void';
                return $ret;
            }
        }


        if (strlen($str) > 2) {
            $ret = $this->voidupc(substr($str,2), $ret);
        } elseif (CoreLocal::get("currentid") == 0) {
            $ret['output'] = DisplayLib::boxMsg(
                _("No Item on Order"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
        } else {
            $id = CoreLocal::get("currentid");

            $status = PrehLib::checkstatus($id);
            $this->discounttype = $status['discounttype'];
            $this->discountable = $status['discountable'];
            $this->caseprice = $status['caseprice'];
            $this->scaleprice = $status['scaleprice'];

            /**
              Voided values:
                2 => "you saved" line
                3 => subtotal line
                4 => discount notice
                5 => % Discount line
                6 => tare weight, case disc notice,
                8 => FS change, regular change
                10 => tax exempt
            */
            if ($status['voided'] == 2) {
                // void preceeding item
                $ret = $this->voiditem($id - 1, $ret);
            } else if ($status['voided'] == 3 || $status['voided'] == 6 || $status['voided'] == 8) {
                $ret['output'] = DisplayLib::boxMsg(
                    _("Cannot void this entry"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } else if ($status['voided'] == 4 || $status['voided'] == 5) {
                PrehLib::percentDiscount(0);
            } else if ($status['voided'] == 10) {
                TransRecord::reverseTaxExempt();
            } else if ($status['status'] == "V") {
                $ret['output'] = DisplayLib::boxMsg(
                    _("Item already voided"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } else {
                $ret = $this->voiditem($id, $ret);
            }
        }

        if (empty($ret['output']) && empty($ret['main_frame'])) {
            $ret['output'] = DisplayLib::lastpage();
            $ret['redraw_footer'] = true;
            $ret['udpmsg'] = 'goodBeep';
        } elseif (empty($ret['main_frame'])) {
            $ret['udpmsg'] = 'errorBeep';
        }

        return $ret;
    }

    /**
      Lookup item and decide whether to void
      by simply reversing the record or by
      applying product UPC
      @param $item_num localtemptrans.trans_id value to void
      @param $json parser return value structure
    */
    public function voiditem($item_num, $json)
    {
        if ($item_num) {
            $query = "SELECT upc, 
                        quantity, 
                        ItemQtty, 
                        foodstamp, 
                        discountable,
                        total, 
                        voided, 
                        charflag, 
                        discounttype,
                        trans_type
                      FROM localtemptrans 
                      WHERE
                        trans_id = " . ((int)$item_num);

            $db = Database::tDataConnect();
            $result = $db->query($query);
            $num_rows = $db->num_rows($result);

            if ($num_rows == 0) {
                $json['output'] = DisplayLib::boxMsg(
                    _("Item not found"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
                return $json;
            } else {
                $row = $db->fetch_array($result);

                $this->discounttype = $row['discounttype'];
                $this->discountable = $row['discountable'];

                if ($row['voided'] == 1 &&
                     (!$row["upc"] || strlen($row["upc"]) < 1 
                     || $row['trans_type'] == 'D' 
                     || $row['charflag'] == 'SO')
                    ) {
                    $json['output'] = DisplayLib::boxMsg(
                        _("Item already voided"),
                        '',
                        false,
                        DisplayLib::standardClearButton()
                    );
                    return $json;
                } elseif (!$row["upc"] || strlen($row["upc"]) < 1 
                           || $row['trans_type'] == 'D'
                           || $row['charflag'] == 'SO') {
                    $json = $this->voidid($item_num, $json);
                    return $json;
                } else {
                    $json = $this->voidupc($row["ItemQtty"] . "*" . $row["upc"], $json, $item_num);
                    return $json;
                }
            }
        } else {
            $json['output'] = DisplayLib::boxMsg(
                _("Item not found"),
                '',
                false,
                DisplayLib::standardClearButton()
            );

            return $json;
        }
    }

    /**
      Void record by trans_id
      @param $item_num [int] trans_id
      @param $json parser return value structure

      This marks the specified record as voided
      and adds an offsetting record also marked voided
      Neither record can be subsequently modified via
      voids.
    */
    public function voidid($item_num, $json)
    {
        $query = "select upc,VolSpecial,quantity,trans_subtype,unitPrice,
            discount,memDiscount,discountable,scale,numflag,charflag,
            foodstamp,discounttype,total,cost,description,trans_type,
            department,regPrice,tax,volDiscType,volume,mixMatch,matched,
            trans_status
                   from localtemptrans where trans_id = ".$item_num;
        $db = Database::tDataConnect();
        $result = $db->query($query);
        $row = $db->fetch_array($result);

        $upc = $row["upc"];
        $VolSpecial = $row["VolSpecial"];
        $quantity = -1 * $row["quantity"];
        $total = -1 * $row["total"];
        // 11Jun14 Andy => don't know why FS is different. legacy?
        if ($row["trans_subtype"] == "FS") {
            $total = -1 * $row["unitPrice"];
        } elseif ($row['trans_status'] == 'R' && $row['trans_type'] == 'D') {
            // set refund flag and let that logic reverse
            // the total and quantity
            CoreLocal::set('refund', 1);
            $total = $row['total'];
            $quantity = $row['quantity'];
        }
        $discount = -1 * $row["discount"];
        $memDiscount = -1 * $row["memDiscount"];
        $discountable = $row["discountable"];
        $unitPrice = $row["unitPrice"];
        $scale = MiscLib::nullwrap($row["scale"]);
        $cost = -1 * $row['cost'];
        $numflag = $row["numflag"];
        $charflag = $row["charflag"];
        $mm = $row['mixMatch'];
        $matched = $row['matched'];

        $foodstamp = 0;
        if ($row["foodstamp"] != 0) {
            $foodstamp = 1;
        }

        $discounttype = MiscLib::nullwrap($row["discounttype"]);

        /**
          Check if the voiding item will exceed the limit. If so,
          prompt for admin password. For baffling reasons, the
          void amount $row['total'] is postive on open rings
        */
        if ($row['trans_type'] == 'D' && is_numeric(CoreLocal::get('VoidLimit')) && CoreLocal::get('VoidLimit') > 0) {
            $currentTotal = CoreLocal::get('voidTotal');
            if ($currentTotal + $row['total'] > CoreLocal::get('VoidLimit') && CoreLocal::get('voidOverride') != 1) {
                CoreLocal::set('strRemembered', CoreLocal::get('strEntered'));
                CoreLocal::set('voidOverride', 0);
                $json['main_frame'] = MiscLib::base_url().'gui-modules/adminlogin.php?class=Void';

                return $json;
            }
        }

        /**
          tenderTotal => tenders have been applied
          Amount to be voided is greater than remaining balance of
          the transaction. Restrict voids if cash is involved.
        */
        if (CoreLocal::get("tenderTotal") < 0 && (-1 * $total) > CoreLocal::get("runningTotal") - CoreLocal::get("taxTotal")) {
            $cash = $db->query("SELECT total FROM localtemptrans WHERE trans_subtype='CA' AND total <> 0");
            if ($db->num_rows($cash) > 0) {
                $json['output'] = DisplayLib::boxMsg(
                    _("Item already paid for"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
                return $json;
            }
        }

        $update = "update localtemptrans set voided = 1 where trans_id = ".$item_num;
        $db->query($update);

        TransRecord::addRecord(array(
            'upc' => $upc, 
            'description' => $row["description"], 
            'trans_type' => $row["trans_type"], 
            'trans_subtype' => $row["trans_subtype"], 
            'trans_status' => "V", 
            'department' => $row["department"], 
            'quantity' => $quantity, 
            'unitPrice' => $unitPrice, 
            'total' => $total, 
            'regPrice' => $row["regPrice"], 
            'scale' => $scale, 
            'tax' => $row["tax"], 
            'foodstamp' => $foodstamp, 
            'discount' => $discount, 
            'memDiscount' => $memDiscount, 
            'discountable' => $discountable, 
            'discounttype' => $discounttype, 
            'ItemQtty' => $quantity, 
            'volDiscType' => $row["volDiscType"], 
            'volume' => $row["volume"], 
            'VolSpecial' => $VolSpecial, 
            'mixMatch' => $mm, 
            'matched' => $matched, 
            'voided' => 1, 
            'cost' => $cost, 
            'numflag' => $numflag, 
            'charflag' => $charflag
        ));

        if ($row["trans_type"] != "T") {
            CoreLocal::set("ttlflag",0);
        } else {
            PrehLib::ttl();
        }

        return $json;
    }

    /**
      Void the given UPC
      @param $upc [string] upc to void. Optionally including quantity and asterisk
      @param $item_num [int] trans_id of record to void. Optional.
      @param $json parser return value structure
    */
    public function voidupc($upc, $json, $item_num=-1)
    {
        $lastpageflag = 1;
        $deliflag = false;
        $quantity = 0;

        /**
          If UPC contains an asterisk, extract quantity
          and validate input. Otherwise use quantity 1.
        */
        if (strstr($upc, '*')) {
            list($quantity, $upc) = explode('*', $upc, 2);
            if ($quantity === '' || $upc === '' || !is_numeric($quantity) || !is_numeric($upc)) {
                $json['output'] = DisplayLib::inputUnknown();
                return $json;
            } else {
                $weight = 0;
            }
        } else {
            $quantity = 1;
            $weight = CoreLocal::get("weight");
        }

        $scaleprice = 0;
        if (is_numeric($upc)) {
            $upc = substr("0000000000000" . $upc, -13);
            if (substr($upc, 0, 3) == "002" && substr($upc, -5) != "00000") {
                $scaleprice = substr($upc, 10, 4)/100;
                $upc = substr($upc, 0, 8) . "0000";
                $deliflag = true;
            } else if (substr($upc, 0, 3) == "002" && substr($upc, -5) == "00000") {
                $scaleprice = $this->scaleprice;
                $deliflag = true;
            }
        }

        $db = Database::tDataConnect();

        $query = "SELECT SUM(ItemQtty) AS voidable, 
                    SUM(quantity) AS vquantity,
                    MAX(scale) AS scale,
                    MAX(volDiscType) AS volDiscType 
                  FROM localtemptrans 
                  WHERE upc = '" . $upc . "'";
        if ($deliflag) {
            $query .= ' AND unitPrice = ' . $scaleprice;
        }
        $query .= ' GROUP BY upc';

        $result = $db->query($query);
        $num_rows = $db->num_rows($result);
        if ($num_rows == 0 ) {
            $json['outpt'] = DisplayLib::boxMsg(
                _("Item not found: ") . $upc,
                '',
                false,
                DisplayLib::standardClearButton()
            );
            return $json;
        }

        $row = $db->fetch_array($result);

        if (($row["scale"] == 1) && $weight > 0) {
            $quantity = $weight - CoreLocal::get("tare");
            CoreLocal::set("tare", 0);
        }

        $volDiscType = $row["volDiscType"];
        $voidable = MiscLib::nullwrap($row["voidable"]);

        $VolSpecial = 0;
        $volume = 0;
        $scale = MiscLib::nullwrap($row["scale"]);

        if ($voidable == 0 && $quantity == 1) {
            $json['output'] = DisplayLib::boxMsg(
                _("Item already voided"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
            return $json;
        } elseif ($voidable == 0 && $quantity > 1) {
            $json['output'] = DisplayLib::boxMsg(
                _("Items already voided"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
            return $json;
        } elseif ($scale == 1 && $quantity < 0) {
            $json['output'] = DisplayLib::boxMsg(
                _("tare weight cannot be greater than item weight"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
            return $json;
        } elseif ($voidable < $quantity && $row["scale"] == 1) {
            $message = _("Void request exceeds")."<br />"._("weight of item rung in")."<p><b>".
                sprintf(_("You can void up to %.2f lb"),$row['voidable'])."</b>";
            $json['output'] = DisplayLib::boxMsg($message, '', false, DisplayLib::standardClearButton());
            return $json;
        } elseif ($voidable < $quantity) {
            $message = _("Void request exceeds")."<br />"._("number of items rung in")."<p><b>".
                sprintf(_("You can void up to %d"),$row['voidable'])."</b>";
            $json['output'] = DisplayLib::boxMsg($message, '', false, DisplayLib::standardClearButton());
            return $json;
        }

        //----------------------Void Item------------------
        $query_upc = "SELECT 
                        ItemQtty,
                        foodstamp,
                        discounttype,
                        mixMatch,
                        cost,
                        numflag,
                        charflag,
                        unitPrice,
                        total,
                        discounttype,
                        regPrice,
                        discount,
                        memDiscount,
                        discountable,
                        description,
                        trans_type,
                        trans_subtype,
                        department,
                        tax,
                        VolSpecial,
                        matched,
                        scale,
                        trans_id
                      FROM localtemptrans 
                      WHERE upc = '" . $upc . "'"; 
        if ($deliflag) {
            $query_upc .= ' AND unitPrice = ' . $scaleprice;
        }
        if ($item_num != -1) {
            $query_upc .= ' AND trans_id = ' . $item_num;
        } else {
            $query_upc .= ' AND voided=0 ORDER BY total';
        }

        $result = $db->query($query_upc);
        $row = $db->fetch_array($result);

        $foodstamp = MiscLib::nullwrap($row["foodstamp"]);
        $discounttype = MiscLib::nullwrap($row["discounttype"]);
        $mixMatch = MiscLib::nullwrap($row["mixMatch"]);
        $matched = -1 * $row['matched'];
        $item_num = $row['trans_id'];
        $cost = $row['cost'];
        $numflag = $row['numflag'];
        $charflag = $row['charflag'];
    
        $unitPrice = $row["unitPrice"];
        /**
          11Jun14 Andy
          Convert unitPrice to/from sale price based on
          member status. I'm not sure this is actually
          necessary.
        */
        if ((CoreLocal::get("isMember") != 1 && $row["discounttype"] == 2) || 
            (CoreLocal::get("isStaff") == 0 && $row["discounttype"] == 4)) {
            $unitPrice = $row["regPrice"];
        } elseif (((CoreLocal::get("isMember") == 1 && $row["discounttype"] == 2) || 
            (CoreLocal::get("isStaff") != 0 && $row["discounttype"] == 4)) && 
            ($row["unitPrice"] == $row["regPrice"])) {
            $db_p = Database::pDataConnect();
            $query_p = "select special_price from products where upc = '".$upc."'";
            $result_p = $db_p->query($query_p);
            $row_p = $db_p->fetch_array($result_p);
            
            $unitPrice = $row_p["special_price"];
        }
                
        $discount = -1 * $row["discount"];
        $memDiscount = -1 * $row["memDiscount"];
        $discountable = $row["discountable"];
        $quantity = -1 * $quantity;
        $total = $quantity * $unitPrice;
        if ($row['unitPrice'] == 0) {
            $total = $quantity * $row['total'];
        } elseif ($row['total'] != $total && $row['scale'] == 1) {
            /**
              If the total does not match quantity times unit price,
              the cashier probably manually specified a quantity
              i.e., VD{qty}*{upc}. This is probably OK for non-weight
              items. Each record should be the same and voiding multiple
              in one line will usually be fine.
            */
            $total = -1*$row['total'];
        }

        /**
          Check if the voiding item will exceed the limit. If so,
          prompt for admin password. 
        */
        if (is_numeric(CoreLocal::get('VoidLimit')) && CoreLocal::get('VoidLimit') > 0) {
            $currentTotal = CoreLocal::get('voidTotal');
            if ($currentTotal + (-1*$total) > CoreLocal::get('VoidLimit') && CoreLocal::get('voidOverride') != 1) {
                CoreLocal::set('strRemembered', CoreLocal::get('strEntered'));
                CoreLocal::set('voidOverride', 0);
                $json['main_frame'] = MiscLib::base_url().'gui-modules/adminlogin.php?class=Void';

                return $json;
            }
        }
    
        $db = Database::tDataConnect();
        if (CoreLocal::get("tenderTotal") < 0 && (-1 * $total) > CoreLocal::get("runningTotal") - CoreLocal::get("taxTotal")) {
            $cash = $db->query("SELECT total FROM localtemptrans WHERE trans_subtype='CA' AND total <> 0");
            if ($db->num_rows($cash) > 0) {
                $json['output'] = DisplayLib::boxMsg(
                    _("Item already paid for"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
                return $json;
            }
        }
        if ($quantity != 0) {

            $update = "update localtemptrans set voided = 1 where trans_id = ".$item_num;
            $db->query($update);

            TransRecord::addRecord(array(
                'upc' => $upc, 
                'description' => $row["description"], 
                'trans_type' => $row["trans_type"], 
                'trans_subtype' => $row["trans_subtype"], 
                'trans_status' => "V", 
                'department' => $row["department"], 
                'quantity' => $quantity, 
                'unitPrice' => $unitPrice, 
                'total' => $total, 
                'regPrice' => $row["regPrice"], 
                'scale' => $scale, 
                'tax' => $row["tax"], 
                'foodstamp' => $foodstamp, 
                'discount' => $discount, 
                'memDiscount' => $memDiscount, 
                'discountable' => $discountable, 
                'discounttype' => $discounttype, 
                'ItemQtty' => $quantity, 
                'volDiscType' => $volDiscType,
                'volume' => $volume,
                'VolSpecial' => $VolSpecial, 
                'mixMatch' => $mixMatch, 
                'matched' => $matched,
                'voided' => 1, 
                'cost' => $cost, 
                'numflag' => $numflag, 
                'charflag' => $charflag
            ));

            if ($row["trans_type"] != "T") {
                CoreLocal::set("ttlflag",0);
            }

            $db = Database::pDataConnect();
            $chk = $db->query("SELECT deposit FROM products WHERE upc='$upc'");
            if ($db->num_rows($chk) > 0) {
                $w = $db->fetch_row($chk);
                $dpt = $w['deposit'];
                if ($dpt <= 0) {
                    return $json; // no deposit found
                }
                $db = Database::tDataConnect();
                $dupc = str_pad((int)$dpt,13,'0',STR_PAD_LEFT);
                $id = $db->query(sprintf("SELECT trans_id FROM localtemptrans
                    WHERE upc='%s' AND voided=0 AND quantity=%d",
                    $dupc,(-1*$quantity)));
                if ($db->num_rows($id) > 0) {
                    $w = $db->fetch_row($id);
                    $trans_id = $w['trans_id'];
                    // pass an empty array instead of $json so
                    // voiding the deposit doesn't result in an error
                    // message. 
                    $this->voidupc((-1*$quantity)."*".$dupc, array(), $trans_id);
                }
            }
        }

        return $json;
    }

    public static $adminLoginMsg = 'Void Limit Exceeded. Login to continue.';
    
    public static $adminLoginLevel = 30;

    public static function adminLoginCallback($success)
    {
        if ($success){
            CoreLocal::set('voidOverride', 1);
            CoreLocal::set('msgrepeat', 1);
            return true;
        } else {
            CoreLocal::set('voidOverride', 0);
            return false;
        }
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>VD<i>ringable</i></td>
                <td>Void <i>ringable</i>, which
                may be a product number or an
                open department ring</td>
            </tr>
            </table>";
    }
}


?>
