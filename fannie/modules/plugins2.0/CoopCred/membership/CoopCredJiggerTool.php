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

class CoopCredJiggerTool extends FanniePage {

    public $themed = 1;
    protected $title='Fannie Member - Fix Coop Cred';
    protected $header='Fix Coop Cred';

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
    private $memberBeingEdited;
    private $programBankNumber;
    private $programPaymentDepartment;
    private $programName;
    private $programID;

    function preprocess(){
        global $FANNIE_PLUGIN_LIST,$FANNIE_PLUGIN_SETTINGS,$FANNIE_OP_DB;
        global $FANNIE_CORRECTION_CASHIER, $FANNIE_CORRECTION_LANE, $FANNIE_CORRECTION_DEPT;

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

        if (FormLib::get_form_value('programID',False) !== False) {
            $this->programID = FormLib::get_form_value('programID');
        } else {
            $this->errors .= "<em>Error: Program ID not supplied.</em>";
            return True;
        }
        /* Get Program info.
         */
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
        $this->depts[$this->dept] = $this->programName;

        if (FormLib::get_form_value('memEDIT',False) !== False)
            $this->memberBeingEdited = FormLib::get_form_value('memEDIT');

        if (FormLib::get_form_value('submit1',False) !== False)
            $this->mode = 'confirm';
        elseif (FormLib::get_form_value('submit2',False) !== False)
            $this->mode = 'finish';

        if ($this->mode == 'init'){
            $memNum = FormLib::get_form_value('memIN');
            if ($memNum != 0) {
                $q = $dbc->prepare("SELECT FirstName,LastName
                    FROM {$OP}custdata
                    WHERE CardNo=? AND personNum=1");
                $r = $dbc->execute($q,array($memNum));
                if ($dbc->num_rows($r) == 0){
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
        if ($this->mode != 'init'){

            $this->dept = FormLib::get_form_value('dept');
            $this->amount = FormLib::get_form_value('amount');
            $this->cn1 = FormLib::get_form_value('memFrom');
            $this->cn2 = FormLib::get_form_value('memTo');

            /* This "back" technique allows the form to display but loses any
             *  input values.
             */
            $backLabel = "Return to Fix form";
            if (!isset($this->depts[$this->dept])){
                $this->errors .= "<em>Error: AR department doesn't exist</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if (!is_numeric($this->amount)){
                $this->errors .= "<em>Error: amount given (".$this->amount.") isn't a number</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if (!is_numeric($this->cn1)){
                $this->errors .= "<em>Error: member given (".$this->cn1.") isn't a number</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            if (!is_numeric($this->cn2)){
                $this->errors .= "<em>Error: member given (".$this->cn2.") isn't a number</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }

            //EL From# as dummy for fix.
            if ($this->cn1 > 0) {
                $q = $dbc->prepare("SELECT FirstName,LastName
                    FROM {$OP}custdata
                    WHERE CardNo=? AND personNum=1");
                $r = $dbc->execute($q,array($this->cn1));
                if ($dbc->num_rows($r) == 0){
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

            $q = $dbc->prepare("SELECT FirstName,LastName
                FROM {$OP}custdata
                WHERE CardNo=? AND personNum=1");
            $r = $dbc->execute($q,array($this->cn2));
            if ($dbc->num_rows($r) == 0){
                $this->errors .= "<em>Error: no such member: ".$this->cn2."</em>"
                    ."<br /><br />"
                    ."<a href=\"javascript:history.back();\">{$backLabel}</a>";
                return True;
            }
            $row = $dbc->fetch_row($r);
            $this->name2 = $row[0].' '.$row[1];
        }

        return True;
    }
    
    function body_content(){
        if ($this->mode == 'init')
            return $this->form_content();
        elseif($this->mode == 'confirm')
            return $this->confirm_content();
        elseif($this->mode == 'finish')
            return $this->finish_content();
    }

    /* mode == confirm. Describe what will happen and prompt for OK.
     */
    function confirm_content(){

        if (!empty($this->errors)) {
            return "<p style='font-size:1.2em;'>" . $this->errors . "</p>";
        }

        $ret = "<form action=\"CoopCredJiggerTool.php\" method=\"post\">";
        $ret .= "<p style='font-size:1.2em; margin-top:1.0em;'>";
        $ret .= "<span style='font-size:1.4em;'>Program: {$this->programName}</span><br />";
        $ret .= sprintf("\$%.2f will be %s %s (#%d)",
            $this->amount,
            (($this->amount > 0) ? "added (credited) to:" : "removed (debited) from:"),
            $this->name2,
            $this->cn2
        );
        $ret .= "<br />The Program Bank account will not be changed.";
        $ret .= "</p>";

        $ret .= "<p>";
        $ret .= "<input type=\"submit\" name=\"submit2\" value=\"Confirm the Fix\" />";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "<input type=\"submit\" value=\"Back to Fix Form\" onclick=\"back(); return false;\" />";
        $ret .= "</p>";
        $ret .= "<input type=\"hidden\" name=\"dept\" value=\"{$this->dept}\" />";
        $ret .= "<input type=\"hidden\" name=\"amount\" value=\"{$this->amount}\" />";
        $ret .= "<input type=\"hidden\" name=\"memFrom\" value=\"{$this->cn1}\" />";
        $ret .= "<input type=\"hidden\" name=\"memTo\" value=\"{$this->cn2}\" />";
        $ret .= "<input type=\"hidden\" name=\"memIN\" value=\"{$this->memberBeingEdited}\" />";
        $ret .= "<input type=\"hidden\" name=\"memEDIT\" value=\"{$this->memberBeingEdited}\" />";
        $ret .= "<input type=\"hidden\" name=\"programID\" value=\"{$this->programID}\" />";
        $ret .= "</form>";
        
        return $ret;
    }

    /* mode == finish. Record the transaction and prompt to return to the main editor.
     */
    function finish_content(){
        global $FANNIE_URL;

        if (!empty($this->errors)) {
            return "<p style='font-size:1.2em;'>" . $this->errors . "</p>";
        }

        $ret = '';
        $ret .= "<p>";

        $dtrans['trans_no'] = $this->getTransNo($this->CORRECTION_CASHIER,$this->CORRECTION_LANE);
        $dtrans['trans_id'] = 1;
        $this->doInsert($dtrans,$this->amount,$this->dept,$this->cn2);

        $dtrans['trans_id']++;
        $this->doInsert($dtrans,-1*$this->amount,$this->CORRECTION_DEPT,$this->cn2);

        // Say what you did ...
        $ret .= "OK.<br />";
        $ret .= sprintf("Receipt: %d-%d-%d",
            $this->CORRECTION_CASHIER,
            $this->CORRECTION_LANE,
            $dtrans['trans_no']);
        $ret .= "</p>";

        $ret .= "<p><a href='{$FANNIE_URL}mem/MemberEditor.php?memNum={$this->memberBeingEdited}'>" .
            "Return to the main Member Editor page</a></p>";

        return $ret;

    // finish_content()
    }

    /* mode == init. Display the form for entering Fix details.
     */
    function form_content(){
        global $FANNIE_URL;

        if (!empty($this->errors)) {
            return "<p style='font-size:1.2em;'>" . $this->errors . "</p>";
        }

        $ret = "<form action=\"CoopCredJiggerTool.php\" method=\"post\">";

        $ret .= "<p style=\"font-size:1.6em;\">";
        $ret .= "Program: {$this->programName}";
        $ret .= "<br />";
        $ret .= "<span style=\"font-size:0.8em;\">";
        $memNum = FormLib::get_form_value('memIN');
        $ret .= "Member: {$this->name1} - #{$memNum}";
        $ret .= "</span>";
        $ret .= "</p>";

        $ret .= "<p>This form is for fixing problems, usually errors, not for Transfers.";
        $ret .= "<br />The amounts involved do not come from or go back to the " .
            "Program Bank account.</p>";
        $ret .= "<p style=\"font-size:1.2em;\">";
        $ret .= "Fix $ <input type=\"text\" name=\"amount\" size=\"5\" /> ";

        $ret .= "<ul><li>If fixing to <b>reduce</b> the amount the member has to spend,
            <br />prefix it with '-' : <span style='font-weight:bold;'>-10.87</span>";
        $ret .= "<li>If fixing to <b>increase</b> the amount the member has to spend,
            <br />use no prefix : <span style='font-weight:bold;'>14.27</span>";
        $ret .= "</ul></p>";
        $ret .= "<p>";
        $ret .= "<input type=\"submit\" name=\"submit1\" value=\"Fix\" />";
        $ret .= "</p>";
        $ret .= "<p><a href='{$FANNIE_URL}mem/MemberEditor.php?" .
            "memNum={$this->memberBeingEdited}'>" .
            "No Fix: Return to the main Member Editor page</a></p>";
        $ret .= "<input type='hidden' name='dept' value='{$this->programPaymentDepartment}' />";
        $ret .= "<input type='hidden' name='programID' value='{$this->programID}' />";
        $ret .= "<input type=\"hidden\" name=\"memTo\" value=\"{$memNum}\" />";
        $ret .= "<input type=\"hidden\" name=\"memFrom\" value=\"0\" />";
        $ret .= "<input type=\"hidden\" name=\"memEDIT\" value=\"{$this->memberBeingEdited}\" />";
        $ret .= "</form>";

        return $ret;

    // form_content()
    }

    function getTransNo($emp,$register){
        global $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        $q = $dbc->prepare("SELECT max(trans_no) FROM dtransactions WHERE register_no=? AND emp_no=?");
        $r = $dbc->execute($q,array($register,$emp));
        $n = array_pop($dbc->fetch_row($r));
        return (empty($n)?1:$n+1);    
    // getTransNo()
    }

    function doInsert($dtrans,$amount,$department,$cardno){
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
        $defaults['unitPrice'] = $amount;
        $defaults['regPrice'] = $amount;
        $defaults['total'] = $amount;
        if ($amount < 0){
            $defaults['trans_status'] = 'R';
            $defaults['quantity'] = -1;
        }
        $defaults['upc'] = abs($amount).'DP'.$department;

        if (isset($this->depts[$department]))
            $defaults['description'] = $this->depts[$department];
        else {
            $nameP = $dbc->prepare("SELECT dept_name FROM {$OP}departments WHERE dept_no=?");
            $nameR = $dbc->execute($nameP,$department);
            if ($dbc->num_rows($nameR) == 0) {
                $defaults['description'] = 'CORRECTIONS';
            } else {
                $nameW = $dbc->fetch_row($nameR);
                $defaults['description'] = $nameW['dept_name'];
            }
        }

        $q = $dbc->prepare("SELECT memType,Staff FROM {$OP}custdata WHERE CardNo=?");
        $r = $dbc->execute($q,array($cardno));
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
        $prep = $dbc->prepare("INSERT INTO dtransactions ($columns) VALUES ($values)");
        $dbc->execute($prep, $args);

    // doInsert()
    }

    // class CoopCredJiggerTool
}

FannieDispatch::conditionalExec();

