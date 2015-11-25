<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

class ItemPD extends Parser 
{
    function check($str)
    {
        if (substr($str,-2) == "PD" && is_numeric(substr($str,0,strlen($str)-2))) {
            return true;
        }
        return false;
    }

    function parse($str)
    {
        $ret = $this->default_json();
        if (CoreLocal::get("currentid") == 0) {
            $ret['output'] = DisplayLib::boxMsg(
                _("No Item on Order"),
                '',
                false,
                DisplayLib::standardClearButton()
            );
        } else {
            $str = CoreLocal::get("currentid");
            $item_pd = substr($str,0,strlen($str)-2);

            $ret['output'] = $this->discountitem($str,$item_pd);
        }
        if (empty($ret['output'])){
            $ret['output'] = DisplayPage::lastpage();
            $ret['redraw_footer'] = True;
            $ret['udpmsg'] = 'goodBeep';
        }
        else {
            $ret['udpmsg'] = 'errorBeep';
        }
        return $ret;
    }

    function discountitem($item_num,$item_pd) 
    {

        if ($item_num) {
            $query = "select upc, quantity, ItemQtty, foodstamp, total, voided, charflag from localtemptrans where "
                ."trans_id = ".$item_num;

            $dbc = Database::tDataConnect();
            $result = $dbc->query($query);
            $num_rows = $dbc->num_rows($result);

            if ($num_rows == 0) {
                return DisplayLib::boxMsg(
                    _("Item not found"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } else {
                $row = $dbc->fetch_array($result);

                if (!$row["upc"] || strlen($row["upc"]) < 1 || $row['charflag'] == 'SO') {
                    return DisplayLib::boxMsg(
                        _("Not a valid item"),
                        '',
                        false,
                        DisplayLib::standardClearButton()
                    );
                } else {
                    return $this->discountupc($row["ItemQtty"]."*".$row["upc"],$item_num,$item_pd);
                }
            }
        }
        return "";
    }    

    function discountupc($upc,$item_num=-1,$item_pd=0) 
    {

        $lastpageflag = 1;
        $deliflag = 0;
        $quantity = 0;

        if (strpos($upc, "*") && (strpos($upc, "**") || strpos($upc, "*") == 0 || 
            strpos($upc, "*") == strlen($upc)-1))
            $upc = "stop";

        elseif (strpos($upc, "*")) {

            $voidupc = explode("*", $upc);
            if (!is_numeric($voidupc[0])) $upc = "stop";
            else {
                $quantity = $voidupc[0];
                $upc = $voidupc[1];
                $weight = 0;

            }
        }
        elseif (!is_numeric($upc) && !strpos($upc, "DP")) $upc = "stop";
        else {
            $quantity = 1;
            $weight = CoreLocal::get("weight");
        }

        $scaleprice = 0;
        if (is_numeric($upc)) {
            $upc = substr("0000000000000".$upc, -13);
            if (substr($upc, 0, 3) == "002" && substr($upc, -5) != "00000") {
                $scaleprice = substr($upc, 10, 4)/100;
                $upc = substr($upc, 0, 8)."0000";
                $deliflag = 1;
            }
            elseif (substr($upc, 0, 3) == "002" && substr($upc, -5) == "00000") {
                $deliflag = 1;
            }
        }

        if ($upc == "stop") return DisplayLib::inputUnknown();

        $dbc = Database::tDataConnect();

        $query = "select sum(ItemQtty) as voidable, sum(quantity) as vquantity, max(scale) as scale, "
            ."max(volDiscType) as volDiscType from localtemptrans where upc = '".$upc
            ."' and unitPrice = ".$scaleprice." and discounttype <> 3 group by upc";

        $result = $dbc->query($query);
        $num_rows = $dbc->num_rows($result);
        if ($num_rows == 0 ){
            return DisplayLib::boxMsg(
                _("Item not found: ") . $upc,
                '',
                false,
                DisplayLib::standardClearButton()
            );
        }

        $row = $dbc->fetch_array($result);

        if (($row["scale"] == 1) && $weight > 0) {
            $quantity = $weight - CoreLocal::get("tare");
            CoreLocal::set("tare",0);
        }

        $volDiscType = $row["volDiscType"];
        $voidable = MiscLib::nullwrap($row["voidable"]);

        $VolSpecial = 0;
        $volume = 0;
        $scale = MiscLib::nullwrap($row["scale"]);

        //----------------------Void Item------------------
        $query_upc = "select ItemQtty,foodstamp,discounttype,mixMatch,cost,
            numflag,charflag,unitPrice,discounttype,regPrice,discount,
            memDiscount,discountable,description,trans_type,trans_subtype,
            department,tax,VolSpecial
            from localtemptrans where upc = '".$upc."' and unitPrice = "
             .$scaleprice." and trans_id=$item_num";

        $result = $dbc->query($query_upc);
        $row = $dbc->fetch_array($result);

        $ItemQtty = $row["ItemQtty"];
        $foodstamp = MiscLib::nullwrap($row["foodstamp"]);
        $discounttype = MiscLib::nullwrap($row["discounttype"]);
        $mixMatch = MiscLib::nullwrap($row["mixMatch"]);
        $cost = isset($row["cost"])?-1*$row["cost"]:0;
        $numflag = isset($row["numflag"])?$row["numflag"]:0;
        $charflag = isset($row["charflag"])?$row["charflag"]:0;
    
        $unitPrice = $row["unitPrice"];
        if ((CoreLocal::get("isMember") != 1 && $row["discounttype"] == 2) || 
            (CoreLocal::get("isStaff") == 0 && $row["discounttype"] == 4)) 
            $unitPrice = $row["regPrice"];
        elseif (((CoreLocal::get("isMember") == 1 && $row["discounttype"] == 2) || 
            (CoreLocal::get("isStaff") != 0 && $row["discounttype"] == 4)) && 
            ($row["unitPrice"] == $row["regPrice"])) {
            $dbc_p = Database::pDataConnect();
            $query_p = "select special_price from products where upc = '".$upc."'";
            $result_p = $dbc_p->query($query_p);
            $row_p = $dbc_p->fetch_array($result_p);
            
            $unitPrice = $row_p["special_price"];
        
        }
                
        $discount = -1 * $row["discount"];
        $memDiscount = -1 * $row["memDiscount"];
        $discountable = $row["discountable"];

        $CardNo = CoreLocal::get("memberID");
        
        $discounttype = MiscLib::nullwrap($row["discounttype"]);
        if ($discounttype == 3) 
            $quantity = -1 * $ItemQtty;

        elseif ($quantity != 0) {
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
                'quantity' => $quantity, 
                'volDiscType' => $volDiscType, 
                'volume' => $volume, 
                'VolSpecial' => $VolSpecial, 
                'mixMatch' => $mixMatch, 
                'voided' => 1, 
                'cost' => $cost, 
                'numflag' => $numflag, 
                'charflag' => $charflag
            ));

            if ($row["trans_type"] != "T") {
                CoreLocal::set("ttlflag",0);
            }

            $dbc = Database::pDataConnect();
            $chk = $dbc->query("SELECT deposit FROM products WHERE upc='$upc'");
            if ($dbc->num_rows($chk) > 0){
                $dpt = array_pop($dbc->fetch_row($chk));
                if ($dpt > 0){
                    $dupc = (int)$dpt;
                    return $this->voidupc((-1*$quantity)."*".$dupc,True);
                }
            }
        }
        return "";
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

