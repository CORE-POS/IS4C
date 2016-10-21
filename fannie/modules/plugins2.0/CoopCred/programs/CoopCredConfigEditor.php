<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op
    Copyright 2015 West End Food Co-op, Toronto

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
 * 20Feb2015 Cloned from CoopCredProgramEditor.php for Coop Cred Configuration
 *           See the notes in CoopCredProgramEditor.php re program flow.
 */
include(dirname(__FILE__).'/../../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CoopCredConfigEditor extends FanniePage {

    public $themed = True;
    protected $title = "Fannie : Configure Coop Cred";
    protected $header = "Configure Coop Cred";
    protected $auth_classes = array('overshorts');
    private $errors = '';
    private $authUserNumber;
    private $first;

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
        $this->first = 0;
        if (FormLib::get_form_value('action') == ''){
            $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
            $config = new CCredConfigModel($dbc);
            $id = (FormLib::get_form_value('configno') != '') ?
                (int)FormLib::get_form_value('configno') : 1;
            $config->configID($id);
            if (!$config->load()) {
                // Create the initial config record with default values.
                $saved = $config->save();
                if ($saved === False){
                    $this->errors .= _("Error: Could not create initial " .
                        "Coop Cred configuration record.");
                    return True;
                }
            }
            $this->first = 1;
        }

        $this->authUserNumber = 0;
        $authName = FannieAuth::checkLogin();
        if (!($authName == 'null' ||
            $authName == 'init' ||
            $authName == False ))
        {
            $this->authUserNumber = FannieAuth::getUID($authName);
        }

        /* The first (unless this is an update) time proceed directly to edit.
         */
        if ($this->first) {
            return True;
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
            $this->ajax_display_program(FormLib::get_form_value('configno',0));
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

        /* A set of vars for all fields this editor handles.
         */
        $cConfigID = 0;
        $cDummyTenderCode = '';
        $cDummyDepartment = 0;
        $cDeptMin = 0;
        $cDeptMax = 0;
        //
        $cDummyBanker = 0;
        $cBankerMin = 0;
        $cBankerMax = 0;
        //
        $cRegularMemberMin = 0;
        $cRegularMemberMax = 0;
        //
        $cModified = '';
        $cModifiedBy = 0;

        /* Prepare to display existing record.
         * -1 means create new record
         */
        if ($id != -1) {

            $config = new CCredConfigModel($dbc);
            $config->configID($id);
            $loadOK = $config->load();
            // Assuming the lookup/load didn't fail, load values to local vars.
            // First row 
            $cDummyTenderCode = $config->dummyTenderCode();
            $cDummyDepartment = $config->dummyDepartment();
            $cDeptMin = $config->deptMin();
            $cDeptMax = $config->deptMax();
            //
            $cDummyBanker = $config->dummyBanker();
            $cBankerMin = $config->bankerMin();
            $cBankerMax = $config->bankerMax();
            //
            $cRegularMemberMin = $config->regularMemberMin();
            $cRegularMemberMax = $config->regularMemberMax();
            //
            $cModified = $config->modified();
            $cModifiedDatestamp = $cModified;
            $cModifiedBy = $config->modifiedBy();

        }

        /* $hb (helpbit) is an array keyed on the id= of the input being described.
         * Each element is an array with 2 elements:
         * ['a'] which contains the <a onclick=target-id><img></a>
         * ['t'] containing the <fieldset id=target-id>long help</fieldset>
         */
        $hbPrefix = '_CCCE';
        $hbNumber = 1000;
        $hb = array();
        $hbShortMessage = array();
        $hbLongMessage = array();
        $hbIcon = "{$FANNIE_URL}src/img/buttons/help16.png";
        $hbMore = " " . _("(Click for more)");
        $hbNoMore = "";
        $hbLongWidth = 50; // em

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

        /* First row helps
         */
        $hbKey = 'dummyTenderCode';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "A placeholder Tender Code until the real Tender Code is assigned.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />You might want to pre-populate a range of tenders ".
        "to make it easier to set up new Programs." .
        "" .
        "";

        $hbKey = 'dummyDepartment';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "A placeholder Payment Department Code until the real Department Code is assigned.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "" .
        "";

        $hbKey = 'deptMin';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The low end of the range of payment department numbers reserved for Coop Cred.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />You might want to pre-populate at least part of this range ".
        "to make it easier to set up new Programs." .
        "" .
        "";

        $hbKey = 'deptMax';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The high end of the range of payment department numbers reserved for Coop Cred.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "" .
        "";

        /* Second row helps
         */

        $hbKey = 'dummyBanker';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "A placeholder Banker Member ID until the real Banker ID is assigned.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "" .
        "";

        $hbKey = 'bankerMin';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The low end of the range of member numbers reserved for Coop Cred Bankers.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "<br />You might want to pre-populate at least part of this range ".
        "to make it easier to set up new Programs." .
        "";

        $hbKey = 'bankerMax';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The high end of the range of member numbers reserved for Coop Cred Bankers.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "" .
        "";

        $hbKey = 'regularMemberMin';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The low end of the range of member numbers for Regular Coop Cred members.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "" .
        "";

        $hbKey = 'regularMemberMax';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] =
            "The high end of the range of member numbers for Regular Coop Cred members.";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
        "" .
        "";


        /* After all the help messages are defined, compose the code that uses them.
         * Insert the ['a'] like: <th>Active{$hb['isactive']['a']}</th>
         * The ['t']s are automatically placed in a certain area of the page.
         */
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
        $ret .= "<th>Config ID</th>";
        $ret .= "<th>Dummy Tender{$hb['dummyTenderCode']['a']}</th>";
        $ret .= "<th>Dummy Department{$hb['dummyDepartment']['a']}</th>";
        $ret .= "<th>First Department{$hb['deptMin']['a']}</th>";
        $ret .= "<th>Last Department{$hb['deptMax']['a']}</th>";
        $ret .= "</tr>";

        // First row of inputs.
        $ret .= "<tr class='vTop'>";
        $ret .= "<td class='iCenter'{$edtd}>";
        /* Not editable.
         * Don't think there will ever be more than one.
         * Maybe no point in displaying.
         */
        if ($id == -1){
            $ret .= _("Will be assigned upon Save");
            $ret .= "<input type=hidden id=configno name=configno value='' />";
            // id must be the same as in the hidden.
            //$ret .= "<input type=text size=4 id=progno />";
        } else {
            $ret .= $id;
        }
        $ret .= "</td>";
        // dummyTenderCode
        $ret .= "<td colspan=1><input type=text size=2 maxlength=2 id=dummytender " .
            "value=\"$cDummyTenderCode\" /></td>";

        /* dummyDepartment
         * If it exists, should that be noted, name displayed?
         */
        $ret .= "<td colspan=1><input type=text size=4 maxlength=5 id=dummydept " .
            "value=\"$cDummyDepartment\" /></td>";

        /* deptMin
         * If it exists, should that be noted, name displayed?
         */
        $ret .= "<td colspan=1><input type=text size=4 maxlength=5 id=deptmin " .
            "value=\"$cDeptMin\" /></td>";

        /* deptMax
         * If it exists, should that be noted, name displayed?
         */
        $ret .= "<td colspan=1><input type=text size=4 maxlength=5 id=deptmax " .
            "value=\"$cDeptMax\" /></td>";

        $ret .= "</tr>";

        /* Second row of headings.
         */
        $ret .= "<tr class='vBottom'>";
        $ret .= "<th>Dummy Banker{$hb['dummyBanker']['a']}</th>";
        $ret .= "<th>First Banker{$hb['bankerMin']['a']}</th>";
        $ret .= "<th>Last Banker{$hb['bankerMax']['a']}</th>";
        $ret .= "<th>First Member{$hb['regularMemberMin']['a']}</th>";
        $ret .= "<th>Last Member{$hb['regularMemberMax']['a']}</th>";
        $ret .= "</tr>";

        /* Second row of inputs.
         */
        $ret .= "<tr class='vTop'>";

        /* dummyBanker
         * If it exists, should that be noted, name displayed?
         */
        $ret .= "<td colspan=1><input type=text size=5 maxlength=5 id=dummybanker " .
            "value=\"$cDummyBanker\" /></td>";

        /* bankerMin
         * If it exists, should that be noted, name displayed?
         */
        $ret .= "<td colspan=1><input type=text size=5 maxlength=5 id=bankermin " .
            "value=\"$cBankerMin\" /></td>";

        /* bankerMax
         * If it exists, should that be noted, name displayed?
         */
        $ret .= "<td colspan=1><input type=text size=5 maxlength=5 id=bankermax " .
            "value=\"$cBankerMax\" /></td>";
            "value=\"$cDummyBanker\" /></td>";

        /* regularMemberMin
         * If it exists, should that be noted, name displayed?
         */
        $ret .= "<td colspan=1><input type=text size=5 maxlength=5 id=membermin " .
            "value=\"$cRegularMemberMin\" /></td>";

        /* regularMemberMax
         * If it exists, should that be noted, name displayed?
         */
        $ret .= "<td colspan=1><input type=text size=5 maxlength=5 id=membermax " .
            "value=\"$cRegularMemberMax\" /></td>";

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
            $ret .= "<input type=hidden id=configno value=\"$id\" />";
        }

        /* The onclick= is what happens instead of <form action=> in
         * regular form submissions.
         */
        $ret .= "<p /><input type=submit value=Save onclick=\"programSave(); return false;\" />";

        echo $ret;

    // ajax_display_program()
    }

    /* Save to the tables:
     *  Use Model for the Program, i.e. config.
     * For call in an AJAX scenario.
     * echo's a message of success or problem which the jQuery .ajax()
     *  treats as return value and displays as a JS alert().
     *  AFAICT the ajax can't or doesn't distinguish success from failure.
     *  I want to be able to tell op to fix things before doing the save.
     */
    private function ajax_save_program(){

        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_OP_DB;

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
        $OP = $FANNIE_OP_DB . $dbc->sep();

        /* Place to accumulate not-immediately-fatal messages
         *  that will be displayed in the ajax-return-string.
         * Maybe better as array.
         */
        $returnMessage = "";

        /* These are from the query (parameters) string in the AJAX request
         *  and are the same as the input form id= values.
         */
        $configno = FormLib::get_form_value('configno',0);
        $dummytender = FormLib::get_form_value('dummytender','');
        $dummydept = FormLib::get_form_value('dummydept',0);
        $deptmin = FormLib::get_form_value('deptmin',0);
        $deptmax = FormLib::get_form_value('deptmax',0);
        $dummybanker = FormLib::get_form_value('dummybanker',0);
        $bankermin = FormLib::get_form_value('bankermin',0);
        $bankermax = FormLib::get_form_value('bankermax',0);
        $membermin = FormLib::get_form_value('membermin',0);
        $membermax = FormLib::get_form_value('membermax',0);

        $isnew = FormLib::get_form_value('isnew',9);

        /* Check for problems.
         * See CoopCredProgramEditor for examples.
         */
        $sMessage = "";
        /* Each check */

        /* After all checks done.
         */
        if ($sMessage) {
            $sMessage = preg_replace("/^\n+/", "", $sMessage);
            $sMessage .= "\n\nNo current changes have been Saved.";
            echo $sMessage;
            return;
        }

        /* Save changes to or Create the Config proper.
         */
        $config = new CCredConfigModel($dbc);
        $config->configID($configno);

        $config->dummyTenderCode($dummytender);
        $config->dummyDepartment($dummydept);
        $config->deptMin($deptmin);
        $config->deptMax($deptmax);
        //
        $config->dummyBanker($dummybanker);
        $config->bankerMin($bankermin);
        $config->bankerMax($bankermax);
        //
        $config->regularMemberMin($membermin);
        $config->regularMemberMax($membermax);
        //
        $config->modifiedBy($this->authUserNumber );
        $config->modified(date('Y-m-d H:i:s'));

        /* save() decides UPDATE vs INSERT based on whether configID already
         * exists.
         */
        $saved = $config->save();

        if ($isnew == 1) {
            if ($saved === False){
                echo 'Error: could not create Configuration';
                return;
            }

        } else {
            if ($saved === False){
                echo 'Error: could not save the changes to the Configuration';
                return;
            } else {
                $returnMessage .= sprintf("\nSaved Configuration (#%d)", $configno);
            }
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

        $q = "SELECT configID
                FROM CCredConfig
                ORDER BY configID";
        $p = $dbc->prepare($q);
        $resp = $dbc->execute($p);
        if ($resp === false) {
            return "<p style='font-size:1.2em;'>" . 
               "Exec SQL failed: $q" . "</p>";
        }
        $programs = "<option value=0>Select Configuration ...</option>";
        $selectedPID = FormLib::get_form_value('configno',1);
        $listSize = 2;
        while($row = $dbc->fetch_row($resp)){
            if ($listSize < 4) {
                $listSize++;
            }
            if ($selectedPID !== '' && $selectedPID == $row['configID']) {
                $programs .= "<option value={$row['configID']} selected>" .
                    "{$row['configID']}</option>";
            } else {
                $programs .= "<option value={$row['configID']}>" .
                    "{$row['configID']}</option>";
            }
        }
        ob_start();
        ?>
        <!-- Code for choosing a Config commented out since there is only one. -->
        <!-- div id="programdiv" style="padding:1.0em 0 0 0;">
        <div style="float:left; margin-right:0.5em;"><b>Configuration</b></div>
        <select id="configselect" onchange="programChange();"
        size="<?php //echo $listSize ?>" -->
        <?php //echo $programs ?>
        <!-- /select>
        </div>
        <p style="clear: both;">To refresh the list above, save your current edits, then
        <a href="CoopCredConfigEditor.php"
            style="font-size:1.2em; font-weight:bold;">Refresh</a></p>
        <hr / -->
        <input type="hidden" id="configselect" name="configselect" value="1" />
        <!-- infodiv is filled with the editing form.  -->
        <div id="infodiv">
        <?php
        if ($this->first) {
            $this->ajax_response('programDisplay');
            }
        ?>
        </div>
        <?php
        //$this->add_script($FANNIE_URL . 'src/CalendarControl.js');
        $this->add_script('coopcred_config.js');
        $this->add_css_file('coopcred_form.css');
        /* Does this run programChange()?
         * To display an, i.e. the, initial config.
         *   If so might be a better way than what I did.
        if ($selectedPID !== '') {
             *  It seems to get the programID from the page, not passed as arg.
            $this->add_onload_command('programChange();');
            $noop=1;
        }
         */

        return ob_get_clean();

    // body_content()
    }


// CoopCredConfigEditor class
}

FannieDispatch::conditionalExec();

