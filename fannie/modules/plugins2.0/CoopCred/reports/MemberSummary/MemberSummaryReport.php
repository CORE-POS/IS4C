<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

/* TODO 28Feb2015
 * - Format dates in PHP rather than MySQL
 */

include(dirname(__FILE__) . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class MemberSummaryReport extends FannieReportPage
{

    public $themed = true;
    public $description = "[Coop Cred Member Summary Report] Coop Cred: Summary of Payments, Purchases and Net for each member of a Program.";
    public $report_set = 'CoopCred';
    protected $title = "Fannie: Coop Cred Program Members Summary Report";
    protected $header = "Coop Cred Program Members Summary Report";

    protected $programID = 0;
    protected $programName = "";
    protected $programBankID = 0;
    protected $programStartDate = "";
    protected $dateFrom = "";
    protected $dateTo = "";
    protected $errors = array();
    protected $reportType = "";
    protected $dbSortOrder;
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

        /**
          Whether invoked by form submission.
        */
        if (isset($_REQUEST['programID'])) {

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
                //obs $this->bankID = $prog->bankID();
            } else {
                $this->errors[] = "Error: Program ID {$programID} is not known.";
                return True;
            }

            $this->programStartDate = (preg_match("/^[12]\d{3}-\d{2}-\d{2}/",$prog->startDate()))
                ? $prog->startDate() : '1970-01-01';

            $dateFrom = FormLib::get_form_value('date1','');
            $dateTo = FormLib::get_form_value('date2','');
            $this->dateFrom = (($dateFrom == '')?$this->programStartDate:$dateFrom);
            $this->dateTo = (($dateTo == '')?date('Y-m-d'):$dateTo);

            /* Vars from the form
             */
            $this->reportType = FormLib::get_form_value('reportType','summary');
            $this->sortable = FormLib::get_form_value('sortable','0');
            $this->subTotals = FormLib::get_form_value('subTotals','0');
            $this->dbSortOrder = FormLib::get_form_value('dbSortOrder','DESC');
            if ($this->sortable == True) {
                if ($this->reportType == 'summary') {
                    $this->sort_column = 1; // 1st column is 0
                    $this->sort_direction = 0; // 0=asc 1=desc
                } else {
                    $this->sort_column = 3; // 1st column is 0
                    /* 0=asc 1=desc 
                     * Name is always ASC. DESC applies to date.
                     */
                    $this->sort_direction = 0;
                    //$this->sort_direction = (($this->dbSortOrder == 'DESC')?1:0);
                }
            } else {
                $this->sortable = False;
            }

            /*
              Check if a non-html format has been requested
            */
            if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls') {
                $this->report_format = 'xls';
                $this->has_menus(False);
            } elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv') {
                $this->report_format = 'csv';
                $this->has_menus(False);
            }

            /* Which page content to create upon return to draw_page().
             */
            $this->content_function = "report_content";
        } else {
            $this->add_script("{$FANNIE_URL}src/CalendarControl.js");
            if (FormLib::get_form_value('pid',0) != 0) {
                $this->pid = FormLib::get_form_value('pid',0);
            }
        }

        return True;

    // preprocess()
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
    ";
    $css .=
