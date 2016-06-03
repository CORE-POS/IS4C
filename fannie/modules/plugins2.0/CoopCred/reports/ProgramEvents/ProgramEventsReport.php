<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
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

/* TO DO
 * 27Feb2015 It is now apparent that there are quite a few things that should
 *            or can be done differently.
 * - For use with a partitioned-table archive:
 *   - Because UNIONing dlogBig and core_trans.dlog may not work for large datasets:
 *     - If core_trans.dlog items are wanted append them to x from a separate query.
 * - Do date formatting in PHP, not MySQL
 * - Trap not-choosing a Program before submit.
 * - Figure out how to $has_menus=0 for a printable report.
 */

include(dirname(__FILE__).'/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ProgramEventsReport extends FannieReportPage {

    public $themed = true;
    public $description = "[Coop Cred Program Report] Events detail: inputs to the Program and payments to members.";

    public $report_set = 'CoopCred';
    protected $title = "Fannie: Coop Cred Program Events: Inputs and Transfers Report";
    protected $header = "Coop Cred Program Events: Inputs and Transfers Report";

    protected $programID = 0;
    protected $programName = "";
    protected $programBankID = 0;
    protected $paymentDepartment = 0;
    protected $programStartDate = "";
    protected $bankerMin = 1;
    protected $bankerMax = 99998;
    protected $bankID = 0;
    protected $errors = array();
    protected $pid = 0;

    function preprocess(){
        global $FANNIE_ROOT,$FANNIE_URL,$FANNIE_PLUGIN_LIST,$FANNIE_PLUGIN_SETTINGS;

        if (!isset($FANNIE_PLUGIN_LIST) || !in_array('CoopCred', $FANNIE_PLUGIN_LIST)) {
            $this->errors[] = "The Coop Cred plugin is not enabled.";
            return True;
        }

        if (array_key_exists('CoopCredDatabase', $FANNIE_PLUGIN_SETTINGS) &&
            $FANNIE_PLUGIN_SETTINGS['CoopCredDatabase'] != "") {
                $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
        } else {
            $this->errors[] = "The Coop Cred database is not assigned in the " .
                "Coop Cred plugin configuration.";
            return True;
        }

        $config = new CCredConfigModel($dbc);
        $config->configID(1);
        $loadOK = $config->load();
        if (!$loadOK) {
            $msg = "Problem: Please 'Configure Coop Cred' from the Coop Cred Admin menu.";
            $this->errors[] = $msg;
            return True;
        } else {
            $this->bankerMin = $config->bankerMin();
            $this->bankerMax = $config->bankerMax();
        }

        /**
          Whether invoked by form submission.
        */
        if (isset($_REQUEST['programID'])){
            // Better to do this in JS in the form.
            if ($_REQUEST['programID'] == "") {
                $this->errors[] = "Please choose a Program";
                $this->add_script("{$FANNIE_URL}src/CalendarControl.js");
                return True;
            }
            $programID = (int)$_REQUEST['programID'];

            $ccpModel = new CCredProgramsModel($dbc);
            $ccpModel->programID($programID);
            $prog = array_pop($ccpModel->find());
            if ($prog != null) {
                $this->programID = $prog->programID();
                $this->programName = $prog->programName();
                $this->programBankID = $prog->bankID();
                //obs. $this->bankID = $prog->bankID();
                $this->paymentDepartment = $prog->paymentDepartment();
                $this->programStartDate =
                    (preg_match("/^[12]\d{3}-\d{2}-\d{2}/",$prog->startDate()))
                    ? $prog->startDate() : '1970-01-01';
            } else {
                $this->errors[] = "Error: Program ID {$programID} is not known.";
                return True;
            }

            if (!FormLib::get_form_value('sortable',False)) {
                $this->sortable = False;
                $this->report_headers = array('When','Member#','Member Name',
                    'Event','$ Amount','Comment');
            } else {
                $this->report_headers = array('Date','When','Member#','Member Name',
                    'Event','$ Amount','Comment');
            }

            $this->content_function = "report_content";

            if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls') {
                $this->report_format = 'xls';
                $this->has_menus(False);
            } elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv') {
                $this->report_format = 'csv';
                $this->has_menus(False);
            }
        } else {
            if (FormLib::get_form_value('pid',0) != 0) {
                $this->pid = FormLib::get_form_value('pid',0);
            }
            $this->add_script("{$FANNIE_URL}src/CalendarControl.js");
        }

        return True;

    // preprocess()
    }

    /* Get data from the database
     * and format it as an HTML table without totals in the last row.
     */
    function fetch_report_data(){

        global $FANNIE_ARCHIVE_METHOD, $FANNIE_ARCHIVE_DB,
            $FANNIE_OP_DB, $FANNIE_TRANS_DB;

        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $card_no = FormLib::get_form_value('card_no','0');

        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
        $OP = $FANNIE_OP_DB . $dbc->sep();
        $TRANS = $FANNIE_TRANS_DB . $dbc->sep();

        // date1='' is program-epoch.
        $date1 = (($date1 == '')?$this->programStartDate:$date1);
        // date2='' is now.
        $date2 = (($date2 == '')?date('Y-m-d'):$date2);

        $dlog = DTransactionsModel::select_dlog($date1,$date2);
        $dte = 'tdate';

        /* If today's transactions are wanted.
          * May not work if archive method is partitions.
         */
        if ($date1 != date('Y-m-d') && $date2 >= date('Y-m-d')) {
            $dlog_spec = " UNION ALL SELECT * FROM {$TRANS}dlog";
            if (substr($dlog,0,1) == '(') {
                $dlog = '(' .
                    trim($dlog,'()') .
                    $dlog_spec .
                    ')';
            } else {
                $dlog = '(SELECT * FROM ' .
                    $dlog .
                    $dlog_spec .
                    ')';
            }
        } else {
            // Should not be a subquery.
            if ($FANNIE_ARCHIVE_METHOD == 'partitions') {
                $dlog = trim($dlog,'()');
            }
        }

        $cardOp = ($card_no == 0)? ">" : "=";
        $query = "SELECT d.card_no,
            d.{$dte},
            DATE_FORMAT(d.{$dte},'%Y %m %d %l:%i') AS 'SortDate',
            DATE_FORMAT(d.{$dte},'%M %e, %Y %l:%i%p') AS 'When',
            CASE WHEN (d.card_no BETWEEN {$this->bankerMin} AND {$this->bankerMax})
                    THEN a.LastName
                    ELSE CONCAT(a.FirstName,' ',a.LastName)
                END AS 'Who',
            trans_status,
            unitPrice,
            quantity,
            total,
            CASE WHEN (d.description != m.dept_name)
                THEN d.description ELSE '' END
                AS 'Comment'
            FROM $dlog d
            LEFT JOIN {$OP}custdata a ON a.CardNo = d.card_no
            LEFT JOIN {$OP}departments m ON m.dept_no = d.department
            WHERE d.department = {$this->paymentDepartment}
              AND ({$dte} BETWEEN ? AND ?)
            ORDER BY DATE_FORMAT(d.{$dte}, '%Y-%m-%d %H:%i')";
        $args = array();
        $args[] = $date1 . ' 00:00:00';
        $args[] = $date2 . ' 23:59:59';

        $prep = $dbc->prepare($query);
        if ($prep === False) {
            $dbc->logger("\nprep failed:\n$query");
        }
        $result = $dbc->execute($prep,$args);
        if ($result === False) {
            $dbc->logger("\nexec failed:\n$query\nargs:",implode(" : ",$args));
        }

        /**
          Build array of results, without totals.
        */
        $ret = array();
        $transferOut = 0;
        $otherOut = 0;
        $rowCount = 0;
        while ($row = $dbc->fetchRow($result)){
            $memberNumber = $row['card_no'];
            $suffix = "";
            if ($row['trans_status'] == 'V') {
                $suffix = " Void";
            }
            /* Refunds: 
             * They are clutter in Bank but not in Member.
             */
            if ($row['trans_status'] == 'R') {
                if ($memberNumber == $this->programBankID) {
                    /* $transferOut is not used.
                    $transferOut += $row['total'];
                     */
                    $suffix = " Refund";
                    continue;
                } else {
                    /* $otherOut is not used.
                    $otherOut += $row['total'];
                     */
                    $suffix = " Reversed";
                }
            }
            $record = array();
            if ($this->sortable) {
                $record[] = $row['SortDate'];
            }
            $record[] = $row['When'];
            $record[] = "<a href='../Activity/ActivityReport.php?" .
                "memNum={$row['card_no']}&amp;programID={$this->programID}'" .
                " target='_CCR_{$row['card_no']}' title='Details for this member'>" .
                "{$row['card_no']}</a>";
            $record[] = "<a href='../Activity/ActivityReport.php?" .
                "memNum={$row['card_no']}&amp;programID={$this->programID}'" .
                " target='_CCR_{$row['card_no']}' title='Details for this member'>" .
                "{$row['Who']}</a>";

            $record[] = (($memberNumber == $this->programBankID)?"Input":"Payment") . $suffix;

            $record[] = sprintf("%.2f",($memberNumber == $this->programBankID)
                                    ? $row['total']
                                    : (-1 * $row['total']));

            $record[] = $row['Comment'];

            $ret[] = $record;
            $rowCount++;
        }

        return $ret;

    // /fetch_report_data()
    }

    /**
      Define any CSS needed
      @return A CSS string
    */
    function css_content(){
    $css =
"p.explain {
    font-family: Arial;
    font-size: 1.0em;
    color: black;
    margin: 0 0 0 0;
}
    p.expfirst {
        margin-top:1.2em;'
    }
    p.exponly {
        margin-bottom:1.2em;'
    }
    ";
    $css .=
