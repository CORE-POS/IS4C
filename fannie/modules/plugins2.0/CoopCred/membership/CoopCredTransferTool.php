<?php
/*******************************************************************************

    Copyright 2010,2013 Whole Foods Co-op, Duluth, MN
    Copyright 2014 West End Food Co-op, Toronto, ON, Canada

    This file is part of Fannie.

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

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CoopCredTransferTool extends FanniePage {

    public $themed = 1;
    protected $title='Fannie Member - Transfer Coop Cred';
    protected $header='Transfer Coop Cred';

    private $errors = '';
    private $mode = 'init';
    // Doesn't need to be an array for Coop Cred.
    private $depts = array();

    /* Whole Foods Duluth defaults. May be obsolete for CORE.
     */
    private $CORRECTION_CASHIER = 1001;
    private $CORRECTION_LANE = 30;
    private $CORRECTION_DEPT = 800;

    private $dept;
    private $amount;
    private $cn1;
    private $cn2;
    private $name1;
    private $name2;
    private $comment;
    private $memberBeingEdited;
    private $hiddenTags = array();
    private $hiddenSearch = array();
    private $hiddenSearchString;
    private $programPaymentDepartment;
    private $programBankNumber;
    private $programName;
    private $programID;
    private $negativeBalanceOK;

    function preprocess() {

        global $FANNIE_CORRECTION_CASHIER, $FANNIE_CORRECTION_LANE, $FANNIE_CORRECTION_DEPT;
        global $FANNIE_PLUGIN_LIST,$FANNIE_PLUGIN_SETTINGS,$FANNIE_OP_DB;

        if (isset($FANNIE_CORRECTION_CASHIER)) {
            $this->CORRECTION_CASHIER = $FANNIE_CORRECTION_CASHIER;
        }
        if (isset($FANNIE_CORRECTION_LANE)) {
            $this->CORRECTION_LANE = $FANNIE_CORRECTION_LANE;
        }
        if (isset($FANNIE_CORRECTION_DEPT)) {
            $this->CORRECTION_DEPT = $FANNIE_CORRECTION_DEPT;
        }

        if (!isset($FANNIE_PLUGIN_LIST) || !in_array('CoopCred', $FANNIE_PLUGIN_LIST)) {
            $this->errors .= "Error: Coop Cred Plugin is not enabled.";
            return True;
        }

        if (array_key_exists('CoopCredDatabase', $FANNIE_PLUGIN_SETTINGS) &&
            $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'] != "") {
                $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
        } else {
            $this->errors .= "Error: Coop Cred Database not named in Plugin Settings.";
            return True;
        }

        $OP = $FANNIE_OP_DB.$dbc->sep();

        if (FormLib::get_form_value('programID',False) !== False) {
            $this->programID = FormLib::get_form_value('programID');
        } else {
            $this->errors .= "<em>" .
                _("Error: ") . _("Program ID not supplied.") .
                "</em>" .
                "";
            return True;
        }

        if (FormLib::get_form_value('programID') == "0") {
            $backLabel = _("Return to Member Editor");
            $this->errors .= "<em>" .
                _("Warning: ") . _("The Member does not yet belong to a Program.") .
                "<br />" .
                _("Please add him/her to a Program, Save, and Edit again to do the Transfer.") .
                "</em>" .
                "<br />" .
                "<a href=\"javascript:history.back();\">{$backLabel}</a>" .
                "";
            return True;
        }

        $ccpModel = new CCredProgramsModel($dbc);
        $ccpModel->programID($this->programID);
        $inputOK = null;
        $prog = array_pop($ccpModel->find());
        if ($prog == null) {
            $this->errors .= "<em>Error: Program ID {$this->programID} is not known.</em>";
            return True;
        }
        $inputOK = $prog->inputOK();
        $this->programPaymentDepartment = $prog->paymentDepartment();
        $this->dept = $prog->paymentDepartment();
        $this->programName = $prog->programName();
        $this->programBankNumber = $prog->bankID();
        /* To use while choosing dept is still part of the form.
         * It isn't, in Input or Transfer but maybe in another Tool?
         */
        $this->depts[$this->dept] = $this->programName;

        /* 16Dec2014 This is not tickable on the form yet.
         */
        $this->negativeBalanceOK = FormLib::get_form_value('negativeBalanceOK',False);

        foreach ($_REQUEST as $name => $value) {
            $item = "{$name}={$value}";
            if (!in_array("$item",$this->hiddenSearch) && substr($name,0,6) != "submit") {
                $this->hiddenSearch[] = "$item";
            }
        }

        if (FormLib::get_form_value('memEDIT',False) !== False) {
            $this->memberBeingEdited = FormLib::get_form_value('memEDIT');
        }

        if (FormLib::get_form_value('submit1',False) !== False)
            $this->mode = 'confirm';
        elseif (FormLib::get_form_value('submit2',False) !== False)
            $this->mode = 'finish';

        /* If the transfer values form is to be displayed
         */
        if ($this->mode == 'init') {
            $memNum = FormLib::get_form_value('memIN');
            if ($memNum != 0) {
                $q = $dbc->prepare("SELECT FirstName,LastName
                    FROM {$OP}custdata
                    WHERE CardNo=? AND personNum=1");
                $r = $dbc->execute($q,array($memNum));
                if ($dbc->num_rows($r) == 0) {
                    $this->errors .= "<em>Error: no such member: ".$memNum."</em>"
                        ."<br /><br />"
                        ."<a href=\"\" onclick=\"back(); return false;\">Back</a>";
                    return True;
                }
                $row = $dbc->fetch_row($r);
                $this->name1 = $row['FirstName'].' '.$row['LastName'];
            }
        }

        /* error check of inputs from form
         */
        if ($this->mode != 'init') {

            $this->dept = FormLib::get_form_value('dept');
            $this->amount = FormLib::get_form_value('amount');
            $this->cn1 = FormLib::get_form_value('memFrom');
            $this->cn2 = FormLib::get_form_value('memTo');
            $this->comment = FormLib::get_form_value('comment');

            /* If full URL is used to go back to the Transfer form
             * compose the search string so the parts op filled out aren't lost.
             * I don't know that this was ever used or worked.
             */
            foreach ($_REQUEST as $name => $value) {
                if (substr($name,0,6) != "submit") {
                    $item = "{$name}={$value}";
                    if (!in_array("$item",$this->hiddenSearch)) {
                        $this->hiddenSearch[] = "$item";
                    }
                }
            }
            $this->hiddenSearchString = "?" . implode('&amp;',$this->hiddenSearch);

            /* This "back" technique allows the form to display but loses any
             *  input values.
             * Not all of these errors are actually possible as the form now is.
             */
            $backLabel = _("Return to Transfer form");
            if (!isset($this->depts[$this->dept])) {
                $this->errors .= "<em>Error: Payment department doesn't exist</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if ("$this->amount" == "") {
                $this->errors .= "<em>Error: please enter an amount to transfer</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if ($this->amount < 0) {
                $this->errors .= "<em>Error: amount to transfer given (".$this->amount
                    .") is negative.</em>"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if (!is_numeric($this->amount)) {
                $this->errors .= "<em>Error: amount to transfer given (".$this->amount
                    .") isn't a number</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if (!is_numeric($this->cn1)) {
                $this->errors .= "<em>Error: transfer 'From' member given (".$this->cn1
                    .") isn't a number</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if ($this->cn1 == 0) {
                $this->errors .= "<em>Error: choose a  member to transfer 'From'</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if (!is_numeric($this->cn2)) {
                $this->errors .= "<em>Error: transfer 'To' member given (".$this->cn2
                    .") isn't a number</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if ($this->cn1 == $this->cn2) {
                $this->errors .= "<em>Error: 'From' and 'To' cannot be the same</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if ($this->cn2 == 0) {
                $this->errors .= "<em>Error: choose a member to tranfser 'To'</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }

            if ($this->cn1 > 0) {
                $q = "SELECT c.FirstName, c.LastName, b.availableBalance
                    FROM {$OP}custdata c
                    JOIN CCredMemCreditBalance b ON c.CardNo = b.cardNo
                    WHERE c.CardNo=? AND c.personNum=1
                    AND b.programID =" . $this->programID .
                    "";
                $s = $dbc->prepare($q);
                $r = $dbc->execute($s,array($this->cn1));
                if ($dbc->num_rows($r) == 0) {
                    $this->errors .= "<em>Error: no such member: ".$this->cn1."</em>"
                        ."<br /><br />"
                        ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                    return True;
                }
                $row = $dbc->fetch_row($r);
                $this->name1 = $row['FirstName'].' '.$row['LastName'];
            } else {
                $this->name1 = "Account Adjustment";
            }
            if ($this->mode == 'confirm' &&
                $this->name1 != 'Account Adjustment' &&
                $this->negativeBalanceOK == False &&
                $row['availableBalance'] < $this->amount) {
                    $this->errors .= "<em>Error: you may not transfer more: \$" .
                        $this->amount .
                        " than the Available Balance in the account: \$" .
                        $row['availableBalance'] .
                        "</em>" .
                        "<br /><br />" .
                        "<a href=\"javascript:history.back();\">{$backLabel}</a>";
                    return True;
                }

            $q = $dbc->prepare("SELECT FirstName,LastName
                FROM {$OP}custdata
                WHERE CardNo=? AND personNum=1");
            $r = $dbc->execute($q,array($this->cn2));
            if ($dbc->num_rows($r) == 0) {
                $this->errors .= "<em>Error: no such member: ".$this->cn2."</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            $row = $dbc->fetch_row($r);
            $this->name2 = $row['FirstName'].' '.$row['LastName'];
        }

        return True;

    // preprocess()
    }
    
    function body_content() {
        if ($this->mode == 'init')
            return $this->form_content();
        elseif($this->mode == 'confirm')
            return $this->confirm_content();
        elseif($this->mode == 'finish')
            return $this->finish_content();
    }

    /* mode == confirm. Describe what will happen and prompt for OK.
     */
    function confirm_content() {

        if (!empty($this->errors)) {
            return "<p style='font-size:1.2em;'>" . $this->errors . "</p>";
        }

        $this->add_onload_command("\$('#bttf').focus();\n");

        $ret = "<form action=\"CoopCredTransferTool.php\" method=\"post\">";
        $ret .= "<p style='font-size:1.2em; margin-top:1.0em;'>";
        $ret .= "<span style='font-size:1.4em;'>Program: {$this->programName}</span><br />";
        $ret .= sprintf("\$%.2f %swill be moved from %s (#%d) to %s (#%d)",
            $this->amount,
            (($this->comment != "") ? " - {$this->comment}<br />" : ""),
            $this->name1,
            $this->cn1,
            $this->name2,
            $this->cn2
            );
        $ret .= "</p>";
        $ret .= "<p>";
        $ret .= "<input type=\"submit\" name=\"submit2\" value=\"Confirm the Transfer\" />";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<input type=\"submit\" id=\"bttf\" value=\"Back to Transfer Form\" " .
                "onclick=\"back(); return false;\" />";
        $ret .= "</p>";
        $ret .= "<input type=\"hidden\" name=\"dept\" value=\"{$this->dept}\" />";
        $ret .= "<input type=\"hidden\" name=\"amount\" value=\"{$this->amount}\" />";
        $ret .= "<input type=\"hidden\" name=\"memFrom\" value=\"{$this->cn1}\" />";
        $ret .= "<input type=\"hidden\" name=\"memTo\" value=\"{$this->cn2}\" />";
        $ret .= "<input type=\"hidden\" name=\"memIN\" value=\"{$this->memberBeingEdited}\" />";
        $ret .= "<input type=\"hidden\" name=\"memEDIT\" value=\"{$this->memberBeingEdited}\" />";
        $ret .= "<input type=\"hidden\" name=\"programID\" value=\"{$this->programID}\" />";
        $ret .= "<input type=\"hidden\" name=\"comment\" value=\"{$this->comment}\" />";
        $ret .= "</form>";
        
        return $ret;

    // /confirm_content()
    }

    /* mode == finish. Record the transaction and prompt to return to the main editor.
     * This should use an SQL transaction.
     */
    function finish_content() {
        global $FANNIE_URL;

        if (!empty($this->errors)) {
            return "<p style='font-size:1.2em;'>" . $this->errors . "</p>";
        }

        $this->add_onload_command("\$('#goAgain').focus();\n");

        $close = "<form action=\"CoopCredTransferTool.php\" method=\"post\">";
        $close .= "<p>";
        $close .= "<input type=\"submit\" id=\"goAgain\" name=\"goAgain\" " .
                "value=\"Do Another Transfer\" />";
        $close .= "</p>";
        $close .= "<input type=\"hidden\" name=\"memIN\" value=\"{$this->memberBeingEdited}\" />";
        $close .= "<input type=\"hidden\" name=\"memEDIT\" value=\"{$this->memberBeingEdited}\" />";
        $close .= "<input type=\"hidden\" name=\"programID\" value=\"{$this->programID}\" />";
        $close .= "</form>";

        $close .= "<p><a href='{$FANNIE_URL}mem/MemberEditor.php?memNum={$this->memberBeingEdited}'>" .
            "Return to the main Member Editor page</a></p>";

        $ret = '';
        $ret .= "<p style='font-size:1.2em;'>";

        // Say what you're gonna do ...
        $ret .= sprintf("Transfer of \$%.2f %sfrom %s (#%d) to %s (#%d) ",
            $this->amount,
            (($this->comment != "") ? " - {$this->comment}<br />" : ""),
            $this->name1,
            $this->cn1,
            $this->name2,
            $this->cn2
            );

        // From:
        $dtrans = array();
        $dtrans['trans_no'] = $this->getTransNo($this->CORRECTION_CASHIER,$this->CORRECTION_LANE);
        $dtrans['trans_id'] = 1;
        $rslt = $this->doInsert($dtrans,$this->amount,$this->CORRECTION_DEPT,$this->cn1,'');
        if ($rslt !== True) {
            $ret .= "<br />Failed: $rslt</p>";
            $ret .= $close;
            return $ret;
        }

        $dtrans['trans_id']++;
        $rslt = $this->doInsert($dtrans,-1*$this->amount,$this->dept,$this->cn1,$this->comment);
        if ($rslt !== True) {
            $ret .= "<br />Failed: $rslt</p>";
            $ret .= $close;
            return $ret;
        }

        // For composing receipt links
        $rrp  = "{$FANNIE_URL}admin/LookupReceipt/RenderReceiptPage.php";
        $tdy = explode('-',date('Y-m-d'));

        $transNum = sprintf("%d-%d-%d",
            $this->CORRECTION_CASHIER,
            $this->CORRECTION_LANE,
            $dtrans['trans_no']);
        $target = " target='_rcpt'";
        $rcpt = sprintf("<a href='%s?year=%d&month=%d&day=%d&receipt=%s'%s>%s</a>",
                $rrp,
                $tdy[0],$tdy[1],$tdy[2],
                $transNum,$target,$transNum);
        $r1 = sprintf("Receipt #1: %s", $rcpt);

        // To:
        $dtrans['trans_no'] = $this->getTransNo($this->CORRECTION_CASHIER,$this->CORRECTION_LANE);
        $dtrans['trans_id'] = 1;
        $rslt = $this->doInsert($dtrans,$this->amount,$this->dept,$this->cn2,$this->comment);
        if ($rslt !== True) {
            $ret .= "<br />Failed: $rslt</p>";
            $ret .= $close;
            return $ret;
        }

        $dtrans['trans_id']++;
        $rslt = $this->doInsert($dtrans,-1*$this->amount,$this->CORRECTION_DEPT,$this->cn2,'');
        if ($rslt !== True) {
            $ret .= "<br />Failed: $rslt</p>";
            $ret .= $close;
            return $ret;
        }

        $transNum = sprintf("%d-%d-%d",
            $this->CORRECTION_CASHIER,
            $this->CORRECTION_LANE,
            $dtrans['trans_no']);
        $target = " target='_rcpt2'";
        $rcpt = sprintf("<a href='%s?year=%d&month=%d&day=%d&receipt=%s'%s>%s</a>",
                $rrp,
                $tdy[0],$tdy[1],$tdy[2],
                $transNum,$target,$transNum);
        $r2 = sprintf("Receipt #2: %s", $rcpt);

        // Didn't fail.
        $ret .= "<br />";
        $ret .= "OK";
        $ret .= '<br />';
        $ret .= $r1;
        $ret .= '<br />';
        $ret .= $r2;
        $ret .= "</p>";

        $ret .= $close;

        return $ret;

    // /finish_content()
    }

    /* mode == init. Display the form for entering Transfer details.
     */
    function form_content() {
        global $FANNIE_URL;

        if (!empty($this->errors)) {
            return "<p style='font-size:1.2em;'>" . $this->errors . "</p>";
        }

        $memNum = FormLib::get_form_value('memIN');

        $this->add_onload_command("\$('#amount').focus();\n");

        $ret = "<form action=\"CoopCredTransferTool.php\" method=\"post\">";
        $ret .= "<p style=\"font-size:1.6em;\">";
        $ret .= "Program: {$this->programName}";
        $ret .= "<br />";
        $ret .= "<span style=\"font-size:0.8em;\">";
        $memNum = FormLib::get_form_value('memIN');
        $ret .= "Member: {$this->name1} - #{$memNum}";
        $ret .= "</span>";
        $ret .= "</p>";
        $ret .= "<p>This form is for moving money from one member of the Program to another.";
        $ret .= "<br />The Program Bank is a member and the source of funds distributed " .
            "to regular members.";
        $ret .= "<br />To add money to the Program Bank from an external source use the Input Tool.";
        $ret .= "</p>";

        $ret .= "<table border=0>";
        $ret .= "<tr style='vertical-align:top;'>";
        $ret .= "<td>";
        $ret .= "<p style='font-size:1.2em; margin:0 1.5em 0 0;'>";
        $ret .= "Transfer $ <input type=\"text\" id=\"amount\" name=\"amount\" ".
            "size=\"5\" value='" .
            ((FormLib::get_form_value('amount',False) !== False)
            ? FormLib::get_form_value('amount') : "")
            . "'/> ";
        $ret .= "<br />Use only positive numbers.";
        $ret .= "</p>";
        $ret .= "</td>";
        $ret .= "<td colspan='1'>";
        $ret .= "<p style='font-size:1.2em; margin:0;'>";
        $ret .= "<input type='text' name='comment' size='30' maxlength='30' />";
        $ret .= "<br />Comment (optional), for example the reason for the transfer.";
        $ret .= "<br />(up to 30 characters)";
        $ret .= "</p>";
        $ret .= "</td>";
        $ret .= "</tr>";
        $ret .= "</table>";

        $ret .= "<p style='margin:0.5em 0 0 0;'>";
        $ret .= "<table border=0>";
        $ret .= "<tr>";
        $ret .= "<td>";
        $ret .= "<span style='font-size:1.2em; font-weight:bold;'>From:</span>";
        $ret .= "</td>";
        $ret .= "<td>";
        $ret .= "<span style='font-size:1.2em; font-weight:bold;'>To:</span>";
        $ret .= "</td>";
        $ret .= "</tr>";

        $ret .= "<tr>";
        $ret .= "<td>";
        $memberOptions = $this->getMemberOptions($this->programID,$this->programBankNumber);
        $ret .= ("<select name='memFrom' size='1'>{$memberOptions}</select>");
        $ret .= "</td>";
        $ret .= "<td>";
        if ($this->memberBeingEdited == $this->programBankNumber) {
            $memberOptions = $this->getMemberOptions($this->programID,0);
        } else {
            $memberOptions = $this->getMemberOptions($this->programID,$this->memberBeingEdited);
        }
        $ret .= ("<select name='memTo' size='1'>{$memberOptions}</select>");
        $ret .= "</td>";
        $ret .= "</tr>";

        $ret .= "</table>";
        $ret .= "</p>";

        $ret .= "<p>";
        $ret .= "<input type='submit' name='submit1' value='Transfer' />";
        // pPD shouldn't be needed.
        $ret .= "<input type='hidden' name='dept' value='{$this->programPaymentDepartment}' />";
        $ret .= "<input type='hidden' name='programID' value='{$this->programID}' />";
        $ret .= "<input type='hidden' name='memEDIT' value='{$this->memberBeingEdited}' />";
        $ret .= "<input type='hidden' name='negativeBalanceOK' value='1' />";
        $ret .= "</p>";
        $ret .= "<p><a href='{$FANNIE_URL}mem/MemberEditor.php?memNum={$this->memberBeingEdited}'>" .
                "No Transfer: Return to the main Member Editor page</a></p>";
        $ret .= "</form>";

        return $ret;

    // /form_content()
    }

    /* Return the next transaction number for the given cashier and lane.
     */
    function getTransNo($emp,$register) {
        global $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $q = $dbc->prepare("SELECT max(trans_no)
            FROM dtransactions
            WHERE register_no=? AND emp_no=?");
        $r = $dbc->execute($q,array($register,$emp));
        $n = array_pop($dbc->fetch_row($r));
        return (empty($n)?1:$n+1);    
    }

    /* Insert rows for the input to dtransactions.
     * @return True or an error message.
     * @param department Department number for the purchase
     *                  0 flags the Tender item
     * @param comment If department=0 is the Tender Type
     *               Otherwise optional, from the operator.
     *
     * Probably more robust to do this with a model.
     */
    // #'i 
    function doInsert($dtrans,$amount,$department,$cardno,$comment=''){
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $OP = $FANNIE_OP_DB.$dbc->sep();

        $defaults = array(
            'register_no'=>$this->CORRECTION_LANE,
            'emp_no'=>$this->CORRECTION_CASHIER,
            'trans_no'=>$dtrans['trans_no'],
            'upc'=>'',
            'description'=>'',
            'trans_type'=>'D',
            'trans_subtype'=>'',
            'trans_status'=>'',
            'department'=>'',
            'quantity'=>1,
            'scale'=>0,
            'cost'=>0,
            'unitPrice'=>'',
            'total'=>'',
            'regPrice'=>'',
            'tax'=>0,
            'foodstamp'=>0,
            'discount'=>0,
            'memDiscount'=>0,
            'discountable'=>0,
            'discounttype'=>0,
            'voided'=>0,
            'percentDiscount'=>0,
            'ItemQtty'=>1,
            'volDiscType'=>0,
            'volume'=>0,
            'volSpecial'=>0,
            'mixMatch'=>'',
            'matched'=>0,
            'memType'=>'',
            'staff'=>'',
            'numflag'=>0,
            'charflag'=>'',
            'card_no'=>'',
            'trans_id'=>$dtrans['trans_id']
        );

        $defaults['department'] = $department;
        $defaults['card_no'] = $cardno;
        $defaults['total'] = $amount;
        // department=0 flags Tender
        if ($department == 0) {
            $defaults['unitPrice'] = 0;
            $defaults['regPrice'] = 0;
            $defaults['trans_type'] = 'T';
            $defaults['trans_subtype'] = $comment;
            $defaults['trans_status'] = '0';
            $defaults['quantity'] = 0;
            $defaults['ItemQtty'] = 0;
            $defaults['upc'] = '0';
            $tenderQ = "SELECT TenderName FROM {$OP}tenders WHERE TenderCode=?";
            $tenderP = $dbc->prepare($tenderQ);
            $tArgs = array($comment);
            $tenderR = $dbc->execute($tenderP,$tArgs);
            if ($tenderR === False) {
                return "$tenderQ\nargs:" . implode(":",$tArgs);
            }
            if ($dbc->num_rows($tenderR) == 0) {
                $defaults['description'] = $comment;
            } else {
                $tenderW = $dbc->fetch_row($tenderR);
                $defaults['description'] = $tenderW['TenderName'];
            }
        // The Program-Input item, Not a Tender.
        } else {
            $defaults['unitPrice'] = $amount;
            $defaults['regPrice'] = $amount;
            if ($amount < 0){
                $defaults['trans_status'] = 'R';
                $defaults['quantity'] = -1;
            }
            $defaults['upc'] = abs($amount).'DP'.$department;
            if ($comment != "") {
                $defaults['description'] = $comment;
            } else {
                if (isset($this->depts[$department]))
                    $defaults['description'] = $this->depts[$department];
                else {
                    $nameQ = "SELECT dept_name FROM {$OP}departments WHERE dept_no=?";
                    $nameP = $dbc->prepare($nameQ);
                    $nArgs = array($department);
                    $nameR = $dbc->execute($nameP,$nArgs);
                    if ($nameR === False) {
                        return "$nameQ\nargs:" . implode(":",$nArgs);
                    }
                    if ($dbc->num_rows($nameR) == 0) {
                        $defaults['description'] = 'CORRECTIONS';
                    } else {
                        $nameW = $dbc->fetch_row($nameR);
                        $defaults['description'] = $nameW['dept_name'];
                    }
                }
            }
        }

        $q = "SELECT memType,Staff FROM {$OP}custdata WHERE CardNo=?";
        $s = $dbc->prepare($q);
        $args = array($cardno);
        $r = $dbc->execute($s,$args);
        if ($r === False) {
            return "$q\nargs:" . implode(":",$args);
        }
        $w = $dbc->fetch_row($r);
        $defaults['memType'] = $w[0];
        $defaults['staff'] = $w[1];

        $columns = 'datetime,';
        $values = $dbc->now().',';
        $args = array();
        foreach($defaults as $k=>$v){
            $columns .= $k.',';
            $values .= '?,';
            $args[] = $v;
        }
        $columns = substr($columns,0,strlen($columns)-1);
        $values = substr($values,0,strlen($values)-1);
        $query = "INSERT INTO dtransactions ($columns) VALUES ($values)";
        $prep = $dbc->prepare($query);
        $rslt = $dbc->execute($prep, $args);
        if ($rslt === False) {
            return "$query\nargs:" . implode(":",$args);
        }

        return True;

    // doInsert()
    }


    /* Return a set of <option>s for program members
     *  with $selectedMember selected
     *  or "Select a Member" if $selectedMember is 0
     */
    function getMemberOptions ($programID, $selectedMember=0)
    {
        global $FANNIE_PLUGIN_SETTINGS,$FANNIE_OP_DB;
        //$dbc = FannieDB::get($FANNIE_OP_DB);
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
        $OP = $FANNIE_OP_DB.$dbc->sep();

        $ret = "";
        if ($selectedMember == 0) {
            $ret = "<option value='$selectedMember'>Select a Member</option>";
        }
        $memberQ = "SELECT c.CardNo, c.FirstName, c.LastName,
            b.availableBalance
            FROM {$OP}custdata c
            JOIN CCredMemberships m ON c.CardNo = m.cardNo
            JOIN CCredMemCreditBalance b ON c.CardNo = b.cardNo
            WHERE m.programID ={$programID} AND c.personNum =1
                AND b.programID ={$programID}
            ORDER BY c.LastName";
        $memberS = $dbc->prepare("$memberQ");
        if ($memberS === False) {
            return "<option value=''>Failed: prep $s : See queries.log</option>";
        }
        $memberR = $dbc->execute($memberS, array());
        if ($memberR === False) {
            return "<option value=''>Failed: exec $memberQ : See queries.log</option>";
        }
        while ($r = $dbc->fetch_row($memberR)) {
            $selected = ($r['CardNo'] == $selectedMember) ? " SELECTED " : "";
            $ret .= "<option value='{$r['CardNo']}'{$selected}>" .
                "{$r['FirstName']} {$r['LastName']} " .
                ": {$r['CardNo']} " .
                "(\${$r['availableBalance']})" .
                "</option>";
        }

        return $ret;
        
    // /getMemberOptions()
    }

}

FannieDispatch::conditionalExec();

