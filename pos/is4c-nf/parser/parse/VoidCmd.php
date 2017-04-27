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
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\adminlogin\AdminLoginInterface;
use COREPOS\pos\parser\Parser;
use \CoreLocal;
use \Exception;

class VoidCmd extends Parser implements AdminLoginInterface
{
    private $scaleprice = 0;

    public function check($str)
    {
        if (substr($str,0,2) == "VD" && strlen($str) <= 15) {
            return true;
        }

        return false;
    }

    public function parse($str)
    {
        $ret = $this->default_json();
    
        try {
            $this->checkVoidLimit(0);
            if (strlen($str) > 2) {
                $ret = $this->voidupc(substr($str,2), $ret);
            } elseif ($this->session->get("currentid") == 0) {
                $ret['output'] = DisplayLib::boxMsg(
                    _("No Item on Order"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } else {
                $transID = $this->session->get("currentid");

                $status = $this->checkstatus($transID);
                $this->scaleprice = $status['scaleprice'];

                $ret = $this->branchByVoided($status['voided'], $transID, $status['status'], $ret);
            }

            if (empty($ret['output']) && empty($ret['main_frame'])) {
                $ret['output'] = DisplayLib::lastpage();
                $ret['redraw_footer'] = true;
                $ret['udpmsg'] = 'goodBeep';
            } elseif (empty($ret['main_frame'])) {
                $ret['udpmsg'] = 'errorBeep';
            }
        } catch (Exception $ex) {
            $ret['main_frame'] = $ex->getMessage();
        }

        return $ret;
    }

    private function branchByVoided($voided, $transID, $status, $ret)
    {
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
        if ($voided == 2) {
            // void preceeding item
            $ret = $this->voiditem($transID - 1, $ret);
        } elseif ($voided == 3 || $voided == 6 || $voided == 8) {
            $ret['output'] = DisplayLib::boxMsg(
                _("Cannot void this entry"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
        } elseif ($voided == 4 || $voided == 5) {
            PrehLib::percentDiscount(0);
        } elseif ($voided == 10) {
            TransRecord::reverseTaxExempt();
        } elseif ($status == "V") {
            $ret['output'] = DisplayLib::boxMsg(
                _("Item already voided"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
        } else {
            $ret = $this->voiditem($transID, $ret);
        }

        return $ret;
    }

    /**
      Lookup item and decide whether to void
      by simply reversing the record or by
      applying product UPC
      @param $itemNum localtemptrans.trans_id value to void
      @param $json parser return value structure
    */
    public function voiditem($itemNum, $json)
    {
        if ($itemNum) {
            $row = $this->getLine($itemNum);

            if (!$row) {
                $json['output'] = DisplayLib::boxMsg(
                    _("Item not found"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif ($row['voided'] == 1 && $this->voidableNonUpc($row)) {
                $json['output'] = DisplayLib::boxMsg(
                    _("Item already voided"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif ($this->voidableNonUpc($row)) {
                $json = $this->voidid($itemNum, $json);
            } else {
                $json = $this->voidupc($row["ItemQtty"] . "*" . $row["upc"], $json, $itemNum);
            }
        } else {
            $json['output'] = DisplayLib::boxMsg(
                _("Item not found"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
        }

        return $json;
    }

    private function voidableNonUpc($row)
    {
        return (
            !$row["upc"] 
            || strlen($row["upc"]) < 1 
            || $row['trans_type'] === 'D'
            || $row['charflag'] === 'SO'
        );
    }

    private function getLine($itemNum)
    {
        $query = "select upc,VolSpecial,quantity,trans_subtype,unitPrice,
            discount,memDiscount,discountable,scale,numflag,charflag,
            foodstamp,discounttype,total,cost,description,trans_type,
            department,regPrice,tax,volDiscType,volume,mixMatch,matched,
            trans_status,ItemQtty,voided
                   from localtemptrans where trans_id = ".$itemNum;
        $dbc = Database::tDataConnect();
        $result = $dbc->query($query);
        return $dbc->fetchRow($result);
    }

    /**
      Void record by trans_id
      @param $itemNum [int] trans_id
      @param $json parser return value structure

      This marks the specified record as voided
      and adds an offsetting record also marked voided
      Neither record can be subsequently modified via
      voids.
    */
    public function voidid($itemNum, $json)
    {
        $row = $this->getLine($itemNum);

        $upc = $row["upc"];
        $volSpecial = $row["VolSpecial"];
        $quantity = -1 * $row["quantity"];
        $total = -1 * $row["total"];
        // 11Jun14 Andy => don't know why FS is different. legacy?
        if ($row["trans_subtype"] == "FS") {
            $total = -1 * $row["unitPrice"];
        } elseif ($row['trans_status'] == 'R' && $row['trans_type'] == 'D') {
            // set refund flag and let that logic reverse
            // the total and quantity
            $this->session->set('refund', 1);
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
        $mixmatch = $row['mixMatch'];
        $matched = $row['matched'];

        $foodstamp = 0;
        if ($row["foodstamp"] != 0) {
            $foodstamp = 1;
        }

        $discounttype = MiscLib::nullwrap($row["discounttype"]);

        try {
            if ($row['trans_type'] == 'D') {
                $this->checkVoidLimit($row['total']);
            }
            $this->checkTendered($total);
            $dbc = Database::tDataConnect();
            $update = "update localtemptrans set voided = 1 where trans_id = ".$itemNum;
            $dbc->query($update);

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
                'VolSpecial' => $volSpecial, 
                'mixMatch' => $mixmatch, 
                'matched' => $matched, 
                'voided' => 1, 
                'cost' => $cost, 
                'numflag' => $numflag, 
                'charflag' => $charflag
            ));

            if ($row["trans_type"] != "T") {
                $this->session->set("ttlflag",0);
            } else {
                PrehLib::ttl();
            }

        } catch (Exception $ex) {
            if ($ex->getCode() == 0) {
                $json['output'] = $ex->getMessage();
            } elseif ($ex->getCode() == 1) {
                $json['main_frame'] = $ex->getMessage();
            }
        }

        return $json;
    }

    private function upcQuantity($upc)
    {
        /**
          If UPC contains an asterisk, extract quantity
          and validate input. Otherwise use quantity 1.
        */
        if (strstr($upc, '*')) {
            list($quantity, $upc) = explode('*', $upc, 2);
            if ($quantity === '' || $upc === '' || !is_numeric($quantity) || !is_numeric($upc)) {
                throw new Exception(DisplayLib::inputUnknown());
            }
            $weight = 0;
        } else {
            $quantity = 1;
            $weight = $this->session->get("weight");
        }
        
        return array($upc, $quantity, $weight);
    }

    private function scaleUPC($upc)
    {
        $scaleprice = 0;
        $deliflag = false;
        if (is_numeric($upc)) {
            $upc = substr("0000000000000" . $upc, -13);
            if (substr($upc, 0, 3) == "002" && substr($upc, -5) != "00000") {
                $scaleprice = substr($upc, 10, 4)/100;
                $upc = substr($upc, 0, 8) . "0000";
                $deliflag = true;
            } elseif (substr($upc, 0, 3) == "002" && substr($upc, -5) == "00000") {
                $scaleprice = $this->scaleprice;
                $deliflag = true;
            }
        }

        return array($upc, $scaleprice, $deliflag);
    }

    private function findUPC($upc, $deliflag, $scaleprice)
    {
        $dbc = Database::tDataConnect();

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

        $result = $dbc->query($query);
        $numRows = $dbc->numRows($result);
        if ($numRows == 0 ) {
            throw new Exception(DisplayLib::boxMsg(
                _("Item not found: ") . $upc,
                '',
                false,
                DisplayLib::standardClearButton()
            ));
        }

        return $dbc->fetchRow($result);
    }

    private function checkUpcQuantities($voidable, $quantity, $scale)
    {
        if ($voidable == 0 && $quantity == 1) {
            throw new Exception(DisplayLib::boxMsg(
                _("Item already voided"),
                '',
                false,
                DisplayLib::standardClearButton()
            ));
        } elseif ($voidable == 0 && $quantity > 1) {
            throw new Exception(DisplayLib::boxMsg(
                _("Items already voided"),
                '',
                false,
                DisplayLib::standardClearButton()
            ));
        } elseif ($scale == 1 && $quantity < 0) {
            throw new Exception(DisplayLib::boxMsg(
                _("tare weight cannot be greater than item weight"),
                '',
                false,
                DisplayLib::standardClearButton()
            ));
        } elseif ($voidable < $quantity && $scale == 1) {
            $message = _("Void request exceeds")."<br />"._("weight of item rung in")."<p><b>".
                sprintf(_("You can void up to %.2f lb"),$row['voidable'])."</b>";
            throw new Exception(DisplayLib::boxMsg($message, '', false, DisplayLib::standardClearButton()));
        } elseif ($voidable < $quantity) {
            $message = _("Void request exceeds")."<br />"._("number of items rung in")."<p><b>".
                sprintf(_("You can void up to %d"),$row['voidable'])."</b>";
            throw new Exception(DisplayLib::boxMsg($message, '', false, DisplayLib::standardClearButton()));
        }

        return true;
    }

    private function findUpcLine($upc, $scaleprice, $deliflag, $itemNum)
    {
        $dbc = Database::tDataConnect();
        //----------------------Void Item------------------
        $queryUPC = "SELECT 
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
            $queryUPC .= ' AND unitPrice = ' . $scaleprice;
        }
        if ($itemNum != -1) {
            $queryUPC .= ' AND trans_id = ' . $itemNum . ' AND voided=0 ';
        } else {
            $queryUPC .= ' AND voided=0 ORDER BY total';
        }

        $result = $dbc->query($queryUPC);
        if ($dbc->numRows($result) > 0) {
            return $dbc->fetchRow($result);
        }

        return $this->findUpcLine($upc, $scaleprice, $deliflag, -1);
    }

    private function adjustUnitPrice($upc, $row)
    {
        $unitPrice = $row['unitPrice'];
        /**
          11Jun14 Andy
          Convert unitPrice to/from sale price based on
          member status. I'm not sure this is actually
          necessary.
        */
        if (($this->session->get("isMember") != 1 && $row["discounttype"] == 2) || 
            ($this->session->get("isStaff") == 0 && $row["discounttype"] == 4)) {
            $unitPrice = $row["regPrice"];
        } elseif ((($this->session->get("isMember") == 1 && $row["discounttype"] == 2) || 
            ($this->session->get("isStaff") != 0 && $row["discounttype"] == 4)) && 
            ($row["unitPrice"] == $row["regPrice"])) {
            $dbcp = Database::pDataConnect();
            $queryp = "select special_price from products where upc = '".$upc."'";
            $resultp = $dbcp->query($queryp);
            $rowp = $dbcp->fetchRow($resultp);
            
            $unitPrice = $rowp["special_price"];
        }

        return $unitPrice;
    }

    private function checkVoidLimit($total)
    {
        /**
          Check if the voiding item will exceed the limit. If so,
          prompt for admin password. 
        */
        if (is_numeric($this->session->get('VoidLimit')) && $this->session->get('VoidLimit') > 0) {
            $currentTotal = $this->session->get('voidTotal');
            if ($currentTotal + (-1*$total) > $this->session->get('VoidLimit') && $this->session->get('voidOverride') != 1) {
                $this->session->set('strRemembered', $this->session->get('strEntered'));
                $this->session->set('voidOverride', 0);
                throw new Exception(MiscLib::baseUL().'gui-modules/adminlogin.php?class=COREPOS-pos-parser-parse-Void', 1);
            }
        }
    }

    private function checkTendered($total)
    {
        if ($this->session->get("tenderTotal") < 0 && (-1 * $total) > $this->session->get("runningTotal") - $this->session->get("taxTotal")) {
            $dbc = Database::tDataConnect();
            $cash = $dbc->query("SELECT total FROM localtemptrans WHERE trans_subtype='CA' AND total <> 0");
            if ($dbc->numRows($cash) > 0) {
                throw new Exception(DisplayLib::boxMsg(
                    _("Item already paid for"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                ));
            }
        }
    }

    /**
      Void the given UPC
      @param $upc [string] upc to void. Optionally including quantity and asterisk
      @param $itemNum [int] trans_id of record to void. Optional.
      @param $json parser return value structure
    */
    public function voidupc($upc, $json, $itemNum=-1)
    {
        $deliflag = false;
        $quantity = 0;

        try {
            list($upc, $quantity, $weight) = $this->upcQuantity($upc);
            list($upc, $scaleprice, $deliflag) = $this->scaleUPC($upc);
            $row = $this->findUPC($upc, $deliflag, $scaleprice);

            if (($row["scale"] == 1) && $weight > 0) {
                $quantity = $weight - $this->session->get("tare");
                $this->session->set("tare", 0);
            }
            $volDiscType = $row["volDiscType"];
            $voidable = MiscLib::nullwrap($row["voidable"]);
            $volSpecial = 0;
            $volume = 0;
            $scale = MiscLib::nullwrap($row["scale"]);
            $this->checkUpcQuantities($voidable, $quantity, $scale);

            $row = $this->findUpcLine($upc, $scaleprice, $deliflag, $itemNum);
            // if the selected line was already voided, findUpcLine() might locate
            // a different, equivalent line to proceed with
            $itemNum = $row['trans_id'];
            $foodstamp = MiscLib::nullwrap($row["foodstamp"]);
            $discounttype = MiscLib::nullwrap($row["discounttype"]);
            $mixMatch = MiscLib::nullwrap($row["mixMatch"]);
            $matched = -1 * $row['matched'];
            $cost = -1* $row['cost'];
            $numflag = $row['numflag'];
            $charflag = $row['charflag'];
            $unitPrice = $this->adjustUnitPrice($upc, $row);
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
            $this->checkVoidLimit($total);
            $this->checkTendered($total);

            if ($quantity != 0) {
                $dbc = Database::tDataConnect();

                $update = "update localtemptrans set voided = 1 where trans_id = ".$itemNum;
                $dbc->query($update);

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
                    'VolSpecial' => $volSpecial, 
                    'mixMatch' => $mixMatch, 
                    'matched' => $matched,
                    'voided' => 1, 
                    'cost' => $cost, 
                    'numflag' => $numflag, 
                    'charflag' => $charflag
                ));

                if ($row["trans_type"] != "T") {
                    $this->session->set("ttlflag",0);
                }

                $this->voidDeposit($upc, $quantity);
            }
        } catch (Exception $ex) {
            if ($ex->getCode() == 0) {
                $json['output'] = $ex->getMessage();
            } elseif ($ex->getCode() == 1) {
                $json['main_frame'] = $ex->getMessage();
            }
        }

        return $json;
    }

    private function voidDeposit($upc, $quantity)
    {
        $dbc = Database::pDataConnect();
        $chk = $dbc->query("SELECT deposit FROM products WHERE upc='$upc'");
        if ($dbc->numRows($chk) > 0) {
            $row = $dbc->fetch_row($chk);
            $dpt = $row['deposit'];
            if ($dpt <= 0) {
                return false; // no deposit found
            }
            $dbc = Database::tDataConnect();
            $dupc = str_pad((int)$dpt,13,'0',STR_PAD_LEFT);
            $transID = $dbc->query(sprintf("SELECT trans_id FROM localtemptrans
                WHERE upc='%s' AND voided=0 AND quantity=%d",
                $dupc,(-1*$quantity)));
            if ($dbc->numRows($transID) > 0) {
                $row = $dbc->fetchRow($transID);
                $transID = $row['trans_id'];
                // pass an empty array instead of $json so
                // voiding the deposit doesn't result in an error
                // message. 
                $this->voidupc((-1*$quantity)."*".$dupc, array(), $transID);

                return true;
            }
        }

        return false;
    }

    public static function messageAndLevel()
    {
        return array(_('Void Limit Exceeded. Login to Continue'), 30);
    }

    public static function adminLoginCallback($success)
    {
        if ($success){
            CoreLocal::set('voidOverride', 1);
            CoreLocal::set('msgrepeat', 1);
            return true;
        }
        CoreLocal::set('voidOverride', 0);

        return false;
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

    /**
      Check if an item is voided or a refund
      @param $num item trans_id in localtemptrans
      @return array of status information with keys:
       - voided (int)
       - scaleprice (numeric)
       - refund (boolean)
       - status (string)
    */
    private function checkstatus($num) 
    {
        $ret = array(
            'voided' => 0,
            'scaleprice' => 0,
            'refund' => false,
            'status' => ''
        );

        $query = "select voided,unitPrice,discountable,
            discounttype,trans_status
            from localtemptrans where trans_id = ".((int)$num);

        $dbc = Database::tDataConnect();
        $result = $dbc->query($query);

        if ($row = $dbc->fetchRow($result)) {

            $ret['voided'] = $row['voided'];
            $ret['scaleprice'] = $row['unitPrice'];

            if ($row["trans_status"] == "V") {
                $ret['status'] = 'V';
            } elseif ($row["trans_status"] == "R") {
                $this->session->set("refund",1);
                $this->session->set("autoReprint",1);
                $ret['status'] = 'R';
                $ret['refund'] = true;
            }
        }
        
        return $ret;
    }
}

