<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
/*
 * 30Oct2014 Cloned from DepartmentEditor.php for Coop Cred Programs
 *
 * Program flow:
 * 1. When called without form variable action=
 *    - body_content() is called to display 
 *      <select id=programselect onchange=programChange()> with
 *      = -1 Create a new Program
 *      =  0 Clear the editing area.
 *      >  0 Existing Programs
 *    - Below it an initially empty and invisible container <div id=infodiv>
 *       which will be filled with the editing form.
 *    - The <select> remains on the page throughout the editing session.
 *      It is/isn't refreshed to include new records created during the session.
 * 2. Clicking one of the options runs JavaScript programChange(), defined in the
 *     companion .js file.
 * 3. Except for the "clear" option, programChange():
 *    - Makes an AJAX request to run the same program:
 *      url: 'CoopCredProgramEditor.php'
 *      - with the key/value pairs:
 *        data: 'pid='+pID+'&action=programDisplay'
 * 4. The PHP program's preprocess() sees that action= is not empty
 *        and passes action= to ajax_response()
 *        which calls ajax_display_program()
 *        which echo's the data capture form for a new or existing program
 *         with a hidden input isnew= indicating whether or not the form contains
 *         a new program, as its final action before exiting.
 *         Control returns to preprocess() which returns False.
 *          draw_page(), which called it, does nothing further.
 * 5. The echo'ed string is returned to JS programChange() as resp
 *        success: function(resp){ $('#infodiv').html(resp); }
 *        populating <div id=infodiv> with the form.
 * 6. When the form's
 *     <input type=submit value=Save onclick="programSave(); return false;">
 *     is clicked, instead of a normal form submission:
 *     - the JS programSave()
 *       - composes a string of key/value pairs from the editing form
 *       - Makes an AJAX request to run the same program:
 *         url: 'CoopCredProgramEditor.php'
 *         with
 *         data: action=programSave and the form key/value pairs, including isnew
 * 7. The PHP program's preprocess() sees that action= is not empty
 *     and passes action= to ajax_response()
 *     which calls ajax_save_program()
 *     which echo's a message about success/failure as its final action before
 *      exiting.
 *      Control returns to preprocess() which returns False.
 *        draw_page(), which called it, does nothing further.
 * 8. The echo'ed string is returned to JS programSave() as resp
 *      success: function(resp){ alert(resp); }
 *      which displays it as a popup dialog box.
 * 9. When the popup is closed the populated form remains and
 *     the (human) editor can:
 *     - continue to edit the same record
 *     - choose to create or edit another from the original <select>
 */

