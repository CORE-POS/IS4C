<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN
    Copyright 2014 West End Food Co-op, Toronto, Canada

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

/* Things TO DO.
 * 21Mar15:
 *  Is usage of getEditJavascript() to load the JS for Ajax correct?.
 *  Detect actual changes, then get and use the modifiedBy value.
 * For bootstrapped v.2 re-code the table with floating divs.
 */

class CoopCredMember extends COREPOS\Fannie\API\member\MemberModule 
{

    protected $regularMemberMin = 1;
    protected $regularMemberMax = 99999;

    protected $pluginHome = 'modules/plugins2.0/CoopCred/';
    protected $pluginName = 'CoopCred';
    protected $moduleHeading = 'Coop Cred Membership';

    public function width()
    {
        return parent::META_WIDTH_HALF;
    }

    /* Compose the block of the form.
        @param memNum Member number
        @param country Isn't used in this block
        @param inProgramID if > -1 is from a call via ajax
            to display the block for a different Program than
            the function would otherwise choose.
     */
    function ShowEditForm($memNum,$country="US",$inProgramID=-1)
    {
        global $FANNIE_URL;
        global $FANNIE_PLUGIN_LIST,$FANNIE_PLUGIN_SETTINGS;

        if (!isset($FANNIE_PLUGIN_LIST) || !in_array('CoopCred', $FANNIE_PLUGIN_LIST)) {
            $msg = "Problem: The '".$this->pluginName."' plugin is not enabled.";
            return $this->FormatReturnMessage($msg);
        }

        if (array_key_exists('CoopCredDatabase', $FANNIE_PLUGIN_SETTINGS) &&
            $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'] != "") {
                $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
        } else {
            $msg = "Problem: Connection to the database for the '".
                $this->pluginName."' plugin failed.";
            return $this->FormatReturnMessage($msg);
        }

        $config = new CCredConfigModel($dbc);
        $config->configID(1);
        $loadOK = $config->load();
        if (!$loadOK) {
            $msg = "Problem: Please 'Configure Coop Cred' from the Coop Cred Admin menu.";
            return $this->FormatReturnMessage($msg);
        } else {
            $this->regularMemberMin = $config->regularMemberMin();
            $this->regularMemberMax = $config->regularMemberMax();
        }

        /* Needs to get:
         * -all programs or
         * -all active programs
         * regardless whether member is in them
         * distinguish the ones he/does belong to by CAPS or colour or something
         * set SELECTED for the first one he/she belongs to, else Choose
         * and show the current balance for that
         * and set the default params for:
         * -transfer
         * -fix
         * -Activity Report
         * to that.
         */

        $programID = 0;
        $programBankNumber = 0;

        /* Get all programs first, then see if the member is in them.
        */
        $progQ = "SELECT programID, programName, bankID
            FROM CCredPrograms
            WHERE active =1
            ORDER BY programName ASC";
        $progS = $dbc->prepare($progQ);
        $progR = $dbc->execute($progS,array());
        $ccred_programs = array();
        $pID = '';  // format: P01
        $firstMpID = ''; // a $pID
        $programCount = 0;
        while ($progW = $dbc->fetch_row($progR)) {
            $programCount++;
            $pID=sprintf("P%02d",$progW['programID']);
            if ($programCount == 1) {
                $firstMpID = $pID;
            }
            $ccred_programs["$pID"] = array(
                'programID' => $progW['programID'],
                'programName' => $progW['programName'],
                'bankID' => $progW['bankID'],
                'creditOK' => 0,
                'membershipID' => 0,
                'balance' => NULL,
                'maxCreditBalance' => 0,
                'isDefault' => 0
            );
        }

        /* Then get balance for the ones the member belongs to.
         * Decide which one will be displayed, the default,
         *  in order of preference.
         *  1. The first one the member belongs to where creditOK is True,
         *      i.e. that he/she can purchase with.
         *  2. The first one he/she belongs to.
         *  3. The first one that exists.
         *  4. Override with parameter $inProgramID.
         *
         */
        $infoQ = "SELECT m.programID, m.cardNo, m.creditOK, m.membershipID,
                    m.maxCreditBalance,
                    b.balance
                FROM CCredMemberships AS m
                JOIN CCredLiveBalance AS b ON m.cardNo = b.cardNo
                    AND m.programID = b.programID
                JOIN CCredPrograms AS p ON m.programID = p.programID
                WHERE m.cardNo =?
                    AND p.active =1";
        $infoS = $dbc->prepare($infoQ);
        $infoR = $dbc->execute($infoS,array($memNum));

        $membershipCount = 0;
        $firstCreditOK = ($inProgramID > -1)?1:0;
        // This may not be getting a real value if Member does not belong to any yet.
        if ($firstMpID == '') {
            // Means there are no Programs
            $pID=sprintf("P%02d", 0);
            $ccred_programs["$pID"] = array(
                'programID' => 0,
                'programName' => "No Programs Exist",
                'bankID' => 0,
                'creditOK' => 0,
                'membershipID' => 0,
                'balance' => NULL,
                'maxCreditBalance' => 0,
                'isDefault' => 1
            );
        } else {
            $pID = $firstMpID;  // format: P01
        }
        $defaultMembership = ($inProgramID > -1)? sprintf("P%02d",$inProgramID) : $pID;
        $firstPID = ''; // a $pID
        while ($infoW = $dbc->fetch_row($infoR)) {
            $membershipCount++;
            $pID = sprintf("P%02d",$infoW['programID']);
            if ($membershipCount == 1) {
                $firstPID = $pID;
            }
            /* ?Details for programs other than the default were gathered
             * for when I was going to compose JS to do the program change.
             */
            $ccred_programs["$pID"]['balance'] = $infoW['balance'];
            $ccred_programs["$pID"]['creditOK'] = $infoW['creditOK'];
            $ccred_programs["$pID"]['maxCreditBalance'] = $infoW['maxCreditBalance'];
            $ccred_programs["$pID"]['membershipID'] = $infoW['membershipID'];
            if ($inProgramID > -1) {
                if ($infoW['programID'] == $inProgramID) {
                    $ccred_programs["$pID"]['isDefault'] = 1;
                    $defaultMembership = $pID;
                    $programID = $ccred_programs["$pID"]['programID'];
                    $programName = $ccred_programs["$pID"]['programName'];
                    $programBankNumber = $ccred_programs["$pID"]['bankID'];
                    $firstCreditOK = 1;
                    continue;
                }
            } else {
                if ($ccred_programs["$pID"]['creditOK'] && $firstCreditOK == 0) {
                    $ccred_programs["$pID"]['isDefault'] = 1;
                    $defaultMembership = $pID;
                    $programID = $ccred_programs["$pID"]['programID'];
                    $programName = $ccred_programs["$pID"]['programName'];
                    $programBankNumber = $ccred_programs["$pID"]['bankID'];
                    $firstCreditOK = 1;
                }
            }
        }
        /* If Member belongs to at least one but none is inputOK yet
         *  default to the first one belonged to.
         */
        if ($membershipCount > 0 && $firstCreditOK == 0) {
                $ccred_programs["$firstPID"]['isDefault'] = 1;
                $defaultMembership = $firstPID;
                $programID = $ccred_programs["$firstPID"]['programID'];
                $programName = $ccred_programs["$firstPID"]['programName'];
                $programBankNumber = $ccred_programs["$firstPID"]['bankID'];
        }

        /* If Member belongs to none $defaultMembership is still "".
         * The formlet can refer to Programs that Member doesn't belong to
         *  but not to values that come from CCredMemberships.
         *  The membership values are dummy.
         */

        $programMemberType = ($memNum == $programBankNumber)?"$programName":"Member";
        $ret = "";

        /* Later, AJAX-driven, calls ($inProgramID > -1) re-populate this <div>
         *  but must not write it again.
         *  CSS is temporary until boostrap coding is done.
         */
        if ($inProgramID == -1) {
            $ret .= "<style type='text/css'>
.MemFormTable th {
    font-size: 75%;
    text-align: right;
    color: #fff;
    padding: 0 5px 0 2px;
    border: solid white 2px;
}
.MemFormTable td {
    padding: 2px 2px 2px 2px;
}
/* The interveneing ccredmemdiv prevents bootstrap's
    * gradient from appearing.
 */
.ccredpanelhead {
    background-color: #f5f5f5;
    border-bottom: 1px solid #ddd;
}
</style>";
            $ret .= "<div class='panel panel-default'>";
            $ret .= "<div id='ccredmemdiv'>";
        }

        $ret .= "<div class=\"panel-heading ccredpanelhead\">Coop Cred - {$programMemberType}"; //</div>";

        $ret .= " <a onclick=\"$('#_fhtt14102004').toggle();return false;\" href=''>" .
            "<img title='Let the Member make purchases against money in an account " .
            "(Click for more)' src='{$FANNIE_URL}src/img/buttons/help16.png'>" .
            "</a>";
        $ret .= "</div><!-- /.panel-heading -->";
        $ret .= "<div class=\"panel-body\">";

        /* For bootstrapped v.1 retain the table coding.
         */
        $ret .= "<table class='MemFormTable' border='0' width='100%'>";

        if ($memNum != $programBankNumber) {
            $ret .= "<tr>";
            $ret .= "<td colspan=3>";
            if ($memNum >= $this->regularMemberMin && $memNum <= $this->regularMemberMax) {
                $ret .= "<select id='ccred_program' name='ccred_program' " .
                   "onchange='memProgramChange();' >";
                $ret .= "<option value='0'>Choose Program</option>";
                foreach($ccred_programs as $pID => $prog ) {
                    $p_sel = (($membershipCount > 0 || 
                        $inProgramID > -1) &&
                        $pID == $defaultMembership)?
                        ' selected="1" ':'';
                    $o_style = ($prog['creditOK']==1) ? "style='font-weight:bold;'" : "";
                    $ret .= "<option value='{$prog['programID']}'{$p_sel}{$o_style}>" .
                        "{$prog['programName']}</option>";
                }
                $ret .= "</select>";
            } else {
                $ret .= " &nbsp; ";
            }
            $ret .= "</td>";
            $ret .= "<th title='The amount of Coop Cred the member has to use.'>Current Balance</th>";
            $ret .= "<th title='The maximum Coop Cred the member may accumulate.'>Max Balance</th>";
            $ret .= "</tr>";
        }

        $ret .= "<tr>";

        $ucc = ($ccred_programs["$defaultMembership"]['creditOK']) ?
            ' checked="" ' : '';
        $uccDisabled = ($memNum == $programBankNumber)?' disabled ':"";
        $ret .= "<th title='Allow/prevent the member&apos;s use of the selected type of Coop Cred.'>Use Coop Cred</th>";
        $ret .= sprintf('<td><input type="checkbox" name="use_coop_cred" %s %s/>
                </td>',$ucc,$uccDisabled);

        // In Coop Cred $limit is always 0.
        $limit = 0;
        $ret .= "<input type='hidden' name='CC_limit' value='{$limit}'>";

        if ($memNum == $programBankNumber) {
            $ret .= "<th title='The amount of Coop Cred the member has to use.'>Current Balance</th>";
        } else {
            $ret .= "<td> &nbsp; </td>";
        }

        $ret .= sprintf('<td id="cbal" name="cbal">%.2f</td>',
                ($ccred_programs["$defaultMembership"]['balance'] * -1));    

        if ($memNum == $programBankNumber) {
            $today = date('Y-m-d');
            $cellContent = "<p style='margin:0em; font-family:Arial;line-height:1.0em;'>
                <a href=\"{$FANNIE_URL}{$this->pluginHome}reports/ProgramEvents/".
                "ProgramEventsReport.php?date1=&amp;date2=&amp;card_no={$memNum}".
                "&amp;sortable=on" .
                "&amp;programID={$programID}\"
                title='List inputs to and payments from the program before today'
                target='_coop_cred_events'
                >Event History</a>
                <br />
                <a href=\"{$FANNIE_URL}{$this->pluginHome}reports/ProgramEvents/".
                "ProgramEventsReport.php?date1={$today}&amp;date2={$today}".
                "&amp;sortable=on" .
                "&amp;other_dates=on&amp;submit=Submit&amp;card_no={$memNum}".
                "&amp;programID={$programID}\"
                title='List inputs to and payments from the program today'
                target='_coop_cred_events'
                >Events Today</a>
                </p>
                ";
        } else {
            $template = '<input type="text" size=8 maxlength=10 id="maxbal" ' .
                'name="maxbal" value="%.2f" >';
            $cellContent = sprintf("$template",
                ($ccred_programs["$defaultMembership"]['maxCreditBalance'] * -1));    
        }
        $ret .= ("<td>" . $cellContent);
        $ret .= "</td>";
        $ret .= "</tr>";

        $ret .= "<tr>";
        if ($memNum == $programBankNumber) {
            $transferTitle = 'Move funds to a member account or return them to ' .
                'the Program Account.';
        } else {
            $transferTitle = 'Get funds for the member from, or return the ' .
                'member&apos;s funds to, the Program Account or another member.';
        }
        if ($programID == 0) {
            $transferTitle .= "\nAdd the member to a Program and Save before " .
                "doing a Transfer.";
        }
        $args1="memIN=$memNum&amp;memEDIT=$memNum&amp;" .
                "programID=$programID";
        $ret .= "<td colspan=\"2\"><a id='tlink' name='tlink'
            href=\"{$FANNIE_URL}{$this->pluginHome}membership/CoopCredTransferTool.php?$args1\"
            title='{$transferTitle}'
            >Transfer Coop Cred</a></td>";

        $args2="memIN=$memNum&amp;memEDIT=$memNum&amp;" .
                "programID=$programID";
        $ret .= "<td><a id='flink' name='flink'
            href=\"{$FANNIE_URL}{$this->pluginHome}membership/CoopCredJiggerTool.php?$args2\"
            title='Fix errors and problems'
            >Fix Coop Cred</a></td>";

        /* In this program only the Program Account may accept inputs.
         * -> Needs to come from Program
         */
        $ret .= "<td colspan='2' style='text-align:center;'>";
        if ($memNum == $programBankNumber) {
            $args3 = $args2;
            $ret .= "<a id='ilink' name='ilink'
                href=\"{$FANNIE_URL}{$this->pluginHome}membership/CoopCredInputTool.php?$args3\"
                title='Input (deposit) external funds to the Program Account'
                >Input Coop Cred</a>";
        } elseif ($memNum >= $this->regularMemberMin && $memNum <= $this->regularMemberMax) {
            $reportLink = "<a id='arlink' name='arlink' " .
                "href=\"{$FANNIE_URL}{$this->pluginHome}reports/" .
                "Activity/ActivityReport.php?" .
                "memNum={$memNum}&amp;programID={$programID}\"" .
                " title='List earnings and purchases for this member'" .
                " target='_blank'" .
                "><p style='margin:0em; font-family:Arial;line-height:1.0em;'>" .
                "Activity Report</p></a>";
            $ret .= $reportLink;
        } else {
            $ret .= " &nbsp; ";
        }
        $ret .= "</td>";
        $ret .= "</tr>";

        $ret .= "</table>";
        //$ret .= "</fieldset>";
        $ret .= "</div><!-- /.panel-body -->";

        if ($inProgramID == -1) {
            /* The fieldset goes in the ccremdiv container. */
            $ret .= "</div> <!-- /ccredmemdiv -->";

            $ret .= '<fieldset id="_fhtt14102004" style="display:none; width:440px;">' .
            "Let the Member make purchases against money in an account. " .
            "<br />A Member may have an account in (be a member of) more than one Program." .
            "<br />'Balance' shows how much is left in the account." .
            "<br />Un-ticking 'OK' will suspend the account when member data is refreshed on lanes. " .
            "<br />Use the 'Transfer' link to add money to the account from the Program Bank." .
            "<br />The Member is allowed to use all accounts in the Program dropdown " .
            "that are in <b>bold</b>." .
            "<br />To add a Member to a Program (allow him/her to use it) " .
            "select the Program, tick 'Use Coop Cred' and click 'Save'" .
            "<!-- br />The Transfer, Fix and Activity links are for the Program that " .
            "is selected when the form initially displays. " .
            "To use Transfer etc. for another Program, temporarily untick 'Use Coop Cred', " .
            "click Save, and Edit the same Member again." .
            "This will expose the next Program the Member belongs to." .
            "<br />After making and Saving the changes, select the initial Program again, " .
            "tick 'Use Coop Cred' and click Save." .
            " -->" .
            "</fieldset>";

            $ret .= sprintf("<input type='hidden' id='memNum' name='memNum' value='%d' />",
                $memNum);
            $ret .= sprintf("<input type='hidden' id='pathTo' name='pathTo' value='%s' />",
                $FANNIE_URL . $this->pluginHome . 'membership/');
            $ret .= "</div><!-- /.panel .panel-default -->";

            $ret .= $this->getEditJavascript();

        }

        return $ret;

    // ShowEditForm()
    }

    /**
      Get any javascript that goes with
      the editing form
      @return [string] javascript
    */
    public function getEditJavascript()
    {
        global $FANNIE_URL;
        $s_type = 'text/javascript';
        $s_url = "{$FANNIE_URL}{$this->pluginHome}membership/" .
            'coopcred_mem_program.js';
        return sprintf('<script type="%s" src="%s"></script>', $s_type, $s_url);
    }

    /* Refers to the program in <select ccred_program>
     * If member already in the program update CCredMemberships.
     * If member not in CCredMemberships for this program
     *  add to CCRedMembers IFF creditOK ticked.
     */
    public function saveFormData($memNum, $json=array())
    {
        global $FANNIE_ROOT;
        //$dbc = $this->db();
        global $FANNIE_URL;
        global $FANNIE_PLUGIN_LIST,$FANNIE_PLUGIN_SETTINGS;

        if (!isset($FANNIE_PLUGIN_LIST) || !in_array('CoopCred', $FANNIE_PLUGIN_LIST)) {
            return '';
        }

        if (array_key_exists('CoopCredDatabase', $FANNIE_PLUGIN_SETTINGS) &&
            $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'] != "") {
                $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
        } else {
            return '';
        }

        // Test probably not necessary.
        if (!class_exists("CCredMembershipsModel")) {
            $dbc->logger("Had to include CCredMembershipsModel");
            include($FANNIE_ROOT . 'modules/plugins2.0/CoopCred/models/CCredMembershipsModel.php');
        }

        // Test probably not necessary.
        if (!class_exists("CCredProgramsModel")) {
            $dbc->logger("Had to include CCredProgramsModel");
            include($FANNIE_ROOT . 'modules/plugins2.0/CoopCred/models/CCredProgramsModel.php');
        }

        // For Coop Cred limit always 0.
        $limit = (int)FormLib::get_form_value('CC_limit',0);
        $credit_ok = (FormLib::get_form_value('use_coop_cred','')!=='' ? 1 : 0);
        $programID = (int)FormLib::get_form_value('ccred_program',0);
        $maxCreditBalance = FormLib::get_form_value('maxbal',0.00);
        if ($maxCreditBalance > 0) {
            $maxCreditBalance = $maxCreditBalance * -1;
        }
        /* For Program Bank members programID is never > 0.
         */
        if ($programID > 0) {
            $ccmModel = new CCredMembershipsModel($dbc);
            $ccmModel->cardNo($memNum);
            $ccmModel->programID($programID);
            /* There should be one or none.
             * find() returns an array, possibly empty, so foreach is needed.
             */
            $test = false;
            $saved = 0;
            foreach($ccmModel->find() as $obj) {
                $saved++;
                $obj->creditOK($credit_ok);
                $obj->creditLimit($limit);
                $obj->maxCreditBalance($maxCreditBalance);
                $now = date('Y-m-d H:i:s');
                $obj->modified($now);
                /*$obj->modifiedBy($auth-user-id);
                 * Like $name = FannieAuth::checkLogin
                 * then modifiedBy = FannieAuth::getUID($name)
                 * Not sure if keeping mod/modBy here is worth it since
                 *  FannieDispatch::logUsage is called by FD::go,
                 *  but not for this class alone.
                 * I only want to log actual changes, which I can't detect yet.
                 */
                $test = $obj->save();
                if ($test === false) {
                    break;
                }
                /* During testing pTLCC writes string error messages to Fannie log.
                 * There doesn't seem to be a mechanism for displaying them
                 *  if they are returned here.
                 */
                $laneTest = $obj->pushToLanesCoopCred();
                if ($laneTest != true) {
                    break;
                }
            }
            /* I.e. was it an update? */
            if ($saved > 0) {
                if ($test === false) {
                    return 'Error: Problem updating Coop Cred membership data.<br />';
                }
            } else {
                // Only add if being enabled.
                if ($credit_ok) {
                    $ccmModel->creditOK($credit_ok);
                    $ccmModel->creditLimit($limit);
                    $ccmModel->maxCreditBalance($maxCreditBalance);
                    $now = date('Y-m-d H:i:s');
                    $ccmModel->modified($now);
                    //$ccmModel->modifiedBy($auth-user-id);
                    /* Get (default) membership values, esp. *OK,
                     *  for fields not entered, from the Program.
                     */
                    $ccpModel = new CCredProgramsModel($dbc);
                    $ccpModel->programID($programID);
                    foreach($ccpModel->find() as $prog) {
                        $ccmModel->inputOK($prog->inputOK());
                        $ccmModel->transferOK($prog->transferOK());
                        break;
                    }
                    $test = $ccmModel->save();
                    if ($test === false) {
                        return 'Error: Problem adding Coop Cred membership data.<br />';
                    }
                    /* During testing pTLCC writes string error messages to Fannie log.
                     * There doesn't seem to be a mechanism for displaying them
                     *  if they are returned here.
                     */
                    $laneTest = $ccmModel->pushToLanesCoopCred();
                    if ($laneTest != true) {
                        return 'Error: Problem adding Coop Cred membership data to lane.<br />';
                    }
                }
            }
        }

        // OK
        return '';

    } // SaveFormData()

    /* Compose a message to return instead of the block of the form.
        @param $msg The message
        @param $heading Optional, a heading other than the default.
     */
    function FormatReturnMessage($msg,$heading='')
    {
        $ret = "";
        $ret .= "<div class='panel panel-default'>";
        $ret .= "<div class='panel-heading '>";
        if (!$heading) {
            $ret .= $this->moduleHeading;
        } else {
            $ret .= $heading;
        }
        $ret .= "</div><!-- /.panel-heading -->";
        $ret .= "<div class='panel-body'>";
        if (substr($msg,0,1) == '<') {
            $ret .= $msg;
        } else {
            $ret .= "<p>{$msg}</p>";
        }
        $ret .= "</div><!-- /.panel-body -->";
        $ret .= "</div><!-- /.panel .panel-default -->";
        return $ret;
    }

// CoopCredMember class
}

