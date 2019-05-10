<?php

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\MemberLib;
use COREPOS\pos\lib\TransRecord;
use \CoreLocal;
use \Exception;

class Undo
{
    private static function getTransaction($empNo, $registerNo, $oldTransNo)
    {
        $dbc = 0;
        $query = "";
        if ($registerNo == CoreLocal::get('laneno')) {
            // look up transation locally
            $dbc = Database::tDataConnect();
            $query = "select upc, description, trans_type, trans_subtype,
                trans_status, department, quantity, scale, unitPrice,
                total, regPrice, tax, foodstamp, discount, memDiscount,
                discountable, discounttype, voided, PercentDiscount,
                ItemQtty, volDiscType, volume, VolSpecial, mixMatch,
                matched, card_no, trans_id
                from localtranstoday where register_no = $registerNo
                and emp_no = $empNo and trans_no = $oldTransNo
                and datetime >= " . $dbc->curdate() . "
                and trans_status <> 'X'
                order by trans_id";
        } elseif (CoreLocal::get("standalone") == 1) {
            // error: remote lookups won't work in standalone
            throw new Exception(_('Transaction not found'));
        } else {
            // look up transaction remotely
            $dbc = Database::mDataConnect();
            if ($dbc === false) {
                throw new Exception(_("Transaction not available"));
            }
            $query = "select upc, description, trans_type, trans_subtype,
                trans_status, department, quantity, scale, unitPrice,
                total, regPrice, tax, foodstamp, discount, memDiscount,
                discountable, discounttype, voided, PercentDiscount,
                ItemQtty, volDiscType, volume, VolSpecial, mixMatch,
                matched, card_no, trans_id
                from dtransactions where register_no = $registerNo
                and emp_no = $empNo and trans_no = $oldTransNo
                and datetime >= " . $dbc->curdate() . "
                and trans_status <> 'X'
                order by trans_id";
        }

        $result = $dbc->query($query);
        // transaction not found
        if ($dbc->numRows($result) < 1) {
            throw new Exception(_("Transaction not found"));
        }

        $ret = array();
        while ($row = $dbc->fetchRow($result)) {
            $ret[] = $row;
        }

        return $ret;
    }

    public static function reverseTransaction($empNo, $registerNo, $transNo)
    {
        /* change the cashier to the original transaction's cashier */
        CoreLocal::set("CashierNo",$empNo);
        CoreLocal::set("transno",Database::gettransno($empNo));    

        /* rebuild the transaction, line by line, in reverse */
        $cardNo = 0;
        $trans = self::getTransaction($empNo, $registerNo, $transNo);
        foreach ($trans as $row) {
            $cardNo = $row["card_no"];

            if ($row["trans_type"] ==  "T"){
                if ($row["description"] == "Change")
                    TransRecord::addchange(-1*$row["total"]);
                else
                    TransRecord::addtender($row["description"],$row["trans_subtype"],-1*$row["total"]);
            }
            elseif (strstr($row["description"],"** YOU SAVED")){
                $temp = explode("$",$row["description"]);
                TransRecord::adddiscount(substr($temp[1],0,-3),$row["department"]);
            }
            elseif ($row["upc"] == "FS Tax Exempt")
                TransRecord::addfsTaxExempt();
            elseif (strstr($row["description"],"% Discount Applied")){
                $temp = explode("%",$row["description"]);    
                TransRecord::discountnotify(substr($temp[0],3));
            }
            elseif ($row["description"] == "** Order is Tax Exempt **")
                TransRecord::addTaxExempt();
            elseif ($row["description"] == "** Tax Excemption Reversed **")
                TransRecord::reverseTaxExempt();
            elseif ($row["description"] == " * Manufacturers Coupon")
                TransRecord::addCoupon($row["upc"],$row["department"],-1*$row["total"]);
            elseif (strstr($row["description"],"** Tare Weight")){
                $temp = explode(" ",$row["description"]);
                TransRecord::addTare($temp[3]*100);
            }
            elseif ($row["trans_status"] != "M" && $row["upc"] != "0" &&
                (is_numeric($row["upc"]) || strstr($row["upc"],"DP"))) {
                $row["trans_status"] = "V";
                $row["total"] *= -1;
                $row["discount"] *= -1;
                $row["memDiscount"] *= -1;
                $row["quantity"] *= -1;
                $row["ItemQtty"] *= -1;
                TransRecord::addRecord($row);
            }
        }

        MemberLib::setMember($cardNo, 1);
    }
}

