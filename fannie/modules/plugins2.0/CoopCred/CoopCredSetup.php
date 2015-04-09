<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/** Coop Cred Setup - How to Install Coop Cred on Back End and Lanes
*/
class CoopCredSetup extends FanniePage
{

    protected $header = 'Coop Cred - Setup';
    protected $title = 'Coop Cred - Setup';
    protected $auth_classes = array('overshorts');
    public $themed = true;

    function body_content()
    {
        global $FANNIE_URL;
        ob_start();
        ?>

<h3>On the Server (Back End)</h3>
<ul>
    <li>Enable the plugin from the Install > Plugins page.
        <br />N.B. At the moment it is necessary to create the database
        <code>coop_cred</code> BEFORE you enable the plugin.
    </li>
    <li>Configuration
    <br />From the Coop Cred Admin menu run "Configure Coop Cred for this co-op".
This establishes some ranges of values that other setup operations need.
    </li>

    <li>Programs
    <br />Each Program needs:
    <ul>
        <li> A Tender for paying with that kind of Coop Cred
        <li> A Department for purchase of that kind of Coop Cred by the member or on their behalf
        <li> A Banker to hold Inputs to the Program and distribute them to members
        <li> Members, Co-op Members who are registered to use the Program.
    </ul>
    <br />For each Program you intend to set up:
    <ul>
    <li>Set up the Tender in the
<?php echo "<a href='{$FANNIE_URL}admin/Tenders/TenderEditor.php' " .
        "target='_blank'>Tender Editor</a>"; ?>
            <ul>
                <li>It is suggested to use a series such as: 'QA', 'QB', 'QC', ...
                    giving them the same or similar names such as 'COOP CRED TENDER'
                    that suggest they are not in use yet.
                <li>The name can be changed from the Coop Cred Program Editor.
                <li>There is no harm setting up several prior to need.
            </ul>
        </li>

        <li>Set up the Department in the
<?php echo "<a href='{$FANNIE_URL}item/departments/DepartmentEditor.php' " .
        "target='_blank'>Department Editor</a>"; ?>
            <ul>
                <li>It is suggested to use a series of department numbers such as: 1021, 1022, 1023, ...
                initially giving them the same or similar names such as 'COOP CRED DEPARTMENT'
                that suggest they are not in use yet.
                <li>The name can be changed from the Coop Cred Program Editor.
                <li>There is no harm setting up several prior to need.
                <li>Actual names with a prefix such as 'CoopCred' will make them easier to find
                in lists.
            </ul>
        </li>
        <li>Set up the Banker Member for the Program.
            <ul>
                <li>It is suggested to use a series of member numbers such as: 99981, 99982, 99983, ...
                    that are outside the range of regular member numbers
                    and to initially give them the same or similar names such as:
                <br />First Name: MY_COOP
                <br />Last Name: 'COOP CRED BANKER'
                <br />that suggest they are not in use yet.
                <li>The name can be changed from the CORE Member Editor.
                <li>There is no harm setting up several prior to need.
                <li>Use the
<?php echo "<a href='{$FANNIE_URL}mem/NewMemberTool.php' " .
"target='_blank'>Create Members Tool</a>"; ?> to assign member id numbers in a special range as suggested above.
            </ul>
        </li>

        <li>Set up the Program in the
<?php echo "<a href='programs/CoopCredProgramEditor.php' " .
        "target='_blank'>Coop Cred Program Editor</a>"; ?>
        <ul>
            <li>Let overnight processing push the Coop Cred Program table to lanes
                or run the Task <em>Coop Cred Lane Sync</em>.
                </li>
                <li>Subsequent edits will update lanes immediately.
                </li>
        <ul>
        </li>
        <li>Push the CORE tenders, departments and members (custdata) tables to lanes
or wait for automated overnight operations to do it.</li>
        <li><b>On each lane</b>, after any new or renamed tenders and departments are on the lanes,
        <ul>
            <li>Install &gt; Extras &gt; Tender Settings &gt; Tender Mapping &gt; The row for the new tender
            <br /><b>Map To:</b> CoopCred Tender
            <br /><b>Tender Rpt:</b> tick
            <br />Click [Save]
            </li>
            <li>Install &gt; Scanning &gt; Special Department modules
            <br /><b>CCredProgramDepartments:</b> 1021, ####
    <div style="margin-left:2em;">Add the department# to the end of the list, after a comma.</div>
            Click [Save]
            </li>
        </ul>
        </li>
    </ul>
        </ul>
    <!-- //Programs -->

    <li>Membership in Programs
    <ul>
        <li>Set up one or more Coop Cred Programs per the instructions above.</li>
        <li>Enable the CoopCredMember Module in
<?php echo "<a href='{$FANNIE_URL}install/InstallMembershipPage.php' " .
"target='_blank'>Install &gt; Members</a>"; ?>
        <br />and place it where you want it in the sequence of modules.</li>
        <li>If the Banker member doesn't exist yet,
        add it from the regular
        <?php echo "<a href='{$FANNIE_URL}mem/MemberSearchPage.php' " .
        "target='_blank'>CORE Member Editor</a>"; ?>
        <br />It cannot be associated with the Program from there.
        <br />It will be associated with the Program from the Program Editor.
        <br />If the Banker already exists with a dummy name you might want to change
        it to a real name at this point.
        </li>
        <li>Use the Input Utility in the Coop Cred Membership Module
        to endow the Program Bank with money.</li>
        <li>If the people who will use this Program are not
        already entered as Co-op members, enter them
        but do not make them members of the Coop Cred Program until
        they are assigned a real Member Number.
At the West End Food Coop that may be in two hours or perhaps not until the next day.
</li>
        <li>For regular Co-op Members with real member numbers, use the Coop Cred Module of the
        <?php echo "<a href='{$FANNIE_URL}mem/MemberSearchPage.php' " .
        "target='_blank'>Member Editor</a>"; ?>
        to make them members of the Program.
        <br />This will make the membership known on lanes immediately
        but does not put money in the account.
        </li>
        <li>Use the Transfer Utility in the Coop Cred Membership module to transfer money from
        the Program (Bank) account to the Membership.
        <br />If the Coop Cred Task for Updating lanes during the day
        is running it will push
        the balance to lanes and at that point the Coop Cred Member will be
        able to make purchases with Coop Cred.
        <br />If the during-the-day process isn't running but the overnight
        process is, then the member will be able to make purchases the next day.
        </li>
    </ul>
    </li>
    <!-- //Membership -->

    <li>Automated Tasks
    <ul>
        <li>If they are not already exported, add Tenders and Departments to
            the tables exported to the lanes by [nightly.]lanesync.api.php
            or the Task Nightly Lane Sync Using API.
        </li>
        <li>Enable Task <b>Coop Cred History</b>
        and set to run after midnight. 
        </li>
        <li>Enable Task <b>Coop Cred Lane Sync</b>
            and set to run after Coop Cred History Task. 
            <br />The first time this runs it will create the lane-side database
            and tables.
        </li>
        <li>Enable Task <b>Update Coop Cred Lane Balance</b>
            and set to run frequently (every 5 minutes) during business hours.
        </li>
    </ul>
    <!-- //Automated Tasks -->
</li>

<!-- //Server -->
</ul>

<h4>On Each Lane</h4>
<ul>
    <li>In Install &gt; Plugins &gt; Coop Cred Plugin
    <br /><b>Enable</b>
    <br /><b>LaneDatabase:</b> coop_cred_lane
    <div style="margin-left:2em;">Must be the same for all lanes.</div>
    Click [Save]
    </li>

    <li>After the server-side Coop Cred plugin is installed,
        run the server-side Task <b>Coop Cred Lane Sync</b>
        to create and populate the lane-side database and tables.
        <br />If this does not create the database,
            then create the database <code>coop_cred_lane</code> by hand.
    </li>

    <li>In Install &gt; Receipt &gt; Message Modules
    <br />Enable Modules in this order:
    <ul>
        <li>CCredUsedBalances</li>
        <li>CCredSigSlip</li>
    </ul>
    </li>

    <li><code>PrehLib::setAltMemMsg()</code> can be coded to report Coop Cred
    balances on the <code>blueLine</code> when the member is identified.
    </li>
    <li>Operations at cash
        <ul>
            <li><tt>QQ</tt> will pop up a list of Programs the Member belongs to and the balance and status.
            <br />You may want to map a key to it.
            <li><tt>TQ</tt> will pop up a tender list restricted to Coop Cred tenders the Member may use.
            <br />You may want to map a key to it.
        </ul>
    </li>
</ul>

        <?php
        return ob_get_clean();
    }

// class CoopCredSetup
}

FannieDispatch::conditionalExec(false);