"H3.report {
    line-height:1.3em;
    margin-bottom:0.2em;
}
    ";
        return $css;
    }

    /**
      Extra, non-tabular information prefixed to tabular reports
      @return array of strings
     */
    function report_description_content(){
        $ret = array();
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $ret[] = "<H3 class='report'>Events in the " .
            "<br />{$this->programName} " .
            "Program (Dept {$this->paymentDepartment})" .
            "<br />From " .
            (($date1 == '')?"Program Start":$date1) .
            " to " .
            (($date2 == '')?date('Y-m-d'):$date2) .
            "</H3>";
        if (($date1 == $date2) && ($date2 == date('Y-m-d'))) {
            $today_time = date("l F j, Y g:i A");
            $ret[] = "<p class='explain exponly'>As at: {$today_time}</p>";
        } else {
            $today_time = date("l F j, Y g:i A");
            $ret[] = "<p class='explain exponly'>As at: {$today_time}</p>";
        }
        return $ret;
    }

    /**
      Extra, non-tabular information appended to reports
      @return array of strings
    */
    function report_end_content(){
        $ret = array();
        $ret[] = "<p class='explain'><br /><a name='notes'><b>Notes:</b></a></p>";
        $ret[] = "<p class='explain'><b>Balance</b>".
            " is the difference between the the amount that has been" .
            " put into the Program".
            " and the amount that has been distributed to Members.".
            " A positive number is the amount remaining to be distributed." .
            "<br />It is relative to the starting day of the report" .
            " and thus may not be meaningful if the opening balance was not zero." .
            "</p>";
        $ret[] = "<p class='explain'><b><a href='{$_SERVER['PHP_SELF']}?pid=" .
            $this->programID . "'>" .
            "Start again from the form.</a></b></p>";
        return $ret;
    // /report_end_content()
    }

    /**
      Sum the total columns
    */
    function calculate_footers($data){
        $sumProgram = 0.0;
        if ($this->sortable) {
            foreach($data as $row){
                $sumProgram += $row[5];
            }
            return array('Balance',null,null,null,null,number_format($sumProgram,2),'');
        } else {
            foreach($data as $row){
                $sumProgram += $row[4];
            }
            return array('Balance',null,null,null,number_format($sumProgram,2),'');
        }
    }

    /** The form for specifying the report
     */
    function form_content(){

        global $FANNIE_PLUGIN_SETTINGS;

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);
?>
<div id=main>    
<?php
        if (isset($this->errors[0])) {
            echo "<p style='font-family:Arial; font-size:1.5em;'>";
            echo "<b>Errors in previous run:</b>";
            $sep = "<br />";
            foreach ($this->errors as $error) {
                echo "{$sep}$error";
            }
            echo "</p>";
        }