include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CoopCredProgramEditor extends FanniePage {

    public $themed = True;
    protected $title = "Fannie : Manage Coop Cred Programs";
    protected $header = "Manage Coop Cred Programs";
    protected $auth_classes = array('overshorts');
    private $errors = '';
    private $dummyTenderCode;
    private $dummyDepartment;
    private $deptMin;
    private $deptMax;
    private $dummyBanker;
    private $bankerMin;
    private $bankerMax;
    private $authUserNumber;

    function preprocess(){
        global $FANNIE_PLUGIN_LIST, $FANNIE_PLUGIN_SETTINGS;

        if (!isset($FANNIE_PLUGIN_LIST) || !in_array('CoopCred', $FANNIE_PLUGIN_LIST)) {
            $this->errors .= _("Error: The Coop Cred Plugin is not enabled.");
            return True;
        }

        if (!array_key_exists('CoopCredDatabase', $FANNIE_PLUGIN_SETTINGS) ||
            $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'] == "") {
            $this->errors .= _("Error: Coop Cred Database not named in Plugin Settings.");
            return True;
        }

        /* Get values from the Whole-Project (Plugin) config table.
        */
        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
        $config = new CCredConfigModel($dbc);
        $config->configID(1);
        if (!$config->load()) {
            $this->errors .= _("Error: Coop Cred configuration not available.");
            return True;
        }
        $this->dummyTenderCode = $config->dummyTenderCode();
        $this->dummyDepartment = $config->dummyDepartment();
            $this->deptMin = $config->deptMin();
            $this->deptMax = $config->deptMax();
        $this->dummyBanker = $config->dummyBanker();
            $this->bankerMin = $config->bankerMin();
            $this->bankerMax = $config->bankerMax();

        /* For CCredPrograms.modifiedBy
         */
        $this->authUserNumber = 0;
        $authName = FannieAuth::checkLogin();
        if (!($authName == 'null' ||
            $authName == 'init' ||
            $authName == False ))
        {
            $this->authUserNumber = FannieAuth::getUID($authName);
        }


        /* Support ajax calls to this program.
         * If there is a form submission with an action go do it.
         * The form submission may be via AJAX instead of <form ...>
         *  with action= in the query string with other parameters.
         */
        if(FormLib::get_form_value('action') !== ''){
            $this->ajax_response(FormLib::get_form_value('action'));
            /* How to handle errors/problems esp. in save?
             * Possibly code readinessCheck()
             */
            return False;
        }

        /* If the call was not by form, e.g. from the initial menu
         * or the <form action=> is '' (when does that happen?)
         * FanniePage::draw_page() continues to $this->body_content()
         *  which returns the the program-select form.
         */
        return True;

    // preprocess()
    }

    /**
      Define any CSS needed
      @return A CSS string
    public function css_content()
    {
        $css = "";
        return $css;
    }
    */

    /* Handles one of the requests assumed to be AJAX.
     * The output of the handler (responder) is a string which can be HTML
     *  formatted if the ajax caller success: function can deal with it.
     *  In this program, success: either:
     *   - assigns to <div id=infodiv>
     *   - displays with JS alert()
     */
    function ajax_response($action){
        switch($action){
        case 'programDisplay':
            $this->ajax_display_program(FormLib::get_form_value('pid',0));
            break;
        case 'programSave':
            $this->ajax_save_program();
            break;
        default:
            echo 'Bad request';
            break;
        }
    }

    /* Echo the data capture form.
     */
    private function ajax_display_program($id){
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_URL, $FANNIE_OP_DB;

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);

        /* A set of vars with the ?default values for all fields this editor handles.
         */
        $pProgramID = 0;
        $pProgramName = '';
        $pActive = 0;
        $pStartDate = '';
        $pEndDate = '';
        //
        $pTenderType = '';
        $pTenderName = '';
        $pTenderKeyCap = '';
        $pInputTenderType = '';
        //
        $pPaymentDepartment = 0;
        $pPaymentName = '';
        $pPaymentKeyCap = '';
        $pBankID = 0;
        //
        $pCreditOK = 0;
        $pMaxCreditBalance = 0; // 10,2
        $pInputOK = 0;
        $pTransferOK = 0;

        /* Prepare to display existing record.
         * -1 means create new record
         */
        if ($id != -1) {

            $program = new CCredProgramsModel($dbc);
            // Supply key for the record.
            $program->programID($id);
            $program->load();
            // Assuming the lookup/load didn't fail, load values to local vars.
            // First row 
            $pProgramName = $program->programName();
            $pActive = $program->active();
            $pStartDate = $program->startDate();
            $pEndDate = $program->endDate();
            if ($pEndDate == "NULL" || $pEndDate == "0000-00-00") {
                $pEndDate = "";
            }
            // Second row 
            $pTenderType = $program->tenderType();
            $pTenderName = $program->tenderName();
            $pTenderKeyCap = $program->tenderKeyCap();
            $pInputTenderType = $program->inputTenderType();
            // Third row
            $pPaymentDepartment = $program->paymentDepartment();
            $pPaymentName = $program->paymentName();
            $pPaymentKeyCap = $program->paymentKeyCap();
            $pBankID = $program->bankID();
            // Fourth row
            $pCreditOK = $program->creditOK();
            $pMaxCreditBalance = $program->maxCreditBalance();
            $pInputOK = $program->inputOK();
            $pTransferOK = $program->transferOK();

        }

        $OP = $FANNIE_OP_DB.$dbc->sep();

        $tenders = array();
        /* Default for new Programs
         * Can be saved to CCredPrograms but not to tenders.
         * Program cannot be used for e.g. membership assignments until a real
         *  value is set up and chosen, i.e. while this is in use.
         */
        $tenders["{$this->dummyTenderCode}"] = "Dummy Tender";
        $tSize=0;
        if ($id == -1) {
            $pTenderType = $this->dummyTenderCode;
            $pTenderName = $tenders["{$this->dummyTenderCode}"];
            $tSize++;
            $pInputTenderType = 'PI';
        }
        $q = "SELECT TenderCode, TenderName
            FROM {$OP}tenders
            WHERE TenderCode NOT IN ('CA','CK','CC','DC','MI','CX','TV')
            ORDER BY TenderCode";
        $p = $dbc->prepare($q);
        $resp = $dbc->execute($p);
        $tenderRet = "";
        $tSelected = '';
        if ($pTenderType == "{$this->dummyTenderCode}") {
            $tenderRet .= sprintf("<option value='%s'%s>%s %s</option>",
                $pTenderType,
                "selected=''",
                $pTenderType,
                $tenders["$pTenderType"]);
        }
        $iSize=0;
        $iTenders = array();
        $iSelected = '';
        $inputRet = '';
        $tenderStyle=" style='color:black;' ";
        while($row = $dbc->fetch_row($resp)){
            // --> 'Q' to come from project configuration.
            if (substr($row['TenderCode'],0,1) == 'Q') {
                $tSize++;
                $tenders["{$row['TenderCode']}"] = $row['TenderName'];
                $tenderRet .= sprintf("<option %s value='%s'%s>%s %s</option>",
                    $tenderStyle,
                    $row['TenderCode'],
                    $tSelected = ($row['TenderCode'] == $pTenderType)
                        ? " selected=''" : '',
                    $row['TenderCode'],
                    $row['TenderName']);
                if ( $row['TenderCode'] == $pTenderType &&
                    $pTenderName == "") {
                    $pTenderName = $row['TenderName'];
                }
            } else {
                $iSize++;
                $iTenders["{$row['TenderCode']}"] = $row['TenderName'];
                $inputRet .= sprintf("<option value='%s'%s>%s %s</option>",
                    $row['TenderCode'],
                    $iSelected = ($row['TenderCode'] == $pInputTenderType)
                        ? " selected=''" : '',
                    $row['TenderCode'],
                    $row['TenderName']);
            }
        }
        $tSize = ($tSize>4) ? 4 : $tSize;
        $tenderRet = "<select id='tendertype' name='tendertype' size='$tSize'>" .
            $tenderRet . "</select>";
        $iSize = ($iSize>1) ? 1 : $iSize;
        $inputRet = "<select id='inputtender' name='inputtender' size='$iSize'>" .
            $inputRet . "</select>";

        $departments = array();
        /* Default for new Programs
         * Can be saved to CCredPrograms but not to departments.
         * Program cannot be used for e.g. membership assignments until a real
         *  value is set up and chosen, i.e. while this is in use.
         */
        $departments[$this->dummyDepartment] = "Dummy Department";
        $dSize=0;
        if ($id == -1) {
            $pPaymentDepartment = $this->dummyDepartment;
            $pPaymentName = $departments[$this->dummyDepartment];
            $dSize++;
        }
        $q = "SELECT dept_no, dept_name
            FROM {$OP}departments
            WHERE dept_no BETWEEN $this->deptMin AND $this->deptMax
            ORDER BY dept_no";
        $p = $dbc->prepare($q);
        $resp = $dbc->execute($p);
        $departmentRet = "";
        $dSelected = '';
        if ($pPaymentDepartment == $this->dummyDepartment) {
            $departmentRet .= sprintf("<option value='%s'%s>%s %s</option>",
                $pPaymentDepartment,
                "selected=''",
                $pPaymentDepartment,
                $departments[$pPaymentDepartment]);
        }
        while($row = $dbc->fetch_row($resp)){
            $dSize++;
            $departments["{$row['dept_no']}"] = $row['dept_name'];
            $departmentRet .= sprintf("<option value='%s'%s>%s %s</option>",
                $row['dept_no'],
                $dSelected = ($row['dept_no'] == $pPaymentDepartment)
                    ? " selected=''" : '',
                $row['dept_no'],
                $row['dept_name']);
            if ( $row['dept_no'] == $pPaymentDepartment &&
                $pPaymentName == "") {
                $pPaymentName = $row['dept_name'];
            }
        }
        $dSize = ($dSize>4) ? 4 : $dSize;
        $departmentRet = "<select id='paymentid' name='paymentid' size='$dSize'>" .
            $departmentRet . "</select>";

        $bankers = array();
        /* Default for new Programs
         * Can be saved to CCredPrograms but not to custdata.
         * Program cannot be used for e.g. membership assignments until a real
         *  value is set up and chosen, i.e. while this is in use.
         */
        $bankers[$this->dummyBanker] = "Dummy Banker";
        $bSize=0;
        if ($id == -1) {
            $pBankID = $this->dummyBanker;
            $bSize++;
        }
        $q = "SELECT CardNo, CONCAT(FirstName, ' ', LastName) as bname
            FROM {$OP}custdata
            WHERE CardNo BETWEEN $this->bankerMin AND $this->bankerMax
            ORDER BY CardNo";
        $p = $dbc->prepare($q);
        $resp = $dbc->execute($p);
        $bankerRet = "";
        $dSelected = '';
        if ($pBankID == $this->dummyBanker) {
            $bankerRet .= sprintf("<option value='%s'%s>%s %s</option>",
                $pBankID,
                "selected=''",
                $pBankID,
                $bankers[$pBankID]);
        }
        while($row = $dbc->fetch_row($resp)){
            $bSize++;
            $bankers["{$row['CardNo']}"] = $row['bname'];
            $bankerRet .= sprintf("<option value='%s'%s>%s %s</option>",
                $row['CardNo'],
                $dSelected = ($row['CardNo'] == $pBankID)
                    ? " selected=''" : '',
                $row['CardNo'],
                $row['bname']);
        }
        $bSize = ($bSize>4) ? 4 : $bSize;
        $bankerRet = "<select id='bankid' name='bankid' size='$bSize'>" .
            $bankerRet . "</select>";

        /* $hb (helpbit) is an array keyed on the id= of the input being described.
         * Each element is an array with 2 elements:
         * ['a'] which contains the <a onclick=target-id><img></a>
         * ['t'] containing the <fieldset id=target-id>long help</fieldset>
         */
        $hbPrefix = '_CCPE';
        $hbNumber = 1000;
        $hb = array();
        $hbShortMessage = array();
        $hbLongMessage = array();
        $hbIcon = "{$FANNIE_URL}src/img/buttons/help16.png";
        $hbMore = " " . _("(Click for more)");
        $hbNoMore = "";
        $hbLongWidth = 50; // em

        /* Steps for each HelpBit
         */

        /* Steps for each HelpBit
         * Copy this template and fill it in.
        $hbKey = 'foo';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "One " .
            "Two." .
            "" .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />One " .
            "two " .
            "<br /> " .
            "" .
            "";

         */

        $hbKey = 'isactive';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "Tick to let activity in the Program take place; un-tick to suspend.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />Un-ticking will prevent any charges or payments being made in the Program, " .
        "in other words, members will not be able to use it." .
        "<br />It may sometimes be necessary to halt activity in order to deal with a problem." .
        "<br />Un-ticking (suspending) overrides the Start Date." .
        "<br />The box must be ticked for any activity in the Program to take place." .
        "<br />Ticking does not override Start or End Dates." .
        "<br />It may take a few minutes for the change to take effect on the lanes." .
        "<br />The change will not take effect on any lane running in 'Standalone' mode." .
        "" .
        "";

        $hbKey = 'startdate';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The first day on which charges and payments in the Program may be made.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />It is necessary to have a Start Date for activity in the Progream to begin to " .
        "take place. " .
        "<br />If you want to suspend activity in the program un-tick the Active checkbox; " .
        "that will override the Start Date.";
        "<br />Some reports rely on the Start Date so if it is changed to later than when the " .
        "first activity took place the report may not be accurate." .
        "<br />On the other hand, Start Date could be changed to, with care, mark a new epoch " .
        "in the life of the Program." .
        "<br />Format: YYYY-MM-DD" .
        "";

        $hbKey = 'enddate';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The last day on which charges and payments in the Program may be made.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />If you want to suspend activity in the program un-tick the Active checkbox." .
        "<br />If the End Date has passed the Active status will not override it; ".
        "if you want to extend the Program set the End Date to later." .
        "<br />An empty End Date means the Program will run indefinitely." .
        "<br />Format: YYYY-MM-DD" .
        "";

        /* Second row helps
        $ret .= "<th colspan=1>Tender{$hb['tendertype']['a']}</th>";
        $ret .= "<th colspan=1>Tender Name{$hb['tendername']['a']}</th>";
        $ret .= "<th colspan=1>Tender Keycap{$hb['tenderkeycap']['a']}</th>";
        $ret .= "<th colspan=1>Input Tender{$hb['inputtender']['a']}</th>";
         */

        $hbKey = 'tendertype';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The tender that is used for purchases in this Program.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />A tender may only be used in one Program." .
        "<br />If the tender for this Program doesn't exist yet " .
        "you can add it using the " .
        "<a href='{$FANNIE_URL}/admin/Tenders/' target='_TE' " .
            "style='font-size:1.2em; font-weight:bold;'>" .
        "Fannie Tenders Editor</a> " .
        "and then select this Program again for editing " .
        "to make the new Tender appear in the list." .
        "";

        $hbKey = 'tendername';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The name of the tender at left as it will appear on receipts.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />This name will appear with the Tender code in the Tender list." .
        "<br />To see the result in the list after Saving," .
        "select a different Program and then select this one again." .
        "";

        $hbKey = 'tenderkeycap';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "A very short abbreviation for the tender.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />Ideally, short enough to be used on a keyboard keycap." .
        "" .
        "<br />long2";

        $hbKey = 'inputtender';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The tender used for inputs from external sources.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />This is usually the same for all Programs at a co-op." .
        "" .
        "";

        /* Third row helps
        $ret .= "<th colspan=1>Payment{$hb['paymentid']['a']}</th>";
        $ret .= "<th colspan=1>Payment Name{$hb['paymentname']['a']}</th>";
        $ret .= "<th colspan=1>Payment Keycap{$hb['paymentkeycap']['a']}</th>";
        $ret .= "<th colspan=1>Bank{$hb['bankid']['a']}</th>";
         */

        $hbKey = 'paymentid';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The department that is used for payments and other transfers in this Program.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />A payment department may only be used in one Program." .
        "<br />If the department for this Program doesn't exist yet " .
        "you can add it using the " .
        "<a href='{$FANNIE_URL}item/departments/DepartmentEditor.php' target='_DE' " .
            "style='font-size:1.2em; font-weight:bold;'>" .
        "Fannie Deparments Editor</a> " .
        "and then select this Program again for editing " .
        "to make the new Payment Department appear in the list." .
        "";

        $hbKey = 'paymentname';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The name that will appear on reports and receipts.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />This name will appear with the Department code in the " .
            "Input/Payment Department list." .
        "<br />To see the result in the list after Saving," .
        "select a different Program and then select this one again." .
        "" .
        "";

        $hbKey = 'paymentkeycap';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "A very short abbreviation for the department.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />Ideally, short enough to be used on a keyboard keycap." .
        "" .
        "";

        $hbKey = 'bankid';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The pseudo-Member that receives inputs from external sources " .
            "and distributes to Program Members.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />A Banker may only serve one Program." .
        "<br />If the Banker for this Program doesn't exist yet " .
        "you can add it using the " .
        "<a href='{$FANNIE_URL}mem/MemberEditor.php?memNum={$pBankID}' target='_ME' " .
            "style='font-size:1.2em; font-weight:bold;'>" .
        "Members Editor</a> " .
        "and then select this Program again for editing " .
        "to make the new Banker Member appear in the list." .
        "<br />Also use the Member Editor to change the name of the Banker." .
        "";

        /* Fourth row helps
        $ret .= "<th colspan=1>Credit OK{$hb['creditok']['a']}</th>";
        $ret .= "<th colspan=1>Maximum Credit{$hb['maxcredit']['a']}</th>";
        $ret .= "<th colspan=1>Input OK{$hb['inputok']['a']}</th>";
        $ret .= "<th colspan=1>Transfer OK{$hb['transferok']['a']}</th>";
         */

        $hbKey = 'creditok';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The default for whether Members of the Program may purchase with Coop Cred.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />Un-ticking it <i>at the Member level</i> is used to suspend the Member's " .
        "use of the Program." .
        "<br />Changing this only affects new memberships in the Program; " .
        "it does not change existing memberships." .
        "<br />To suspend activity in the Program un-tick the Active checkbox." .
        "" .
        "";

        $hbKey = 'maxcredit';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The default maximum amount of Coop Cred a Member of the Program may have.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />This supports management of fraud." .
        "<br />It may be overridden for each Member." .
        "" .
        "";

        $hbKey = 'inputok';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The default for whether Members may put money into their own accounts.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />For some Programs all amounts must be transferred to Members by the Banker." .
        "<br />It may be overridden for each Member." .
        "<br />Changing this only affects new memberships in the Program; " .
        "it does not change existing memberships." .
        "" .
        "";

        $hbKey = 'transferok';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The default for whether Members of the Program may transfer Coop Cred to another Member.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />It may be overridden for each Member." .
        "<br />Changing this only affects new memberships in the Program; " .
        "it does not change existing memberships." .
        "<br />16Nov2014 It is not supported at this time, has no effect." .
        "" .
        "";

        /* After all the help messages are defined, compose the code that uses them.
         * Insert the ['a'] like: <th>Active{$hb['isactive']['a']}</th>
         * The ['t']s are automatically placed in a certain area of the page.
         */
        $fieldsetStyle = "";
        foreach($hbKeys as $hbKey) {
            $hbTarget = "$hbPrefix" . ++$hbNumber;
            if (isset($hbLongMessage["$hbKey"]) && $hbLongMessage["$hbKey"] != "") {
                $anyMore = $hbMore;
                $onClick = " onclick=\"$('#{$hbTarget}').toggle();return false;\" ";
                $helpCloser = "<br /><a href='' $onClick style='font-weight:bold;'>Close</a>";
            } else {
                $anyMore = $hbNoMore;
                $onClick = "";
            }
            $hb["$hbKey"]['a'] = " <a " .
                $onClick .
                "href=''>" .
                "<img title='{$hbShortMessage["$hbKey"]}{$anyMore}' src='{$hbIcon}'>" .
                "</a>";
            $hb["$hbKey"]['t'] = "<fieldset id='{$hbTarget}' name='$hbTarget' " .
                "class='helpbit' " .
                "style='width:{$hbLongWidth}em;'" .
                ">" .
                $hbLongMessage["$hbKey"] .
                $helpCloser .
                "</fieldset>";
        }

        /* This and similar horrors are to overcome bootstrap
         * until I have time to figure out how to code this
         * in a bootstrap-compliant way.
         */
        $tableStyle = " style='border-spacing:5px; border-collapse: separate;'";
        $ret = "<table id='edtable'{$tableStyle}>";
        // First row of headings.
        $ret .= "<tr class='vTop'>";
        $ret .= "<th>Program ID</th>";
        $ret .= "<th colspan=1>Name</th>";
        $ret .= "<th>Active{$hb['isactive']['a']}</th>";
        $ret .= "<th>Program Start{$hb['startdate']['a']}</th>";
        $ret .= "<th>Program End{$hb['enddate']['a']}</th>";
        $ret .= "</tr>";

        // First row of inputs.
        $ret .= "<tr class='vTop'>";
        $ret .= "<td class='iCenter'>";
        if ($id == -1){
            $ret .= _("Will be assigned upon Save");
            $ret .= "<input type=hidden id=progno name=progno value='' />";
        } else {
            $ret .= $id;
        }
        $ret .= "</td>";
        $ret .= "<td colspan=1 class=''><input type=text maxlength=30 id=progname " .
            "value=\"$pProgramName\" /></td>";

        /* active
         */
        $ret .= "<td class='iCenter'><input type=checkbox id=isactive ".
            ($pActive==1?'checked':'')." /></td>";
        /* startDate
            These alone are not sufficient for the bootstrap implementation.
            '<div class="form-group">' .
            "<input type=text id=startdate name=startdate class='form-control date-field' " .
            "</div>" .
         */
        $ret .= "<td>" .
            "<input type=text size=8 maxlength=10 id=startdate name=startdate " .
                    "onclick='showCalendarControl(this);' " .
            "value='$pStartDate' " .
            "/>" .
            "</td>";
        /* endDate
         */
        $ret .= "<td><input type=text size=8 maxlength=10 id=enddate name=enddate
                    onclick='showCalendarControl(this);'
                    value='$pEndDate' /></td>";
        $ret .= "</tr>";

        /* Second row of headings.
         */
        $ret .= "<tr class='vBottom'>";
        $ret .= "<th colspan=1><br />Tender{$hb['tendertype']['a']}</th>";
        $ret .= "<th colspan=1><br />Tender Name{$hb['tendername']['a']}</th>";
        $ret .= "<th colspan=1 class='headLeft'>Tender Keycap{$hb['tenderkeycap']['a']}</th>";
        $ret .= "<th colspan=1>External Input<br />Tender{$hb['inputtender']['a']}</th>";
        $ret .= "<th colspan=1> &nbsp; </th>";
        $ret .= "</tr>";

        /* Second row of inputs.
         */
        $ret .= "<tr class='vTop'>";
        $ret .= "<td>{$tenderRet}</td>";
        $ret .= "<td><input type=text size=22 maxlength=25 id=tendername " .
            "value=\"$pTenderName\" /></td>";
        $ret .= "<td><input type=text size=1 id=tenderkeycap maxlength=10 " .
            "value=\"$pTenderKeyCap\" /></td>";
        $ret .= "<td>{$inputRet}</td>";
        $ret .= "<td colspan=1> &nbsp; </td>";
        $ret .= "</tr>";

        /* Third row of headings.
         */
        $ret .= "<tr class='vBottom'>";
        $ret .= "<th colspan=1>Input/Payment<br />Department{$hb['paymentid']['a']}</th>";
        $ret .= "<th colspan=1>Input/Payment<br />Department Name{$hb['paymentname']['a']}</th>";
        $ret .= "<th colspan=1>Department Keycap{$hb['paymentkeycap']['a']}</th>";
        $ret .= "<th colspan=2><br />Banker{$hb['bankid']['a']}</th>";
        $ret .= "</tr>";

        /* Third row of inputs.
         */
        $ret .= "<tr class='vTop'>";
        $ret .= "<td>{$departmentRet}</td>";
        $ret .= "<td><input type=text size=22 maxlength=25 id=paymentname " .
            "value=\"$pPaymentName\" /></td>";
        $ret .= "<td><input type=text size=1 id=paymentkeycap maxlength=10 " .
            "value=\"$pPaymentKeyCap\" /></td>";
        $ret .= "<td colspan=2>{$bankerRet}</td>";
        $ret .= "</tr>";

        /* Fourth row of headings.
         */
        $ret .= "<tr class='vTop'>";
        $ret .= "<th colspan=1>Credit OK{$hb['creditok']['a']}</th>";
        $ret .= "<th colspan=1>Maximum Credit{$hb['maxcredit']['a']}</th>";
        $ret .= "<th colspan=1>Input OK{$hb['inputok']['a']}</th>";
        $ret .= "<th colspan=1>Transfer OK{$hb['transferok']['a']}</th>";
        $ret .= "<th colspan=1> &nbsp; </th>";
        $ret .= "</tr>";

        /* Fourth row of inputs.
         */
        $ret .= "<tr class='vTop'>";
        $ret .= "<td class='iCenter'><input type=checkbox id=creditok ".
            ($pCreditOK==1?'checked':'')." /></td>";
        $ret .= sprintf("<td>\$ <input type=text size=5 id=maxcredit value=\"%.2f\" /></td>",
            $pMaxCreditBalance);    
        $ret .= "<td class='iCenter'><input type=checkbox id=inputok ".
            ($pInputOK==1?'checked':'')." /></td>";
        $ret .= "<td class='iCenter'><input type=checkbox id=transferok ".
            ($pTransferOK==1?'checked':'')." /></td>";
        $ret .= "<td colspan=1> &nbsp; </td>";
        $ret .= "</tr>";

        $ret .= "</table>";

        /* Place for long help messages.
         * Each in its own fieldset or possibly other container.
         * Revealed when 2nd-level help is clicked; hidden when clicked again.
         */
        foreach($hbKeys as $hbKey) {
            if (isset($hbLongMessage["$hbKey"]) && $hbLongMessage["$hbKey"] != "") {
                $ret .= $hb["$hbKey"]['t'];
            }
        }


        // Remember that these are also in JS.
        if ($id == -1){
            $ret .= "<input type=hidden id=isnew value=1 />";
        } else {
            $ret .= "<input type=hidden id=isnew value=0 />";
            // id must be the same as in the form, the <select> of programs.
            $ret .= "<input type=hidden id=progno value=\"$id\" />";
            $ret .= "<input type=hidden id='origdepartment' value=\"{$pPaymentDepartment}\" />";
            $ret .= "<input type=hidden id='origtender' value=\"{$pTenderType}\" />";
        }

        /* The onclick= is what happens instead of <form action=> in
         * regular form submissions.
         */
        $ret .= "<p /><input type=submit value=Save onclick=\"programSave(); return false;\" />";

        echo $ret;

    // ajax_display_program()
    }

    /* Save to the tables:
     *  Use Model for the Program.
     *  departments
     *  tenders
     * For call in an AJAX scenario.
     * echo's a message of success or problem which the jQuery .ajax()
     *  treats as return value and displays as a JS alert().
     *  AFAICT the ajax can't or doesn't distinguish success from failure.
     *  I want to be able to tell op to fix things before doing the save.
     *  E.g. tender may not be used by more than one program.
     */
    private function ajax_save_program(){

        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
        $OP = $FANNIE_OP_DB . $dbc->sep();

        /* Place to accumulate not-immediately-fatal messages
         *  that will be displayed in the ajax-return-string.
         */
        $returnMessage = "";

        /* These are from the query (parameters) string in the AJAX request
         *  and are the same as the input form id= values.
         */
        $progno = FormLib::get_form_value('progno',0);
        $progname = FormLib::get_form_value('progname','');
        $isactive = FormLib::get_form_value('isactive',0);
        $startdate = FormLib::get_form_value('startdate','');
        $enddate = FormLib::get_form_value('enddate','');
            if ($enddate == 'NULL' || $enddate == '0000-00-00') {
                $enddate = '';
            }
        $tendertype = FormLib::get_form_value('tendertype','');
        $tendername = FormLib::get_form_value('tendername','');
        $tenderkeycap = FormLib::get_form_value('tenderkeycap','');
        $inputtender = FormLib::get_form_value('inputtender','');

        $paymentid = FormLib::get_form_value('paymentid',0);
        $paymentname = FormLib::get_form_value('paymentname','');
        $paymentkeycap = FormLib::get_form_value('paymentkeycap','');
        $bankid = FormLib::get_form_value('bankid',0);

        $creditok = FormLib::get_form_value('creditok',0);
        $maxcredit = FormLib::get_form_value('maxcredit',0);
        $inputok = FormLib::get_form_value('inputok',0);
        $transferok = FormLib::get_form_value('transferok',0);

        $origTender = FormLib::get_form_value('origtender','');
        $origDepartment = FormLib::get_form_value('origdepartment',0);

        $isnew = FormLib::get_form_value('isnew',9);

        /* 
         * Check for problems, conflicts mostly:
         */
        $sMessage = "";
        /* Each check */
        /* Tender */
        $sErrors = array();
        $sargs = array();
        $sq = "SELECT tenderType, programName
            FROM CCredPrograms 
            WHERE tenderType = ? ";
        $sargs[] = $tendertype;
        if (!$isnew) {
            $sq .= "AND programID != ?";
            $sargs[] = $progno;
        }
        $ss = $dbc->prepare($sq);
        $sr = $dbc->execute($ss,$sargs);
        while ($srow = $dbc->fetch_row($sr)) {
            $sErrors[] = $srow['programName'];
        }
        if (count($sErrors)>0){
            $sMessage .= sprintf("\nThe Tender %s is already in use by:\n", $tendertype);
            $sMessage .= implode("\n",$sErrors);
            $sMessage .= 
                "\nPlease choose a different one." .
                "\nIf the one you need doesn't exist yet,\n" .
                " set it up in the Tenders Editor\n" .
                " and then edit this Program again." .
                "\nAlso see the Tender '?' Help." .
                "";
        }

        /* Payment Department */
        $sErrors = array();
        $sargs = array();
        $sq = "SELECT paymentDepartment, paymentName, programName
            FROM CCredPrograms 
            WHERE paymentDepartment = ? ";
        $sargs[] = $paymentid;
        if (!$isnew) {
            $sq .= "AND programID != ?";
            $sargs[] = $progno;
        }
        $ss = $dbc->prepare($sq);
        $sr = $dbc->execute($ss,$sargs);
        while ($srow = $dbc->fetch_row($sr)) {
            $sErrors[] = $srow['programName'];
        }
        if (count($sErrors)>0){
            $sMessage .= sprintf("\nThe Department %s is already in use by:\n", $paymentid);
            $sMessage .= implode("\n",$sErrors);
            $sMessage .= 
                "\nPlease choose a different one." .
                "\nIf the one you need doesn't exist yet,\n" .
                " set it up in the Departments Editor\n" .
                " and then edit this Program again." .
                "\nAlso see the Department '?' Help." .
                "";
        }

        /* Banker Department */
        $sErrors = array();
        $sargs = array();
        $sq = "SELECT bankID, programName
            FROM CCredPrograms 
            WHERE bankID = ? ";
        $sargs[] = $bankid;
        if (!$isnew) {
            $sq .= "AND programID != ?";
            $sargs[] = $progno;
        }
        $ss = $dbc->prepare($sq);
        $sr = $dbc->execute($ss,$sargs);
        while ($srow = $dbc->fetch_row($sr)) {
            $sErrors[] = $srow['programName'];
        }
        if (count($sErrors)>0){
            $sMessage .= sprintf("\nThe Banker %s is already in use by:\n", $bankid);
            $sMessage .= implode("\n",$sErrors);
            $sMessage .= 
                "\nPlease choose a different one." .
                "\nIf the one you need doesn't exist yet,\n" .
                " set it up in the Members Editor\n" .
                " and then edit this Program again." .
                "\nAlso see the Banker '?' Help." .
                "";
        }

        /* After all checks done.
         */
        if ($sMessage) {
            $sMessage = preg_replace("/^\n+/", "", $sMessage);
            $sMessage .= "\n\nNo current changes have been Saved.";
            echo $sMessage;
            return;
        }

        /* Save changes to or Create the Program proper.
         */
        $model = new CCredProgramsModel($dbc);
        $model->programID($progno);
        $model->programName($progname);
        /* Cannot be active if any dummy's in use. */
        $resetIsactive = False;
        if ($isactive == 1 &&
            ($paymentid == $this->dummyDepartment ||
            $tendertype == "{$this->dummyTenderCode}" ||
            $bankid == $this->dummyBanker)) {
                $resetIsactive = True;
                $isactive = 0;
            }
        $model->active($isactive);
        $model->startDate($startdate);
        $model->endDate($enddate);

        $model->tenderType($tendertype);
        $model->tenderName($tendername);
        $model->tenderKeyCap($tenderkeycap);
        $model->inputTenderType($inputtender);

        $model->paymentDepartment($paymentid);
        $model->paymentName($paymentname);
        $model->paymentKeyCap($paymentkeycap);
        $model->bankID($bankid);

        $model->creditOK($creditok);
        $model->maxCreditBalance($maxcredit);
        $model->inputOK($inputok);
        $model->transferOK($transferok);

        $model->modified(date('Y-m-d H:i:s'));
        $model->modifiedBy($this->authUserNumber);
        /* save() decides UPDATE vs INSERT based on whether programID already
         *  exists.
         */
        $saved = $model->save();

        if ($isnew == 1) {
            if ($saved === False){
                echo 'Error: could not create Program';
                return;
            }

            /* Get the programID that was assigned by auto-increment.
             */
            $newQ = "SELECT LAST_INSERT_ID()";
            $rslt = $dbc->query("$newQ");
            if ( $rslt === False) {
                $msg = sprintf("Failed: %s", $dbc->error());
                echo $msg;
                return;
            }

            $rows = $dbc->num_rows($rslt);
            if ( $rows > 0 ) {
                $row = $dbc->fetch_row($rslt);
                $retVal = $row[0];
            } else {
                $retVal = 0;
            }

            if ( $retVal == 0 ) {
                $msg = "Failed to return new Program id.";
                echo $msg;
                return;
            }
            $progno = $retVal;

            $returnMessage .= sprintf("\nCreated Program %s (#%d)", $progname, $progno);

            /* If there are no dummies, re-create the views.
             */
            if ($paymentid != $this->dummyDepartment &&
                $tendertype != "{$this->dummyTenderCode}" &&
                $bankid != $this->dummyBanker) {

                $rslt = $this->updateViews($dbc);
                if ($rslt !== True) {
                    if (stripos($rslt, "fatal") !== False) {
                        echo $returnMessage . "\n$rslt";
                        return;
                    } else {
                        $returnMessage .= "\n$rslt";
                    }
                }
            }


        } else {
            if ($saved === False){
                echo 'Error: could not save the changes to the Program';
                return;
            } else {
                $returnMessage .= sprintf("\nSaved Program %s (#%d)", $progname, $progno);
            }
            /* During testing pTLCC writes string error messages to Fannie log.
             * All problems are noted in return value.
             */
            $laneTest = $model->pushToLanesCoopCred();
            if ($laneTest === true) {
                $returnMessage .= "\nUpdated lanes.";
            } else {
                if (is_string($laneTest)) {
                    $returnMessage .=
                        sprintf("\nError saving Program %s (#%d) to lanes: %s",
                            $progname, $progno, $laneTest);
                } else {
                    $returnMessage .=
                        sprintf("\nError saving Program %s (#%d) to lanes.",
                            $progname, $progno);
                }
            }
        }

        /* Membership of Banker in Program
         * Create new membership or change Banker to this Program.
         * Check first that Banker has only one membership,
         *  that something has not gone wrong.
         */
        if ($bankid == $this->dummyBanker) {
            $returnMessage .= "\nThe Program may not be actually used until " .
                "a real Banker is assigned.";
        } else {
            $ccmModel = new CCredMembershipsModel($dbc);
            $ccmModel->cardNo($bankid);
            /* Default values for new programs.
             * Should these be configurable? Probably.
             */
            $mCreditBalance = 0.00;
            $mCreditLimit = 0.00;
            $mMaxCreditBalance = 0.00;
            $mCreditOK = 1;
            $mInputOK = 1;
            $mTransferOK = 1;
            //
            $found = 0;
            $memships = array();
            foreach($ccmModel->find() as $obj) {
                $found++;
                $memships[] = $obj->programID(-1);
                /* Keep the values of an existing membership.
                 * If it changes Programs.
                 * Really a good idea?
                 * If they, esp creditBalance, are not 0 catastrophe may ensue!
                 */
                $mCreditBalance = $obj->creditBalance();
                $mCreditLimit = $obj->creditLimit();
                $mMaxCreditBalance = $obj->maxCreditBalance();
                $mCreditOK = $obj->creditOK();
                $mInputOK = $obj->inputOK();
                $mTransferOK = $obj->transferOK();
            }
            if ($found > 1) {
                $msg = "Banker %d is already associated with more than one " .
                    "(in fact %d) Program: %s" .
                    "\nPlease use a different one.";
                echo $returnMessage . sprintf("\n$msg", $bankid, $found, implode(', ',$memships));
                return;
            }
            $ccmModel = new CCredMembershipsModel($dbc);
            $ccmModel->cardNo($bankid);
            $ccmModel->programID($progno);
            $ccmModel->creditBalance($mCreditBalance);
            $ccmModel->creditLimit($mCreditLimit);
            $ccmModel->maxCreditBalance($mMaxCreditBalance);
            $ccmModel->creditOK($mCreditOK);
            $ccmModel->inputOK($mInputOK);
            $ccmModel->transferOK($mCreditOK);
            $ccmModel->isBank(1);
            $ccmModel->modified(date('Y-m-d H:i:s'));
            $ccmModel->modifiedBy($this->authUserNumber);
            $membershipSaved = $ccmModel->save();
            if ($membershipSaved === False) {
                $msg = "Adding or updating membership in Program %d for Banker %d failed.";
                echo $returnMessage . sprintf("\n$msg", $progno, $bankid);
                return;
            }
            /* Membership of Banker in Program
             * Create new membership or change Banker to this Program.
             * Check first that Banker has only one membership,
             *  that something has not gone wrong.
             */
            $ccmModel = new CCredMembershipsModel($dbc);
            $ccmModel->cardNo($bankid);
            /* There should be one or none.
             * find() returns an array, possibly empty, so foreach is needed.
             */
            /* Default values for new programs.
             * Should these be configurable? Probably.
             */
            $mCreditBalance = 0.00;
            $mCreditLimit = 0.00;
            $mMaxCreditBalance = 0.00;
            $mCreditOK = 1;
            $mInputOK = 1;
            $mTransferOK = 1;
            //
            $found = 0;
            $memships = array();
            foreach($ccmModel->find() as $obj) {
                $found++;
                $memships[] = $obj->programID(-1);
                /* Keep the values of an existing membership.
                 * If it changes Programs.
                 * Really a good idea? If they are not 0 catastrophe may ensue!
                 */
                $mProgramID = $obj->programID();
                $mCreditBalance = $obj->creditBalance();
                $mCreditLimit = $obj->creditLimit();
                $mMaxCreditBalance = $obj->maxCreditBalance();
                $mCreditOK = $obj->creditOK();
                $mInputOK = $obj->inputOK();
                $mTransferOK = $obj->transferOK();
            }
            if ($found > 1) {
                $msg = "Banker %d already has more than one (in fact %d) memberships: %s";
                echo $returnMessage .
                    sprintf("\n$msg", $bankid, $found, implode(', ',$memships));
                return;
            }
            $ccmModel = new CCredMembershipsModel($dbc);
            $ccmModel->cardNo($bankid);
            $ccmModel->programID($progno);
            $ccmModel->creditBalance($mCreditBalance);
            $ccmModel->creditLimit($mCreditLimit);
            $ccmModel->maxCreditBalance($mMaxCreditBalance);
            $ccmModel->creditOK($mCreditOK);
            $ccmModel->inputOK($mInputOK);
            $ccmModel->transferOK($mCreditOK);
            $ccmModel->isBank(1);
            $ccmModel->modified(date('Y-m-d H:i:s'));
            $ccmModel->modifiedBy($this->authUserNumber);
            $membershipSaved = $ccmModel->save();
            if ($membershipSaved === False) {
                $msg = "Adding or updating membership in Program %d for Banker %d failed.";
                $returnMessage .= sprintf("\n$msg", $progno, $bankid);
            }
        // not dummyBanker
        }

        /* #'u Updates to Fannie tables
         */
        if ($tendertype == $this->dummyTenderCode) {
            $returnMessage .= "\nThe Program may not be actually used until " .
                "a real Tender is assigned.";
        } else {
            if ($tendername != "") {
                $opQ = "UPDATE {$OP}tenders
                    SET TenderName = ?
                    WHERE TenderCode = ?";
                $opArgs = array();
                $opArgs[] = $tendername;
                $opArgs[] = $tendertype;
                $opS = $dbc->prepare($opQ);
                $opR = $dbc->execute($opS,$opArgs);
                if ($opR === False) {
                    $returnMessage .= "\nSaved changes to the Program proper but updating the " .
                        "Tender name in CORE Backend failed.";
                }
            }
        }

        if ($paymentid == $this->dummyDepartment) {
            $returnMessage .= "\nThe Program may not be actually used until " .
                "a real Input/Payment is assigned.";
        } else {
            if ($paymentname != "") {
                $opQ = "UPDATE {$OP}departments
                    SET dept_name = ?
                    WHERE dept_no = ?";
                $opArgs = array();
                $opArgs[] = $paymentname;
                $opArgs[] = $paymentid;
                $opS = $dbc->prepare($opQ);
                $opR = $dbc->execute($opS,$opArgs);
                if ($opR === False) {
                    $returnMessage .= "\nSaved changes to the Program proper but updating the " .
                        "Department name in CORE Backend failed.";
                }
            }
        }

        /* Update views if Department or Tender have changed and
         *  as long as neither is dummy.
         */
        if ($paymentid != $origDepartment ||
            $tendertype != $origTender) {
            if ($paymentid != $this->dummyDepartment &&
                $tendertype != "{$this->dummyTenderCode}" &&
                $bankid != $this->dummyBanker) {

                $rslt = $this->updateViews($dbc);
                if ($rslt !== True) {
                    if (stripos($rslt, "fatal") !== False) {
                        echo $returnMessage . "\n$rslt";
                        return;
                    } else {
                        $returnMessage .= "\n$rslt";
                    }
                } else {
                    $returnMessage .= "\nUpdated views.";
                }
            }
        }

        if ($resetIsactive) {
            $returnMessage .=
                "\nThe Program has been marked Not-Active because " .
                "one or more dummy values are in use.";
        }
        $returnMessage = preg_replace("/^\n+/","",$returnMessage);
        echo $returnMessage;

    // ajax_save_program()
    }

    /**
     * Override FanniePage::errorContent().
     * Body of a page to display instead of body_content
     * $this->error_text is also used for smaller HTML-formatted messages.
    public function errorContent()
    {
        return $this->error_text;
    }
     */

    /**
     * Override FanniePage::readinessCheck()
     * Check if there are any problems
     * that might prevent the page from working properly.
     * If there are, return False,
     *  which will cause draw_page() to call errorContent()
     *  instead of body_content().
     *  There are two helper functions in FanniePage:
     *   tableExistsReadinessCheck($database, $table)
     *   tableHasColumnReadinessCheck($database, $table, $column)
    public function readinessCheck()
    {
        return true;
    }
    */

    /**
      Define the main displayed content
      @return An HTML string
      For this script the page consists of two horizontal sections.
       above, a fixed select of Programs
       below, an area that is populated by JS with the editing form
        and possibly other art.
    */
    function body_content(){

        global $FANNIE_PLUGIN_SETTINGS;
        global $FANNIE_URL;

        if (!empty($this->errors)) {
            return "<p style='font-size:1.2em;'>" . $this->errors . "</p>";
        }

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);

        $q = "SELECT programID, programName
                FROM CCredPrograms
                ORDER BY programID";
        $p = $dbc->prepare($q);
        $resp = $dbc->execute($p);
        if ($resp === false) {
            return "<p style='font-size:1.2em;'>" . 
               "Exec SQL failed: $q" . "</p>";
        }
        $programs = "<option value=0>Select a Program ...</option>";
        $programs .= "<option value=-1 style='text-decoration:underline;'>" .
            "Create a new Program</option>";
        $selectedPID = FormLib::get_form_value('pid');
        $listSize = 2;
        while($row = $dbc->fetch_row($resp)){
            if ($listSize < 4) {
                $listSize++;
            }
            if ($selectedPID !== '' && $selectedPID == $row['programID']) {
                $programs .= "<option value={$row['programID']} selected>" .
                    "{$row['programName']}</option>";
            } else {
                $programs .= "<option value={$row['programID']}>" .
                    "{$row['programName']}</option>";
            }
        }
        ob_start();
        ?>
        <div id="programdiv" style="padding:1.0em 0 0 0;">
        <div style="float:left; margin-right:0.5em;"><b>Program</b></div>
        <!-- With select size=2 some options are skipped over in the scrolling. -->
        <select id="programselect" onchange="programChange();"
        size="<?php echo $listSize ?>">
        <?php echo $programs ?>
        </select>
        </div>
        <p style="clear: both;">To refresh the list above, save your current edits, then
        <a href="CoopCredProgramEditor.php"
            style="font-size:1.2em; font-weight:bold;">Refresh</a></p>
        <hr />
        <!-- infodiv is filled with the editing form. -->
        <div id="infodiv"></div>
        <?php
        $this->add_script('CalendarControl.js');
        $this->add_css_file('CalendarControl.css');
        $this->add_script('coopcred_program.js');
        $this->add_css_file('coopcred_form.css');
        /* Does this run JS programChange()? To display an initial Program?
        if ($selectedPID !== '') {
            //  It seems to get the programID from the page, not passed as arg.
            $this->add_onload_command('programChange();');
            $noop=1;
        }
         */

        return ob_get_clean();

    // body_content()
    }

    /* Re-create the Views that depend on currently-in-use tenders and
     *  departments.
     * Return True on success, string message otherwise.
     */
    function updateViews ($dbc) {
        global $FANNIE_PLUGIN_SETTINGS;

        $models = array(
            array('name' => 'CCredHistoryTodayModel', 'drop' => True)
            ,array('name' => 'CCredHistoryTodaySumModel', 'drop' => True)
            ,array('name' => 'CCredLiveBalanceModel', 'drop' => True)
            ,array('name' => 'CCredMemCreditBalanceModel', 'drop' => True)
        );
        $dropAllViews =
            (array_key_exists('CoopCredDropAllViews',$FANNIE_PLUGIN_SETTINGS)) ?
            $FANNIE_PLUGIN_SETTINGS['CoopCredDropAllViews'] : False;

        foreach($models as $model){
            $model_class = $model['name'];
            $filename = dirname(__FILE__).'../models/vmodels/'.$model_class.'.php';
            if (!class_exists($model_class)) {
                include_once($filename);
            }
            $instance = new $model_class($dbc);
            /* For views the view should be dropped if it exists
             *  because some of them depend on changes in CCredPrograms.
             *  isView() is in $dbc, true only if the view exists
             */
            $view = $instance->name();
            if ($dbc->isView($view)) {
                // drop() a better name for the function, like create()
                // ->suggest alias drop()
                if ($dropAllViews || $model['drop']) {
                    $try = $instance->delete();
                    if ($try) {
                        $msg="Dropped view $view prior to re-creating.";
                        $dbc->logger("$msg");
                    } else {
                        $msg="Failed to drop view $view prior to re-creating. " .
                            "Will not try to re-create.";
                        $dbc->logger("$msg");
                        continue;
                    }
                } else {
                    $msg="Not dropping existing view $view prior to re-creating. " .
                        "Will not try to re-create.";
                    $dbc->logger("$msg");
                    continue;
                }
            }
            $try = $instance->create();        
            if ($try) {
                $msg="Created view $view specified in {$model_class}";
                $dbc->logger("$msg");
            } else {
                $msg="Failed to create view specified in {$model_class}";
                $dbc->logger("$msg");
            }
            /* Generate the accessor function code for each column.
             * The Model file must be writable by the webserver user.
            */
            if (is_writable($filename)) {
                $try = $instance->generate($filename);
                if ($try) {
                    $dbc->logger("[Re-]Generated $model_class accessor functions.");
                } else {
                    $dbc->logger("Failed to [re-]generate $model_class accessor functions.");
                }
            } else {
                $dbc->logger("Could not [re-]generate $model_class accessor functions " .
                    "because the model-file is not writable by the webserver user.");
            }
        // views
        }

        return True;

    // updateViews()
    }

// CoopCredProgramEditor class
}

FannieDispatch::conditionalExec();

