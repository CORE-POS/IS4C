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

    private function checkInput($trans_num)
    {
        // clear/cancel undo attempt
        if ($trans_num == "" || $trans_num == "CL"){
            $this->change_page($this->page_url."gui-modules/pos2.php");
            return false;
        }

        // error: malformed transaction number
        if (!strpos($trans_num,"-")){
            $this->boxColor="errorColoredArea";
            $this->msg = _("Transaction not found");
            return true;
        }

        $temp = explode("-",$trans_num);
        // error: malformed transaction number (2)
        if (count($temp) != 3){
            $this->boxColor="errorColoredArea";
            $this->msg = _("Transaction not found");
            return true;
        }

        $emp_no = $temp[0];
        $register_no = $temp[1];
        $old_trans_no = $temp[2];
        // error: malformed transaction number (3)
        if (!is_numeric($emp_no) || !is_numeric($register_no)
            || !is_numeric($old_trans_no)){
            $this->boxColor="errorColoredArea";
            $this->msg = _("Transaction not found");
            return true;
        }

        return array($emp_no, $register_no, $old_trans_no);
    }

    private function getTransaction($emp_no, $register_no, $old_trans_no)
    {
        $dbc = 0;
        $query = "";
        if ($register_no == $this->session->get("laneno")){
            // look up transation locally
            $dbc = Database::tDataConnect();
            $query = "select upc, description, trans_type, trans_subtype,
                trans_status, department, quantity, scale, unitPrice,
                total, regPrice, tax, foodstamp, discount, memDiscount,
                discountable, discounttype, voided, PercentDiscount,
                ItemQtty, volDiscType, volume, VolSpecial, mixMatch,
                matched, card_no, trans_id
                from localtranstoday where register_no = $register_no
                and emp_no = $emp_no and trans_no = $old_trans_no
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
                from dtransactions where register_no = $register_no
                and emp_no = $emp_no and trans_no = $old_trans_no
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
            $trans_num = strtoupper($this->form->reginput);
            $chk = $this->checkInput($trans_num);
            if (!is_array($chk)) {
                return $chk;
            }
            list($emp_no, $register_no, $old_trans_no) = $chk;
            $trans = $this->getTransaction($emp_no, $register_no, $old_trans_no);
            if (!is_array($trans)) {
                return $trans;
            }
            /* change the cashier to the original transaction's cashier */
            $prevCashier = $this->session->get("CashierNo");
            $this->session->set("CashierNo",$emp_no);
            $this->session->set("transno",Database::gettransno($emp_no));    

            /* rebuild the transaction, line by line, in reverse */
            $card_no = 0;
            TransRecord::addcomment("VOIDING TRANSACTION $trans_num");
            foreach ($trans as $row) {
                $card_no = $row["card_no"];

                if ($row["upc"] == "TAX"){
                    //TransRecord::addtax();
                }
                elseif ($row["trans_type"] ==  "T"){
                    if ($row["description"] == "Change")
                        TransRecord::addchange(-1*$row["total"]);
                    elseif ($row["description"] == "FS Change")
                        TransRecord::addfsones(-1*$row["total"]);
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
                elseif ($row["upc"] == "DISCOUNT"){
                    //TransRecord::addTransDiscount();
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

            COREPOS\pos\lib\MemberLib::setMember($card_no, 1);
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

