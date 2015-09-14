<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class EquityReport extends FannieReportPage 
{
    public $description = '[Member Equity] lists all equity transactions for a given member';
    public $report_set = 'Membership';
    public $themed = true;

    protected $report_headers = array('Date', 'Receipt', 'Amount', 'Type');
    protected $sort_direction = 1;
    protected $title = "Fannie : Equity Activity Report";
    protected $header = "Equity Activity Report";
    protected $required_fields = array('memNum');

    public function preprocess()
    {
        $this->card_no = FormLib::get('memNum','');

        return parent::preprocess();
    }

    public function report_description_content()
    {
        return array('Activity for account #'.$this->card_no);
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        /**
          Query dlog for today's transactions if
          department info is available
        */
        $args = array();
        $equity_depts = $this->config->get('EQUITY_DEPARTMENTS');
        $todayQ = false;
        if (preg_match_all('/[0-9]+/', $equity_depts, $matches)) {
            $depts = array_pop($matches);
            $in = '';
            foreach ($depts as $d) {
                $args[] = $d;
                $in .= '?,';
            }
            $in = substr($in, 0, strlen($in)-1);
            $args[] = $this->card_no;

            $todayQ = '
                SELECT -total AS stockPurchase,
                    trans_num,
                    dept_name,
                    YEAR(tdate) AS year,
                    MONTH(tdate) AS month,
                    DAY(tdate) AS day,
                    t.tdate AS tdate
                FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'dlog AS t
                    LEFT JOIN departments AS d ON t.department=d.dept_no
                WHERE t.department IN (' . $in . ')
                    AND t.card_no=?';
        }

        /**
          Query dedicated history table for yesterday
          and earlier
        */
        $historyQ = "
            SELECT stockPurchase,
                trans_num,
                dept_name,
                YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                s.tdate AS tdate
            FROM " . $this->config->get('TRANS_DB') . $dbc->sep() . "stockpurchases AS s 
                LEFT JOIN departments AS d ON s.dept=d.dept_no
            WHERE s.card_no=?";
        $args[] = $this->card_no;

        /** union two queries together if applicable **/
        if ($todayQ) {
            $historyQ = $todayQ . ' UNION ALL ' . $historyQ
                . ' ORDER BY tdate';
        } else {
            $historyQ .= ' ORDER BY tdate';
        }
        $p = $dbc->prepare($historyQ);
        $r = $dbc->execute($p, $args);

        $data = array();
        while($w = $dbc->fetch_row($r)) {
            $record = array();
            $record[] = sprintf('%d/%d/%d',$w[4],$w[5],$w[3]);
            if (FormLib::get('excel') !== '') {
                $record[] = $w[1];
            } else {
                $record[] = sprintf('<a href="%sadmin/LookupReceipt/RenderReceiptPage.php?year=%d&month=%d&day=%d&receipt=%s">%s</a>',
                        $this->config->get('URL'),$w[3],$w[4],$w[5],$w[1],$w[1]);
            }
            $record[] = sprintf('%.2f', ($w[0] != 0 ? $w[0] : $w[2]));
            $record[] = $w[2];
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        $this->add_onload_command('$(\'#memNum\').focus()');
        return '<form method="get" action="EquityReport.php">
            <label>Member #</label>
            <input type="text" name="memNum" value="" 
                id="memNum" class="form-control" />
            <p>
            <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            List equity transactions for a given member. Simply
            enter the member number.
            </p>';
    }

}

FannieDispatch::conditionalExec();

