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

/* TODO 12Sep2015
 * - Remove deadwood.
 * - Refine help.
 * - help to toolbar help.
 */

include(dirname(__FILE__) . '/../../../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class LiabilityReport extends FannieReportPage
{

    public $themed = true;
    public $description = "[Coop Cred Liability Report] Coop Cred: Summary of Inputs, Payments, Purchases and Unspent for each Program.";
    public $report_set = 'CoopCred';
    protected $title = "Fannie: Coop Cred Program Liability Report";
    protected $header = "Coop Cred Program Liability Report";

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
            // 0 means all programs
            $programID = (int)$_REQUEST['programID'];

            if ($programID > 0) {
                $ccpModel = new CCredProgramsModel($dbc);
                $ccpModel->programID($programID);
                $prog = array_pop($ccpModel->find());
                if ($prog != null) {
                    $this->programID = $prog->programID();
                    $this->programName = $prog->programName();
                    $this->programBankID = $prog->bankID();
                    $this->programStartDate = (preg_match("/^[12]\d{3}-\d{2}-\d{2}/",$prog->startDate()))
                        ? $prog->startDate() : '1970-01-01';
                } else {
                    $this->errors[] = "Error: Program ID {$programID} is not known.";
                    return True;
                }
            } else {
                $this->programID = $programID;
                $this->programName = "All Programs";
                $this->programBankID = 0;
                $this->programStartDate = '1970-01-01';
            }


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
                    $this->sort_column = 0; // 1st column is 0
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
                Check if a non-html format has been requested.
                Does FannieReportPage already do this?
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
        $ret[] = sprintf("<H3 class='report'>Coop Cred: Inputs, Payments,
            Purchases and Unspent for %s<br />From %s to %s</H3>",
            (($this->programID == 0) ? "<br />{$this->programName}" :
                "the<br />{$this->programName} Program"),
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
        }
        $ret[] = "<p class='explain expfirst'><b>Inputs</b>" .
            " is the amount from sponsors and other contributions." .
            "</p>";
        $ret[] = "<p class='explain'><b>Disbursements</b>" .
            " is the amount transferred from Inputs to Members' accounts." .
            " It should be the same as Payments." .
            "</p>";
        $ret[] = "<p class='explain'><b>Undisbursed</b>" .
            " is the difference between Inputs and Disbursements." .
            " It is the amount of Inputs the co-op retains, is still expected to disburse." .
            "</p>";
        $ret[] = "<p class='explain'><b>Total</b> of <b>Purchases</b>".
            " at the <a href='#notes' style='text-decoration:underline;'>end</a> of the report".
            " is the retail value of what has been".
            " taken from inventory.</p>";
        $ret[] = "<p class='explain'><b>Total</b> of <b>Unspent</b>".
            " at the <a href='#notes' style='text-decoration:underline;'>end</a> of the report".
            " is the difference between the the amount that has been put in Members'".
            " accounts (Payment) and the amount they have used for purchases.".
            " It is the amount the Coop is still liable for." .
            "</p>";
        $ret[] = "<p class='explain'><b>Disbursements minus Payments</b>" .
            " is the difference between the amount removed (disbursed) from Inputs " .
            " and the amount added (paid) to Members' accounts. " .
            " It should be 0." .
            "</p>";
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
        $programWhere = "";
        if ($this->programID != 0) {
            $programWhere = " AND h.programID = ? ";
            $args[] = $this->programID;
        }
        $args[] = "$this->dateFrom 00:00:00";
        $args[] = "$this->dateTo 23:59:59";
        if (False && $this->dateTo == date('Y-m-d')) {
            $args[] = $this->programID;
            $args[] = $this->programID;
            $args[] = $this->programBankID;
        }

        /* 11Sep2015 At the moment I can't think how a "detail" version
         * would work. Restore from MemberSummary if needed.
         */

        if ($this->reportType == "summary") {
            $this->report_headers = array(
                'Program',
                'Program<br />Inputs',
                'Program<br />Disbursements',
                'Program<br />Undisbursed',
                'Member<br />Payments',
                'Member<br />Purchases',
                'Member<br />Unspent',
                'Disbursements -<br />Payments'
            );
            $selectFields = "programID, cardNo, charges, payments, tdate";
            $todayQuery = "";
            if ($this->dateTo == date('Y-m-d')) {
                $todayQuery = " UNION SELECT {$selectFields} FROM CCredHistoryToday";
            }
            //c.FirstName, c.LastName,
            $query = "SELECT h.programID, h.cardNo, h.charges, h.payments, h.tdate,
                m.isBank,
                p.programName
                FROM (SELECT {$selectFields} FROM CCredHistory{$todayQuery}) as h
                LEFT JOIN CCredPrograms p ON p.programID = h.programID
                LEFT JOIN CCredMemberships m ON m.cardNo = h.cardNo AND
                         m.programID = h.programID
                LEFT JOIN {$FANNIE_OP_DB}{$dbc->sep()}custdata AS c
                    ON c.CardNo = h.cardNo
                WHERE 1=1 {$programWhere}
                AND c.personNum =1
                AND h.tdate BETWEEN ? AND ?
                ORDER BY h.programID, h.cardNo";


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

        /* 11Sep2015 At the moment I can't think how a "detail" version
         * would work. Restore from MemberSummary if needed.
         */

        /*
                0 'Program',
                1 'Inputs',
                2 'Disbursements',
                3 'Undisbursed',
                4 'Payments',
                5 'Purchases',
                6 'Unspent'
                7 'Disbursed - Paid'
            Inputs = banker, CCredHistory.payments > 0
            Payments = banker, CCredHistory.payments < 0

            Payments = member, .payments > 0
            Purchases = member, .charges > 0

            Unspent = Payments to members - Purchases by members
            Undisbursed = Inputs to banker - Payments from banker
        */

        if ($this->reportType == "summary") {
            $lastProgramID = 0;
            $record = array('',0,0,0,0,0,0,0);
            $rowCount = 0;
            while ($row = $dbc->fetchRow($results)) {
                if ($row['programID'] != $lastProgramID && $lastProgramID != 0) {
                    // Finish the Program row.
                    $record[2] = ($record[2] * -1); // as positive
                    $record[3] = ($record[1] - $record[2]);
                    $record[6] = ($record[4] - $record[5]);
                    $record[7] = ($record[2] - $record[4]);
                    for ($i=1 ; $i<count($record) ; $i++) {
                        $record[$i] = sprintf('%0.2f',$record[$i]);
                    }
                    $ret[] = $record;
                    // Array of cells of a row in the report table.
                    $record = array('',0,0,0,0,0,0,0);
                }
                if ($row['programID'] != $lastProgramID) {
                    //Program
                    $record[0] = "<a href='../ProgramEvents/ProgramEventsReport.php?programID=" .
                        "{$row['programID']}" .
                        "&amp;date1=" . $this->dateFrom .
                        "&amp;date2=" . $this->dateTo .
                        "&amp;sortable=True" .
                        "'" .
                        " target='_CCP_{$row['programID']}' title='Details for this Program'>" .
                        "{$row['programID']} {$row['programName']}</a>";
                    $record[0] .= " <a href='../MemberSummary/MemberSummaryReport.php?programID=" .
                        "{$row['programID']}" .
                        "&amp;date1=" . $this->dateFrom .
                        "&amp;date2=" . $this->dateTo .
                        "&amp;sortable=True" .
                        "'" .
                        " target='_CCM_{$row['programID']}' " .
                        "title='Details for Members of this Program'>" .
                        "(members)" .
                        "</a>";
                    if ($row['isBank'] == 1) {
                        if ($row['payments'] > 0) {
                            $record[1] = $row['payments'];
                        } else {
                            $record[2] = $row['payments'];
                        }
                    } else {
                        $record[4] = $row['payments'];
                        $record[5] = $row['charges'];
                    }
                } else {
                    if ($row['isBank'] == 1) {
                        if ($row['payments'] > 0) {
                            $record[1] += $row['payments'];
                        } else {
                            $record[2] += $row['payments'];
                        }
                    } else {
                        $record[4] += $row['payments'];
                        $record[5] += $row['charges'];
                    }
                }
                $lastProgramID = $row['programID'];
                $rowCount++;
            }
            if ($rowCount > 0) {
                // Finish the last Program row.
                $record[2] = ($record[2] * -1); // as positive
                $record[3] = ($record[1] - $record[2]);
                $record[6] = ($record[4] - $record[5]);
                $record[7] = ($record[2] - $record[4]);
                for ($i=1 ; $i<count($record) ; $i++) {
                    $record[$i] = sprintf('%0.2f',$record[$i]);
                }
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
        $ret[] = "<p class='explain'><b>Total</b> of <b>Undisbursed</b>" .
            " is the difference between Total Inputs and Total Disbursements." .
            " It is the amount of Inputs the co-op retains, is still expected to disburse." .
            "</p>";
        $ret[] = "<p class='explain'><b>Total</b> of <b>Purchases</b>".
            " is the retail value of what has been".
            " taken from inventory.</p>";
        $ret[] = "<p class='explain'><b>Total</b> of <b>Unspent</b>".
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
                0 'Program',
                1 'Inputs',
                2 'Disbursements',
                3 'Undisbursed',
                4 'Payments',
                5 'Purchases',
                6 'Unspent'
                7 'Disbursed - Payments'
    */
    function calculate_footers($data){
        $sumPayments = 0.0;
        $sumCharges = 0.0;
        $sumUnspent = 0.0;
        $sumInputs = 0.0;
        $sumDisbursements = 0.0;
        $sumUndisbursed = 0.0;
        $sumDminusP = 0.0;

        if ($this->reportType == "summary") {
            foreach($data as $row) {
                $sumInputs += (isset($row[1]))?$row[1]:0;
                $sumDisbursements += (isset($row[2]))?$row[2]:0;
                $sumUndisbursed += (isset($row[3]))?$row[3]:0;

                $sumPayments += (isset($row[4]))?$row[4]:0;
                $sumCharges += (isset($row[5]))?$row[5]:0;
                $sumUnspent += (isset($row[6]))?$row[6]:0;
                $sumDminusP += (isset($row[7]))?$row[7]:0;
            }
            $ret = array();
            $ret[] = array(null,
                '$ Inputs',
                '$ Disbursements',
                '$ Undisbursed',
                '$ Payments',
                '$ Purchases',
                '$ Unspent',
                '$ Disbursed - Paid'
            );
            $ret[] = array('Totals',
                number_format($sumInputs,2),
                number_format($sumDisbursements,2),
                number_format($sumUndisbursed,2),
                number_format($sumPayments,2),
                number_format($sumCharges,2),
                number_format($sumUnspent,2),
                number_format($sumDminusP,2)
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
<form method = "get" action="LiabilityReport.php" class="form-horizontal">
<div class="row">
    <div class="col-sm-6">
        <div class="form-group">
            <label class="col-sm-4 control-label">Program</label>
            <div class="col-sm-8">
<?php

            echo "<select id='programID' name='programID' class='form-control'>";
            echo "<option value='0'>All Programs</option>";
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
                    both dates empty to report on the whole life of the program(s).
                <br />Leave Start date empty to report from the beginning of the program(s).
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

    public function helpContent()
    {
        return '<p>Report liabilities,
            places where the co-op is still obliged
            to disburse money or provide goods.
            </p>
            <ul>
                <li>Inputs, money that has been provided by sponsors
                but not disbursed to members yet.
                </li>
                <li>Money that has been transferred (paid) to Program Members
                that they have not used for purchases (spent) yet.
                </li>
            </ul>
            ';
    }

// /class LiabilityReport
}

FannieDispatch::conditionalExec();