?>
</div><!-- /#main -->

<!-- Bootstrap-coded begins -->
<form method = "get" action="ProgramEventsReport.php" class="form-horizontal">
<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label class="col-sm-4 control-label">Program</label>
            <div class="col-sm-8">
<?php

            echo "<select id='programID' name='programID' class='form-control'>";
            echo "<option value=''>Choose a Program</option>";
            $ccpModel = new CCredProgramsModel($dbc);
            $today = date('Y-m-d');
            foreach($ccpModel->find() as $prog) {
                $desc = $prog->programName();
                if ($prog->active()==0) {
                    $desc .= " (inactive)";
                }
                if ($prog->startDate() > $today) {
                    $desc .= " (Starts {$prog->startDate()})";
                }
                if ($prog->endDate() != 'NULL' && 
                    $prog->endDate() != '0000-00-00' &&
                    $prog->endDate() < $today) {
                    $desc .= " (Ended {$prog->endDate()})";
                }
                printf("<option value='%d'%s>%s</option>",
                    $prog->programID(),
                    ($this->pid == $prog->programID()) ? " SELECTED" : "",
                    $desc
                );
            }
            echo "</select>";
?>
            </div><!-- /.col-sm-8 -->
        </div><!-- /.form-group -->
    </div><!-- /.col-sm-6 end of left col -->