"H3.report {
    line-height:1.3em;
    margin-bottom:0.2em;
}
    ";
        return $css;
    }

    /* Lines of descriptive text that appear before the tabular part of the
     * report.
     */
    function report_description_content(){
        /* Each line of description is an element of this array.
         * At output <br /> will be prefixed unless the element starts with
         *  an HTML tag
         */
        $ret = array();
        $ret[] = sprintf("<H3 class='report'>Coop Cred: Payments,
            Purchases and Net for the<br />%s Program<br />From %s to %s</H3>",
            $this->programName,
            (($this->dateFrom == $this->programStartDate)
                ? "Program Start"
                : date("F j, Y",strtotime("$this->dateFrom"))),
            (($this->dateTo == date('Y-m-d'))?"Today":date("F j, Y",strtotime("$this->dateTo")))
        );
        // Today, until now (not necessarily the whole day).
       if ($this->dateTo = date('Y-m-d')) {
            $today_time = date("l F j, Y g:i A");
            $ret[] = "<p class='explain'>As at: {$today_time}</p>";
        // Last day
        } else {
            $today_time = date("l F j, Y");
            $ret[] = "<p class='explain'>To the end of the day: {$today_time}</p>";
            //$ret[] = "To the end of the day <b>before</b> this day: {$today_time}</p>";
        }
        $ret[] = "<p class='explain expfirst'><b>Total</b> of <b>Purchase</b>".
            " at the <a href='#notes' style='text-decoration:underline;'>end</a> of the report".
            " is the retail value of what has been".
            " taken from inventory.</p>";
        $ret[] = "<p class='explain'><b>Total</b> of <b>Net</b>".
            " at the <a href='#notes' style='text-decoration:underline;'>end</a> of the report".
            " is the difference between the the amount that has been put in Members'".
            " accounts (Payment) and the amount they have used for purchases.".
            " It is the amount the Coop is still liable for.<!--br /><br /--></p>";
        $ret[] ="";
        return $ret;
    // /report_description_content()
    }

    /* Get data from the database
     * and format it as a table without totals in the last row.
     */
    function fetch_report_data(){

        //global $FANNIE_TRANS_DB,
        global $FANNIE_OP_DB, $FANNIE_URL;
        global $FANNIE_PLUGIN_SETTINGS;

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['CoopCredDatabase']);

        /* Return value of the function,
         * an array of rows of table cell contents
         */
        $ret = array();
        $args = array();
        $args[] = $this->programID;
        $args[] = $this->programID;
        $args[] = $this->programBankID;
        $args[] = "$this->dateFrom 00:00:00";
        $args[] = "$this->dateTo 23:59:59";
        if ($this->dateTo == date('Y-m-d')) {
            $args[] = $this->programID;
            $args[] = $this->programID;
            $args[] = $this->programBankID;
        }

        if ($this->reportType == "detail") {

            $this->report_headers = array('Date','When','Member#','Member Name',
               'Receipt','$ '._('Payment'),'$ '._('Purchase'), '$ '._('Net'));

            $queryPast = " (SELECT a.cardNo AS cardNo,
                        a.tdate AS OrderDate,
                        DATE_FORMAT(a.tdate,'%Y %m %d %H:%i') AS 'SortDate',
                        DATE_FORMAT(a.tdate,'%M %e, %Y %l:%i%p') AS 'When',
                        a.charges,
                        a.transNum,
                        a.payments,
                        (a.payments - a.charges) AS 'Net',
                        year(a.tdate) AS tyear, month(a.tdate) AS tmonth, day(a.tdate) AS tday,
                        c.CardNo AS CCardNo,
                        c.FirstName AS FirstName,
                        c.LastName AS LastName
                    FROM CCredMemberships AS m
                        JOIN {$FANNIE_OP_DB}{$dbc->sep()}custdata AS c
                            ON m.cardNo = c.CardNo
                        JOIN CCredHistory AS a
                            ON a.cardNo = m.cardNo
                    WHERE m.programID = ?
                        AND a.programID = ?
                        AND m.cardNo != ?
                        AND (a.tdate BETWEEN ? AND ?))";

            $queryToday = " (SELECT a.cardNo AS cardNo,
                    a.tdate AS OrderDate,
                    DATE_FORMAT(a.tdate,'%Y %m %d %H:%i') AS 'SortDate',
                    DATE_FORMAT(a.tdate,'%M %e, %Y %l:%i%p') AS 'When',
                    a.charges,
                    a.trans_num,
                    a.payments,
                    (a.payments - a.charges) AS 'Net',
                    year(a.tdate) AS tyear, month(a.tdate) AS tmonth, day(a.tdate) AS tday,
                    c.CardNo AS CCardNo,
                    c.FirstName AS FirstName,
                    c.LastName AS LastName
                FROM CCredMemberships AS m
                    JOIN {$FANNIE_OP_DB}{$dbc->sep()}custdata AS c
                        ON m.cardNo = c.CardNo
                    JOIN CCredHistoryToday AS a
                        ON a.cardNo = m.cardNo
                WHERE m.programID = ?
                    AND a.programID = ?
                    AND m.cardNo != ?)";

            $queryOrder = "\nORDER BY LastName ASC, FirstName, CCardNo, OrderDate {$this->dbSortOrder}";
            $queryUnion = "\nUNION\n";

            // If the order is DESC and the range includes today then
            //  the CCredHistoryToday select needs to be first.
            if ($this->dateTo == date('Y-m-d')) {
                if ($this->dbSortOrder == 'DESC') {
                    $args = array();
                    $args[] = $this->programID;
                    $args[] = $this->programID;
                    $args[] = $this->programBankID;
                    $args[] = $this->programID;
                    $args[] = $this->programID;
                    $args[] = $this->programBankID;
                    $args[] = "$this->dateFrom 00:00:00";
                    $args[] = "$this->dateTo 23:59:59";
                    $query = "$queryToday $queryUnion $queryPast $queryOrder";
                } else {
                    $args = array();
                    $args[] = $this->programID;
                    $args[] = $this->programID;
                    $args[] = $this->programBankID;
                    $args[] = "$this->dateFrom 00:00:00";
                    $args[] = "$this->dateTo 23:59:59";
                    $args[] = $this->programID;
                    $args[] = $this->programID;
                    $args[] = $this->programBankID;
                    $query = "$queryPast $queryUnion $queryToday $queryOrder";
                }
            } else {

                $args = array();
                $args[] = $this->programID;
                $args[] = $this->programID;
                $args[] = $this->programBankID;
                $args[] = "$this->dateFrom 00:00:00";
                $args[] = "$this->dateTo 23:59:59";
                $query = "$queryPast $queryOrder";
            }

        } elseif ($this->reportType == "summary") {
            $this->report_headers = array(
                'Member#','Member Name',
                '$ Payments','$ Purchases', '$ Net'
            );
        $query = "(SELECT a.cardNo AS cardNo,
                    a.tdate AS OrderDate,
                    SUM(a.charges) AS charges,
                    SUM(a.payments) AS payments,
                    SUM(a.payments - a.charges) AS 'Net',
                    c.CardNo AS CCardNo,
                    c.FirstName AS FirstName,
                    c.LastName AS LastName
                FROM CCredMemberships AS m
                    JOIN {$FANNIE_OP_DB}{$dbc->sep()}custdata AS c
                        ON m.cardNo = c.CardNo
                    JOIN CCredHistory AS a
                        ON a.cardNo = m.cardNo
                WHERE m.programID = ?
                    AND a.programID = ?
                    AND m.cardNo != ?
                    AND (a.tdate BETWEEN ? AND ?)
        GROUP BY c.CardNo)";
        // If range includes today, need UNION with CCredHistoryToday
        if ($this->dateTo == date('Y-m-d')) {
            $query .= "\nUNION";
            $query .= "\n(SELECT a.cardNo AS cardNo,
                    a.tdate AS OrderDate,
                    SUM(a.charges) AS charges,
                    SUM(a.payments) AS payments,
                    SUM(a.payments - a.charges) AS 'Net',
                    c.CardNo AS CCardNo,
                    c.FirstName AS FirstName,
                    c.LastName AS LastName
                FROM CCredMemberships AS m
                    JOIN {$FANNIE_OP_DB}{$dbc->sep()}custdata AS c
                        ON m.cardNo = c.CardNo
                    JOIN CCredHistoryToday AS a
                        ON a.cardNo = m.cardNo
                WHERE m.programID = ?
                    AND a.programID = ?
                    AND m.cardNo != ?
        GROUP BY c.CardNo)";
        }
        $query .= "\nORDER BY LastName, FirstName, CCardNo";
        // summary
        }

        $statement = $dbc->prepare("$query");
        if ($statement === False) {
            $ret[] = "***Error preparing: $query";
            return $ret;
        }
        $results = $dbc->execute($statement,$args);
        if ($results === False) {
            $allArgs = implode(' : ',$args);
            $ret[] = "***Error executing: $query with: $allArgs";
            return $ret;
        }

        if ($this->reportType == "detail") {
            // Compose the rows of the table.
            while ($row = $dbc->fetchRow($results)) {
                // Array of cells of a row in the report table.
                $record = array();
                if ($this->reportType == "detail")
                    $record[] = $row['SortDate'];
                if ($this->reportType == "detail")
                    $record[] = $row['When'];
                //Member Number
                $record[] = "<a href='../Activity/index.php?memNum={$row['cardNo']}" .
                    "&amp;programID={$this->programID}'".
                    " target='_CCR_{$row['cardNo']}' title='Details for this member'>" .
                    "{$row['cardNo']}</a>";
                //Member Name
                $memberName = sprintf("%s, %s", $row['LastName'], $row['FirstName']);
                $record[] = "<a href='../Activity/index.php?memNum={$row['cardNo']}" .
                    "&amp;programID={$this->programID}'".
                    " target='_CCR_{$row['cardNo']}' title='Details for this member'>" .
                    "{$memberName}</a>";
                //trans_num
                if ($this->reportType == "detail") {
                    $record[] = sprintf("<a href='%sadmin/LookupReceipt/RenderReceiptPage.php?".
                        "year=%d&month=%d&day=%d&receipt=%s' target='_Receipt'>%s</a>",
                        "$FANNIE_URL",
                        $row['tyear'],$row['tmonth'],$row['tday'],
                        $row['trans_num'], $row['trans_num']);
                }
                $record[] = $row['payments'];
                $record[] = $row['charges'];
                $record[] = $row['Net'];
                $ret[] = $record;
            }
        // detail
        }

        /* Summary, consolidating today and before-today rows for the same member.
         * Compose the rows of the table.
         * %0.2f doesn't work as expected. To do with negative payments at some point?
            $record[2] = sprintf("%0.2f",$row['payments']);
         */
        if ($this->reportType == "summary") {
            $lastCardNo = 0;
            $record = array();
            $rowCount = 0;
            while ($row = $dbc->fetchRow($results)) {
                if ($row['cardNo'] != $lastCardNo && $lastCardNo != 0) {
                    $ret[] = $record;
                    // Array of cells of a row in the report table.
                    $record = array();
                }
                if ($row['cardNo'] != $lastCardNo) {
                    //Member Number
                    $record[] = "<a href='../Activity/index.php?memNum=" .
                        "{$row['cardNo']}&amp;programID={$this->programID}'".
                        " target='_CCR_{$row['cardNo']}' title='Details for this member'>" .
                        "{$row['cardNo']}</a>";
                    //Member Name
                    $memberName = sprintf("%s, %s", $row['LastName'], $row['FirstName']);
                    $record[] = "<a href='../Activity/index.php?memNum={$row['cardNo']}" .
                        "&amp;programID={$this->programID}'".
                        " target='_CCR_{$row['cardNo']}' title='Details for this member'>" .
                        "{$memberName}</a>";
                    $record[2] = $row['payments'];
                    $record[3] = $row['charges'];
                    $record[4] = $row['Net'];
                } else {
                    $record[2] += $row['payments'];
                    $record[3] += $row['charges'];
                    $record[4] += $row['Net'];
                }
                $lastCardNo = $row['cardNo'];
                $rowCount++;
            }
            if ($rowCount > 0) {
                $ret[] = $record;
            }
        // summary
        }

        return $ret;

    // /fetch_report_data()
    }

    /**
      Extra, non-tabular information appended to reports
      @return array of strings
    */
    function report_end_content(){
        $ret = array();
        $ret[] = "<p class='explain'><br /><a name='notes'><b>Notes:</b></a></p>";
        $ret[] = "<p class='explain'><b>Total</b> of <b>Purchases</b>".
            " is the retail value of what has been".
            " taken from inventory.</p>";
        $ret[] = "<p class='explain'><b>Total</b> of <b>Net</b>".
            " is the difference between the the amount that has been put in Members'".
            " accounts (Payment) and the amount they have used for purchases.".
            " It is the amount the Coop is still liable for.</p>";
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
        $sumPayments = 0.0;
        $sumCharges = 0.0;
        $sumNet = 0.0;
        if ($this->reportType == "detail") {
            foreach($data as $row) {
                $sumPayments += (isset($row[5]))?$row[5]:0;
                $sumCharges += (isset($row[6]))?$row[6]:0;
                $sumNet += (isset($row[7]))?$row[7]:0;
            }
            $ret = array();
            $ret[] = array(null,null,null,null,null,'$ Payments','$ Purchases','$ Net');
            $ret[] = array('Totals',null,null,null,null,
                number_format($sumPayments,2),
                number_format($sumCharges,2),
                number_format($sumNet,2)
            );
        } elseif ($this->reportType == "summary") {
            foreach($data as $row) {
                $sumPayments += (isset($row[2]))?$row[2]:0;
                $sumCharges += (isset($row[3]))?$row[3]:0;
                $sumNet += (isset($row[4]))?$row[4]:0;
            }
            $ret = array();
            $ret[] = array(null,null,'$ Payments','$ Purchases','$ Net');
            $ret[] = array('Totals',null,
                number_format($sumPayments,2),
                number_format($sumCharges,2),
                number_format($sumNet,2)
            );
        }
        return $ret;
    // /calculate_footers()
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
<form method = "get" action="MemberSummaryReport.php" class="form-horizontal">
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
<!-- Restore these two tags to put the dates to the right -->
    <!-- /div --><!-- /.col-sm-6 -->
    <!-- div class="col-sm-5" -->
        <div class="form-group">
            <label class="col-sm-4 control-label">Start Date</label>
            <div class="col-sm-8">
                <input type=text id=date1 name=date1 class="form-control date-field" /><!-- required / -->
                <p class="explain" style="float:none; margin:0 0 0 0.5em;">Leave
                    both dates empty to report on the whole life of the program.
                <br />Leave Start date empty to report from the beginning of the program.
                </p>
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
    </div><!-- /.col-sm-5 -->
</div><!-- /.row -->
<p>
        <button type=submit name=submit value="Create Report" class="btn btn-default">Create Report</button>
        <!-- button type=reset name=reset class="btn btn-default">Start Over</button -->
</p>
<!-- input type=hidden name=cardNo id=cardNo value=0  / -->
<input type=hidden name='reportType' id='reportType' value='summary' />
<!-- input type=hidden name='sortable' id='sortable' value='0' / -->
<input type=hidden name='subTotals' id='subTotals' value='0' />
<input type=hidden name='dbSortOrder' id='dbSortOrder' value='ASC' />
</form><!-- /.form-horizontal -->

<!-- Bootstrap-coded ends -->
<?php
    // /form_content()
    }

// /class MemberSummaryReport
}

FannieDispatch::conditionalExec();

