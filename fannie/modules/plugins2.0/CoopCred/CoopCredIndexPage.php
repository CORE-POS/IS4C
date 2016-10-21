<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto, Canada

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CoopCredIndexPage extends FanniePage
{

    protected $header = 'Coop Cred - Admin';
    protected $title = 'Coop Cred - Admin';
    protected $auth_classes = array('admin');
    public $themed = true;

    function body_content()
    {
        global $FANNIE_URL;

        /* $hb (helpbit) is an array keyed on the id= of the input being described.
         * Each element is an array with 2 elements:
         * ['a'] which contains the <a onclick=target-id><img></a>
         * ['t'] containing the <fieldset id=target-id>long help</fieldset>
         */
        $hbPrefix = '_CCIP';
        $hbNumber = 1000;
        $hb = array();
        $hbShortMessage = array();
        $hbLongMessage = array();
        $hbIcon = "{$FANNIE_URL}src/img/buttons/help16.png";
        $hbMore = " " . _("(Click for more)");
        $hbNoMore = "";
        $hbLongWidth = 30; // em

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
        $hbKey = 'prog';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "Add and Edit Programs " .
            "and their settings including activation and suspension." .
            "" .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "" .
            "";

        $hbKey = 'rstatus';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "The amount that has been Transferred " .
            "to members of a Coop Cred Program and the amount they have used " .
            "for purchases." .
            "" .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />Use this report to see detail of members' activity." .
            "" .
            "";

        $hbKey = 'revents';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "The amount that has been Input to " .
            "a Coop Cred Program and the amounts Transferred to its members. " .
            "" .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />Use this report for a summary of the Program. " .
            "<br />It includes a Balance." .
            "";

        $hbKey = 'rliability';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "Use this report to see what the Coop is still " .
            "liable for to Programs and Members." .
            "" .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />The amounts that have been " .
            "Input to Programs, " .
            "Transferred to members of a Program, " .
            "are awaiting transfer to members, " .
            "the amount the members have used for purchases, " .
            "and amounts they have not yet used." .
            "" .
            "";

        $hbKey = 'config';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "Configure Coop Cred to fit in your system. " .
            "" .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />Establish some values and ranges of members and departments " .
            "that will be reserved for Coop Cred and make it easier to establish " .
            "new Programs." .
            "";

        $hbKey = 'ftools';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "Coop Cred uses some regular CORE entities " .
            "such as Tenders, Departments and Members." .
            "" .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />If you are using the Coop Cred Program Manager to set up the Program, " .
            "these entities need to exist before you can use them there." .
            "<br />You can set up entities before you need them, giving them dummy " .
            "names and other values that can be changed when a real Program comes into use." .
            "<br />These tools also give you access to non-Coop Cred entities; " .
            "please be careful and considerate to not change them." .
            "" .
            "";

        $hbKey = 'ttool';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "The Tenders Editor has some unusual features." .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />The Tenders Editor makes all existing Tenders " .
            "available for editing on one page and updates the database " .
            "as soon as your cursor leaves a field. " .
            "There is no 'Save' for all the changes to a Tender, or all Tenders." .
            "<br />There is a link at the bottom of the page to create a new Tender." .
            "<br />Please follow the pattern you chose for Coop Cred Tender codes, " .
            "e.g. that they all start with 'Q'." .
            "" .
            "";

        $hbKey = 'dtool';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "The Deparments Editor." .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />Please follow the pattern you chose for Coop Cred Department codes, " .
            "e.g. that they are in the range 1021 to 1099." .
            "" .
            "";

        $hbKey = 'mtool';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "For regular Members and \"Banks\"." .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />The Members Editor is used for Members of Coop Cred " .
            "Programs and for the \"Bank\" or \"Banker Member\" for each Program." .
            "" .
            "";

        $hbKey = 'mctool';
        $hbKeys[] = $hbKey;
        $hbShortMessage["$hbKey"] = "Especially to create \"Banks\"." .
            "";
        $hbLongMessage["$hbKey"] = "{$hbShortMessage[$hbKey]}" .
            "<br />Use to create a group of records outside the range ".
            "used for regular Program Members." .
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
                $onClick = "onclick=\"$('#{$hbTarget}').toggle();return false;\" ";
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
                "style='display:none; width:{$hbLongWidth}em; " .
                    "margin-bottom:1em; padding:1em; border:thin solid gray;'>" .
                $hbLongMessage["$hbKey"] .
                $helpCloser .
                "</fieldset>";
        }

        ob_start();
        ?>
        <ul>
        <li>Setup
            <ul>
                <li><a href="CoopCredSetup.php"
                    target="_Setup">README: Initial Setup of Coop Cred</a>
                </li>
                <li><a href="programs/CoopCredConfigEditor.php?configno=1"
                    target="_Config">Configure Coop Cred for this co-op </a>
                                <?php echo $hb['config']['a']; ?>
                </li>
                <li><a href="<?php echo $FANNIE_URL; ?>/mem/NewMemberTool.php" target="_MT"
                        >Create Members</a>
                        <?php echo $hb['mctool']['a']; ?>
                </li>
            </ul>
        </li>
        <li>Day-to-day
            <ul>
                <li><a href="programs/CoopCredProgramEditor.php"
                    target="_CCProgEdit">Manage Coop Cred Programs</a>
                                <?php echo $hb['prog']['a']; ?>
                </li>
                <li><a href="reports/ProgramEvents/"
                    target="_CCEvents">Report on Coop Cred - Program Events</a>
                                <?php echo $hb['revents']['a']; ?>
                    </li>
                <li><a href="reports/MemberSummary/"
                    target="_CCMemSumm">Report on Coop Cred - Program Members Summary</a>
                                <?php echo $hb['rstatus']['a']; ?>
                </li>
                <li><a href="reports/Liability/"
                    target="_CCLiability">Report on Coop Cred - Program Liability</a>
                                <?php echo $hb['rliability']['a']; ?>
                </li>
            </ul>
        </li>
        <li>CORE tools for Coop Cred entities
            <?php echo $hb['ftools']['a']; ?>
            <ul>
                <li><a href="<?php echo $FANNIE_URL; ?>/mem/MemberSearchPage.php" target="_ME"
                        >Members Editor</a>
                        <?php echo $hb['mtool']['a']; ?>
                </li>
                <li><a href="<?php echo $FANNIE_URL; ?>/admin/Tenders/" target="_TE"
                        >Tenders Editor</a>
                        <?php echo $hb['ttool']['a']; ?>
                </li>
                <li><a href="<?php echo $FANNIE_URL; ?>/item/departments/DepartmentEditor.php"
                        target="_DE"
                        >Payment Departments Editor</a>
                        <?php echo $hb['dtool']['a']; ?>
                </li>
            </ul>
        </ul>
        <?php

        /* Place for long help messages.
         * Each in its own fieldset or possibly other container.
         * Revealed when 2nd-level help is clicked; hidden when clicked again.
         */
        foreach($hbKeys as $hbKey) {
            if (isset($hbLongMessage["$hbKey"]) && $hbLongMessage["$hbKey"] != "") {
                echo $hb["$hbKey"]['t'];
            }
        }

        return ob_get_clean();
    }

// class CoopCredIndexPage
}

FannieDispatch::conditionalExec(false);

