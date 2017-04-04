 <?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2015 West End Food Co-op, Toronto

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* TODO
 * Eliminate the multi-array code left over from the ancestor of this report.
 */

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class PatronageOverDatesReport extends FannieReportPage
{
    public $description = '[Patronage Over Date Range] lists top, or all, customers by purchases/avg basket over a range of dates';
    public $report_set = 'Membership :: Patronage';
    public $themed = true;

    protected $report_headers = array();
    protected $sort_direction = 1;
    protected $sort_column = 1;
    protected $title = "";
    protected $header = "";
    protected $required_fields = array('date1','date2');
    protected $top_n = 0;
    protected $non_member = array();

    public function preprocess()
    {
        $this->report_headers = array(_('Member'), '$Total Purchased', '$Average per Receipt',
            '#Receipts');
        $this->title = "Fannie : Patronage over Date Range Report";
        $this->header = "Patronage over Date Range Report";
        if (is_numeric(FormLib::get_form_value('top_n',0))) {
            $this->top_n = FormLib::get_form_value('top_n',0);
        }

        return parent::preprocess();
    }

    /* Text at the top of the report, below the standard heading.
     */
    public function report_description_content()
    {
        $desc = array();
        $line = "<p><b>Patronage over Date Range: " .
        (($this->top_n > 0) ? "Top " . $this->top_n : "All") .
            " Customers, by Total Purchases</b></p>";
        $desc[] = $line;
        //$line = "Var: " . $this->top_n . "  Form:" .  FormLib::get_form_value('top_n',0);
        $line = "<a href='" . $_SERVER['PHP_SELF'] ."'>Start over</a>";
        $desc[] = $line;
        return $desc;
    }

    function fetch_report_data(){
        global $FANNIE_OP_DB, $FANNIE_COOP_ID,
             $FANNIE_TRANS_DB, $FANNIE_URL;

        try {
            $d1 = $this->form->date1;
            $d2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }

        $dlog = DTransactionsModel::select_dlog($d1,$d2);

        if ( isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto' ) {
            $shrinkageUsers = " AND (card_no not between 99900 and 99998)";
        } else {
            $shrinkageUsers = "";
        }

        // New structure.
        $mdata = array();
        // Ancestor structures
        $card_no = array();
        $total = array();       // Total Spent for desired Range
        $numTran = array();     // Number of transactions for selected Range for each Owner

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));

        $limit = "";
        if ($this->top_n) {
            $limit = " LIMIT " . $this->top_n;
        }
        /* Total purchases for each member.
         * Old way: create parallel arrays $card_no of .card_no, $total of total.
         * New way: creat mdata, array of arrays.
         */
        $query = "SELECT card_no,
                sum(total) as tpurch  
                FROM $dlog dx
                WHERE
                (tdate BETWEEN ? AND ?)
                AND trans_type in ('I','D','S'){$shrinkageUsers}
                GROUP BY card_no 
                ORDER BY tpurch desc{$limit}
                ;";

        $statement = $dbc->prepare($query);
        $args = array($d1.' 00:00:00', $d2.' 23:59:59');
        $result = $dbc->execute($statement, $args);
        $mdata = array(); // New
        while ($row = $dbc->fetch_row($result)) {
            $card_no[] = $row['card_no'];
            $total[] = $row['tpurch'];
            //New
            $mem_num = $row['card_no'];
            //N.B. index is string.
            $mdata["$mem_num"] = array($row['tpurch'],0);
        }

        /* Number of transactions for each member in the previous search.
         * First option, search repeated for each member.
         *  OK to do this way if $top_n <= ~10 and if date range is small,
         *   but not for many or over a long range if unions involved.
         *  Why all the other arguments?
         *   Needed to distinguish transactions: emp-reg-trans on y-m-d.
         *   Why does that matter within the date range?
         */
        $unionCount = substr_count($dlog, ' union ');
        if ($this->top_n > 0 && $this->top_n <= 10 && $unionCount <= 2) {
            $query = "SELECT trans_num,
                        month(tdate) as mt, day(tdate) as dt, year(tdate) as yt 
                    FROM $dlog dx
                    WHERE
                    (tdate BETWEEN ? AND ?)
                    AND card_no = ? AND trans_type = 'A'
                    GROUP BY trans_num, mt, dt, yt 
                    ORDER BY mt, dt, yt
                    ;";
            $statement = $dbc->prepare($query);
            $args = array($d1.' 00:00:00', $d2.' 23:59:59');
            for($i=0; $i<count($card_no);$i++) {
                $args[2] = $card_no[$i];
                $result = $dbc->execute($statement, $args);
                $mdata["$card_no[$i]"][1] = $dbc->num_rows($result);
                $numTran[] = $dbc->num_rows($result);
            }
        } else {
            /* Number of transactions for each member in the previous search. */
            $query = "SELECT count(card_no) AS ct, card_no
                    FROM $dlog  dx
                    WHERE
                    (tdate BETWEEN ? AND ?)
                    AND (trans_type = 'A'){$shrinkageUsers}
                    GROUP BY card_no
                    ;";
            $statement = $dbc->prepare($query);
            $args = array($d1.' 00:00:00', $d2.' 23:59:59');
            $result = $dbc->execute($statement, $args);
            $count = 0;
            if ($this->top_n > 0) {
                while ($row = $dbc->fetch_row($result)) {
                    $mem_num = $row['card_no'];
                    if (isset($mdata["$mem_num"])) {
                        $mdata["$mem_num"][1] = $row['ct'];
                        $count++;
                    }
                }
            } else {
                while ($row = $dbc->fetch_row($result)) {
                    $mem_num = $row['card_no'];
                    $mdata["$mem_num"][1] = $row['ct'];
                    $count++;
                }
            }
        }

        if (isset($mdata['99999'])) {
            $this->non_member = $mdata['99999'];
        }

        // Compose the rows of the report in a 2D array.
        $info = array();
        foreach ($mdata as $mem => $mbits) {
            $table_row = array(
                $mem,
                sprintf("%0.2f", $mbits[0]),
                sprintf("%0.2f", ($mbits[0] / $mbits[1])),
                $mbits[1],
            );
            $info[] = $table_row;
        }

        return $info;

    // fetch_report_data()
    }

    /**
      Sum the quantity and total columns for a footer of one or more rows.
      Also set up headers and in-table (column-head) sorting.
    */
    function calculate_footers($data)
    {
        // no data; don't bother
        if (empty($data)) {
            return array();
        }

        /* Initial sequence of the report.
         * May not be the same as the sequence  of composition, driven, say,
         *  by an ORDER BY clause.
         */
        $this->sort_column = 1; // First = 0
        $this->sort_direction = 1;  // 1 = ASC

        $sumSales = 0.0;
        $sumAvgBskt = 0.0;
        $sumTransactions = 0;
        foreach($data as $row) {
            $sumSales += $row[1];
            $sumAvgBskt += $row[2];
            $sumTransactions += $row[3];
        }
        $rowCount = count($data);
        $avgBskt = sprintf("%0.2f", ($sumSales / $sumTransactions));
        $avgBskts = sprintf("%0.2f", ($sumTransactions / $rowCount));
        /* Means as well as averages? */

        $ret = array();
        $ret[] = array('#Customers','$ Grand Total Purchases','$ Overall Average per Receipt',
        '# Grand Total Receipts');
        $ret[] = array(number_format($rowCount),
            '$ '.number_format($sumSales,2),
            '$ '.number_format($avgBskt,2),
            number_format($sumTransactions)
        );
        $ret[] = array('','','Average Receipts per Customer','');
        $ret[] = array('','',number_format($avgBskts,2),'');

        if (isset($this->non_member[0])) {
            $ret[] = array('Without non-member purchases:',null,null,null);
            $sumSalesM = $sumSales - $this->non_member[0];
            $sumTransactionsM = $sumTransactions - $this->non_member[1];
            $rowCountM = $rowCount - 1;
            $avgBsktM = sprintf("%0.2f", ($sumSalesM / $sumTransactionsM));
            $avgBsktsM = sprintf("%0.2f", ($sumTransactionsM / $rowCountM));
            $ret[] = array('#Members','$ Grand Total Purchases','$ Overall Average per Receipt',
            '# Grand Total Receipts');
            $ret[] = array(number_format($rowCountM),
                '$ '.number_format($sumSalesM,2),
                '$ '.number_format($avgBsktM,2),
                number_format($sumTransactionsM)
            );
            $ret[] = array('','','Average Receipts per Member','');
            $ret[] = array('','',number_format($avgBskts,2),'');
            // Proportion of activity by Members.
            $ret[] = array('','% of Purchases by Members',
                'Relative size of Member Receipt',
                '% of Receipts to Members');
            $propPurchM = sprintf("%0.2f %%",(($sumSalesM / $sumSales)*100));
            $propBsktM = sprintf("%00.2f %%",(($avgBsktM / $avgBskt)*100));
            $propTransM = sprintf("%0.2f %%",(($sumTransactionsM / $sumTransactions)*100));
            $ret[] = array('',$propPurchM,$propBsktM,$propTransM);
        }

        return $ret;

    // calculate_footers()
    }


    function form_content()
    {
        ob_start();
        list($lastMonday, $lastSunday) = \COREPOS\Fannie\API\lib\Dates::lastWeek();

        ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get" id="form1">
        <!-- Left column -->
        <div class="col-sm-5">
            <div class="form-group">
            <label>Top how many <?php echo _("members"); ?>?
            <br />(Leave empty for all.)</label>
                <input type="text" name="top_n" value="" class="form-control"
                    id="top_n" />
            </div>
            <div class="form-group">
                <label>Start Date</label>
                <input type=text id=date1 name=date1 class="form-control date-field" 
                    value="<?php echo $lastMonday; ?>" />
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type=text id=date2 name=date2 class="form-control date-field" 
                    value="<?php echo $lastSunday; ?>" />
            </div>
            <div class="form-group">
                <label>Sortable
                    <input type="checkbox" name="sortable" />
                </label>
            </div>
            <div class="form-group">
                <p>
                    <button type="submit" class="btn btn-default">Prepare Report</button>
                </p>
            </div>
        </div>
        <!-- Right column -->
        <div class="col-sm-5">
<p>
<br/> 
<br/> 
<br/> 
</p>
            <?php echo FormLib::date_range_picker(); ?>
        </div>
        </form>
        <?php
        $this->add_onload_command('$(\'#top_n\').focus()');
        return ob_get_clean();

    // form_content()
    }

    public function helpContent()
    {
        return '<p>
            List ' . _("members") . ' by total purchases and number of transactions over a range of dates.
            Can choose to show only the Top (highest dollar-value) <i>N</i> or all
            who shopped at all.
            <br />End-of-report has totals and averages with and without non-member purchases
            and proportion of member to non-member purchases.
            </p>';
    }

}

FannieDispatch::conditionalExec();