</div><!-- /.row -->

<div class="row">
    <div class="col-sm-6"><!-- start left col -->
        <div class="form-group">
            <label class="col-sm-4 control-label"> </label>
            <div class="col-sm-8">
                <p class="explain" style="float:none; margin:0 0 0 0.5em;">
<span style='font-weight:bold;'>Leave dates empty to report on the whole life of the program.</span>
<br/>The final Balance is relative to the Balance at Date Start .</p>
            </div>
        </div><!-- /.form-group -->
    </div><!-- /.col-sm-6 end of left col -->
</div><!-- /.row -->

<div class="row">
    <div class="col-sm-6"><!-- start left col -->
        <div class="form-group">
            <label class="col-sm-4 control-label">Start Date</label>
            <div class="col-sm-8">
                <input type=text id=date1 name=date1 class="form-control date-field" /><!-- required / -->
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">End Date</label>
            <div class="col-sm-8">
                <input type=text id=date2 name=date2 class="form-control date-field" /><!-- required / -->
            </div>
        </div>
        <div class="form-group">
            <label for="sortable" class="col-sm-4 control-label"
title="Tick to display with sorting from column heads; un-tick for a plain formt."
>Sort on Column Heads</label>
            <input type="checkbox" name="sortable" id="sortable" CHECKED />
        </div>
    </div><!-- /.col-sm-6 -->
    <div class="col-sm-5"><!-- start right col -->
        <div class="form-group">
<?php
            echo FormLib::date_range_picker();
?>                            
        </div>
    </div><!-- /.col-sm-5 -->
</div><!-- /.row -->
<p>
        <button type=submit name=submit value="Create Report" class="btn btn-default">Create Report</button>
        <!-- button type=reset name=reset class="btn btn-default">Start Over</button -->
</p>
            <input type=hidden name=card_no id=card_no value=0  />
</form><!-- /.form-horizontal -->

<!-- Bootstrap-coded ends -->
<?php
    // /form_content()
    }

    // class programReport
}

FannieDispatch::conditionalExec();

