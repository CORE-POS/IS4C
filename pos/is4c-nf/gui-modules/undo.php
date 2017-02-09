<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\TransRecord;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class undo extends NoInputCorePage 
{
    private $msg;

    function body_content()
    {
        ?>
        <div class="baseHeight">
        <div class="<?php echo $this->boxColor; ?> centeredDisplay">
        <span class="larger">
        <?php echo $this->msg ?>
        </span><br />
        <form name="form" method='post' autocomplete="off" action="<?php echo filter_input(INPUT_SERVER, "PHP_SELF"); ?>">
        <input type="text" name="reginput" id="reginput" tabindex="0" onblur="($'#reginput').focus();" >
        </form>
        <p>
        <?php echo _('Enter transaction number<br />[clear to cancel]'); ?>
        </p>
        </div>
        </div>
        <?php
        $this->add_onload_command("\$('#reginput').focus();");
    }

    private function checkInput($transNum)
    {
        // clear/cancel undo attempt
        if ($transNum == "" || $transNum == "CL"){
            $this->change_page($this->page_url."gui-modules/pos2.php");
            return false;
        }

        // error: malformed transaction number
        if (!strpos($transNum,"-")){
            $this->boxColor="errorColoredArea";
            $this->msg = _("Transaction not found");
            return true;
        }

        $temp = explode("-",$transNum);
        // error: malformed transaction number (2)
        if (count($temp) != 3){
            $this->boxColor="errorColoredArea";
            $this->msg = _("Transaction not found");
            return true;
        }

        $empNo = $temp[0];
        $registerNo = $temp[1];
        $oldTransNo = $temp[2];
        // error: malformed transaction number (3)
        if (!is_numeric($empNo) || !is_numeric($registerNo)
            || !is_numeric($oldTransNo)){
            $this->boxColor="errorColoredArea";
            $this->msg = _("Transaction not found");
            return true;
        }

        return array($empNo, $registerNo, $oldTransNo);
    }

    private function getTransaction($empNo, $registerNo, $oldTransNo)
    {
        $dbc = 0;
        $query = "";
        if ($registerNo == $this->session->get("laneno")){
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
        } elseif ($this->session->get("standalone") == 1) {
            // error: remote lookups won't work in standalone
            $this->boxColor="errorColoredArea";
            $this->msg = _("Transaction not found");
            return true;
        } else {
            // look up transaction remotely
            $dbc = Database::mDataConnect();
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
        if ($dbc->num_rows($result) < 1) {
            $this->boxColor="errorColoredArea";
            $this->msg = _("Transaction not found");
            return true;
        }

        $ret = array();
        while ($row = $dbc->fetchRow($result)) {
            $ret[] = $row;
        }

        return $ret;
    }

    function preprocess()
    {
        $this->boxColor = "coloredArea";
        $this->msg = _("Undo transaction");

        try {
            $transNum = strtoupper($this->form->reginput);
            $chk = $this->checkInput($transNum);
            if (!is_array($chk)) {
                return $chk;
            }
            list($empNo, $registerNo, $oldTransNo) = $chk;
            $trans = $this->getTransaction($empNo, $registerNo, $oldTransNo);
            if (!is_array($trans)) {
                return $trans;
            }
            /* change the cashier to the original transaction's cashier */
            $this->session->set("CashierNo",$empNo);
            $this->session->set("transno",Database::gettransno($empNo));    

            /* rebuild the transaction, line by line, in reverse */
            $cardNo = 0;
            TransRecord::addcomment("VOIDING TRANSACTION $transNum");
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

            COREPOS\pos\lib\MemberLib::setMember($cardNo, 1);
            $this->session->set("autoReprint",0);

            /* do NOT restore logged in cashier until this transaction is complete */
            
            $this->change_page($this->page_url."gui-modules/undo_confirm.php");
            return false;
        } catch (Exception $ex) {}
        
        return true;
    }

    public function unitTest($phpunit)
    {
        ob_start();
        $phpunit->assertEquals(false, $this->checkInput(''));
        $phpunit->assertEquals(false, $this->checkInput('CL'));
        $phpunit->assertEquals(true, $this->checkInput('111'));
        $phpunit->assertEquals(true, $this->checkInput('1-11'));
        $phpunit->assertEquals(true, $this->checkInput('1-1-z'));
        $phpunit->assertEquals(array(1,1,1), $this->checkInput('1-1-1'));
        $phpunit->assertEquals(true, $this->getTransaction(1, 1, 1));
        ob_get_clean();
    }
}

AutoLoader::dispatch();

