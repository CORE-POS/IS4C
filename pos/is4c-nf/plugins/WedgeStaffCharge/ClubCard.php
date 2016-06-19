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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\parser\Parser;

class ClubCard extends Parser 
{
    function check($str)
    {
        if ($str == "50JC") {
            return true;
        }
        return false;
    }

    function parse($str)
    {
        $ret = $this->default_json();
        $query = "select upc,description,VolSpecial,quantity,
            total,discount,memDiscount,discountable,
            unitPrice,scale,foodstamp,voided,discounttype,
            trans_type,trans_status,department,regPrice,
            tax,volume,volDiscType
            from localtemptrans where 
            trans_id = " . CoreLocal::get("currentid");    
        $connection = Database::tDataConnect();
        $result = $connection->query($query);
        $num_rows = $connection->num_rows($result);

        if ($num_rows > 0) {
            $row = $connection->fetchRow($result);
            $strUPC = $row["upc"];
            $strDescription = $row["description"];
            $dblVolSpecial = $row["VolSpecial"];            
            $dblquantity = -0.5 * $row["quantity"];

            $dblTotal = MiscLib::truncate2(-1 * 0.5 * $row["total"]);         // invoked truncate2 rounding function to fix half-penny errors apbw 3/7/05

            $strCardNo = CoreLocal::get("memberID");
            $dblDiscount = $row["discount"];
            $dblmemDiscount = $row["memDiscount"];
            $intDiscountable = $row["discountable"];
            $dblUnitPrice = $row["unitPrice"];
            $intScale = MiscLib::nullwrap($row["scale"]);

            if ($row["foodstamp"] <> 0) {
                $intFoodStamp = 1;
            } else {
                $intFoodStamp = 0;
            }

            $intdiscounttype = MiscLib::nullwrap($row["discounttype"]);

            if ($row["voided"] == 20) {
                $ret['output'] = DisplayLib::boxMsg(
                    _("Discount already taken"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif ($row["trans_type"] == "T" or $row["trans_status"] == "D" or $row["trans_status"] == "V" or $row["trans_status"] == "C") {
                $ret['output'] = DisplayLib::boxMsg(
                    _("Item cannot be discounted"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif (strncasecmp($strDescription, "Club Card", 9) == 0 ) {        //----- edited by abpw 2/15/05 -----
                $ret['output'] = DisplayLib::boxMsg(
                    _("Item cannot be discounted"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif (CoreLocal::get("tenderTotal") < 0 and $intFoodStamp == 1 and (-1 * $dblTotal) > CoreLocal::get("fsEligible")) {
                $ret['output'] = DisplayLib::boxMsg(
                    _("Item already paid for"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } elseif (CoreLocal::get("tenderTotal") < 0 and (-1 * $dblTotal) > (CoreLocal::get("runningTotal") - CoreLocal::get("taxTotal"))) {
                $ret['output'] = DisplayLib::boxMsg(
                    _("Item already paid for"),
                    '',
                    false,
                    DisplayLib::standardClearButton()
                );
            } 
            else {
                // --- added partial item desc to club card description - apbw 2/15/05 --- 
                TransRecord::addRecord(array(
                    'upc' => $strUPC, 
                    'description' => "Club Card: " . substr($strDescription, 0, 19), 
                    'trans_type' => "I", 
                    'trans_status' => "J", 
                    'department' => $row["department"], 
                    'quantity' => $dblquantity, 
                    'unitPrice' => $dblUnitPrice, 
                    'total' => $dblTotal, 
                    'regPrice' => 0.5 * $row["regPrice"], 
                    'scale' => $intScale, 
                    'tax' => $row["tax"], 
                    'foodstamp' => $intFoodStamp, 
                    'discount' => $dblDiscount, 
                    'memDiscount' => $dblmemDiscount, 
                    'discountable' => $intDiscountable, 
                    'discounttype' => $intdiscounttype, 
                    'ItemQtty' => $dblquantity, 
                    'volDiscType' => $row["volDiscType"], 
                    'volume' => $row["volume"], 
                    'VolSpecial' => $dblVolSpecial,
                ));

                $update = "update localtemptrans set voided = 20 where trans_id = " . CoreLocal::get("currentid");
                $connection = Database::tDataConnect();
                $connection->query($update);

                CoreLocal::set("TTLflag",0);
                CoreLocal::set("TTLRequested",0);

                $ret['output'] = DisplayLib::lastpage();
            }
        }

        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>50JC</td>
                <td>Some kind of card special. I don't
                actually use this one myself</td>
            </tr>
            </table>";
    }
}

