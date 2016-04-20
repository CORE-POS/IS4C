<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ReprintReceiptPage extends \COREPOS\Fannie\API\FannieReadOnlyPage 
{

    protected $title = 'Fannie :: Lookup Receipt';
    protected $header = 'Receipt Search - Fill in any information available';

    public $description  = '[Lookup Receipt] finds a POS transaction.';
    private $results = '';

    public function preprocess()
    {
        $this->addRoute('get<date>');
        return parent::preprocess();
    }

    function get_date_handler()
    {
        $date = $this->date;
        $date2 = FormLib::get('date2','');
        if ($date === '' && $date2 !== '') {
            // only one date is supplied and it's
            // via the secondary field, still use it
            $date = $date2;
        }
        $trans_num = FormLib::get('trans_num','');
        $card_no = FormLib::get('card_no','');
        $emp_no = FormLib::get('emp_no','');
        $register_no = FormLib::get('register_no','');
        $trans_subtype = FormLib::get('trans_subtype','');
        $tenderTotal = FormLib::get('tenderTotal','');
        $department = FormLib::get('department','');
        $trans_no="";

        if ($trans_num !== "") {
            $temp = explode("-",$trans_num);
            if (count($temp) !== 3) {
                $emp_no=$reg_no=$trans_no=0;
            } else {
                $emp_no = $temp[0];
                $register_no=$temp[1];
                $trans_no=$temp[2];
            }
        }

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $dlog = $this->config->get('TRANS_DB') . $dbc->sep() . "transarchive";
        $query = "SELECT 
            year(datetime) AS year,
            month(datetime) AS month,
            day(datetime) AS day,
            emp_no,
            register_no,
            trans_no,
            MAX(card_no) AS card_no,
            MAX(datetime) AS ts
        FROM $dlog WHERE 1=1 ";
        $args = array();
        if ($date != "") {
            $date2 = ($date2 != "") ? $date2 : $date;
            $query .= ' AND datetime BETWEEN ? AND ? ';
            $args[] = $date.' 00:00:00';
            $args[] = $date2.' 23:59:59';
            $dlog = DTransactionsModel::selectDTrans($date, $date2);
            // update the table we're searching
            $query = str_replace($this->config->get('TRANS_DB') . $dbc->sep() . 'transarchive', $dlog, $query);
        } else {
            $query .= ' AND datetime >= ? ';
            $args[] = date('Y-m-d 00:00:00', strtotime('-15 days'));
        }
        if ($card_no != "") {
            $query .= " AND card_no=? ";
            $args[] = $card_no;
        }
        if ($emp_no != "") {
            $query .= " AND emp_no=? ";
            $args[] = $emp_no;
        }
        if ($register_no != "") {
            $query .= " AND register_no=? ";
            $args[] = $register_no;
        }
        if ($trans_no != "") {
            $query .= " AND trans_no=? ";
            $args[] = $trans_no;
        }
        if (FormLib::get('no-training') == '1') {
            $query .= ' AND emp_no <> 9999 AND register_no <> 99 ';
        }
        if (FormLib::get('no-canceled') == '1') {
            $query .= ' AND trans_status <> \'X\' ';
        }

        $tender_clause = "( 1=1";
        if ($trans_subtype != "") {
            $tender_clause .= " AND trans_subtype=? ";
            $args[] = $trans_subtype;
        }
        if ($tenderTotal != "") {
            $tender_clause .= " AND total=-1*? ";
            $args[] = $tenderTotal;
        } else {
            $tender_clause .= ' AND total <> 0 ';
        }
        $tender_clause .= ")";

        /**
          There is no tender restriction
          replace with a not-true statements
          otherwise the OR will match everything
        */
        if ($tender_clause == '( 1=1 AND total <> 0 )') {
            $tender_clause = '1=0';
        }

        $or_clause = '(' . $tender_clause;
        if ($department != "") {
            $or_clause .= " OR (department=? AND trans_type IN ('I','D')) ";
            $args[] = $department;
        }

        if (FormLib::get('is_refund', 0) == 1) {
            $or_clause .= ' OR trans_status=\'R\' ';
        }
        if (FormLib::get('mem_discount', 0) == 1) {
            $or_clause .= ' OR upc=\'DISCOUNT\' ';
        }

        $or_clause .= ")";
        if ($or_clause == "(1=0)") {
            $or_clause = "1=1";
        }
        $query .= ' AND '.$or_clause;

        $query .= " GROUP BY year(datetime),month(datetime),day(datetime),emp_no,register_no,trans_no ";
        $query .= " ORDER BY year(datetime),month(datetime),day(datetime),emp_no,register_no,trans_no ";

        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);
        if (!empty($trans_num) && !empty($date)) {
            header("Location: RenderReceiptPage.php?date=$date&receipt=$trans_num");
            return false;
        } elseif ($dbc->num_rows($result) == 0) {
            $this->results = "<b>No receipts match the given criteria</b>";
        } elseif ($dbc->num_rows($result) == 1){
            $row = $dbc->fetch_row($result);
            $year = $row[0];
            $month = $row[1];
            $day = $row[2];
            $trans_num = $row[3].'-'.$row[4].'-'.$row[5];
            header("Location: RenderReceiptPage.php?year=$year&month=$month&day=$day&receipt=$trans_num");
            return false;
        } else {
            $this->addScript('../../src/javascript/tablesorter/jquery.tablesorter.js');
            $this->addOnloadCommand("\$('.tablesorter').tablesorter();\n");
            $this->results = "<b>Matching receipts</b>:<br />";
            $this->results .= '<table class="table tablesorter">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Receipt</th>
                        <th>Employee</th>
                        <th>Lane</th>
                        <th>Owner</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>';
            $subTotalP = $dbc->prepare("
                SELECT SUM(-total) AS subtotal
                FROM {$dlog} AS d
                WHERE datetime BETWEEN ? AND ?
                    AND trans_type='T'
                    AND department = 0
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
            ");
            $num_results = $dbc->numRows($result);
            while ($row = $dbc->fetch_row($result)) {
                $this->results .= '<tr>';
                $year = $row[0];
                $month = $row[1];
                $day = $row[2];
                $this->results .= '<td>' . $row['ts'] . '</td>';
                $trans_num = $row[3].'-'.$row[4].'-'.$row[5];
                $this->results .= "<td><a href=RenderReceiptPage.php?year=$year&month=$month&day=$day&receipt=$trans_num>";
                $this->results .= "$trans_num</a></td>";
                $this->results .= '<td>' . $row['emp_no'] . '</td>';
                $this->results .= '<td>' . $row['register_no'] . '</td>';
                $this->results .= '<td>' . $row['card_no'] . '</td>';
                if ($num_results < 50) {
                    $subTotalArgs = array(
                        date('Y-m-d 00:00:00', strtotime($row['ts'])),
                        date('Y-m-d 23:59:59', strtotime($row['ts'])),
                        $row['emp_no'],
                        $row['register_no'],
                        $row['trans_no'],
                    );
                    $subTotalR = $dbc->execute($subTotalP, $subTotalArgs);
                    $subTotalW = $dbc->fetchRow($subTotalR);
                    $subTotal = is_array($subTotalW) ? $subTotalW['subtotal'] : 0;
                    $this->results .= sprintf('<td>%.2f</td>', $subTotal);
                } else {
                    $this->results .= '<td>n/a</td>';
                }
                $this->results .= '</tr>';
            }
            $this->results .= '</tbody></table>';
        }

        return true;
    }

    function css_content()
    {
        return '
        #mytable th {
            background: #330066;
            color: white;
            padding-left: 4px;
            padding-right: 4px;
        }
        .tablesorter thead th {
            cursor: hand;
            cursor: pointer;
        }
        .ui-datepicker {
            z-index: 100 !important;
        }
        ';
    }

    function get_date_view()
    {
        if (!empty($this->results)) {
            $str = filter_input(INPUT_SERVER, 'QUERY_STRING');
            $json = FormLib::queryStringToJSON($str);
            $this->results .= '
                <p>
                    <button type="button" class="btn btn-default"
                        onclick="location=\'ReprintReceiptPage.php\';">New Search</button>
                    <a href="?json=' . base64_encode($json) . '" class="btn btn-default btn-reset">
                        Adjust Search</a>
                </p>';
            return $this->results;
        } else {
            return $this->get_view();
        }
    }

    function get_view()
    {
        $dbc = $this->connection;
        $depts = "<option value=\"\">Select one...</option>";
        $prep = $dbc->prepare("SELECT dept_no,dept_name from departments order by dept_name");
        $res = $dbc->execute($prep);
        while($row = $dbc->fetch_row($res)) {
            $depts .= sprintf("<option value=%d>%s</option>",$row[0],$row[1]);
        }
        ob_start();
        ?>
<form action=ReprintReceiptPage.php method="get">
<div class="container"> 
<div class="row form-group form-inline">
    <label>Date*</label>
    <input type="text" name="date" class="form-control date-field" id="date"
        placeholder="Date" />
    <input type="text" name="date2" class="form-control date-field" id="date2"
        placeholder="2nd Date (optional)" />
    <label>Receipt #</label>
    <input type="text" name="trans_num" class="form-control" />
</div>
<div class="row form-group form-inline">
    <label>Member #</label>
    <input type="text" name="card_no" class="form-control" />
    <label>Cashier #</label>
    <input type="text" name="emp_no" class="form-control" />
    <label>Lane #</label>
    <input type="text" name="register_no" class="form-control" />
</div>
<div class="row form-group form-inline">
    <label>
        Refund
        <input type="checkbox" class="checkbox-inline" name="is_refund" value="1" />
    </label>
    &nbsp;
    <label>
        Mem Discount
        <input type="checkbox" class="checkbox-inline" name="mem_discount" value="1" />
    </label>
    &nbsp;
    <label>
        Exclude Canceled
        <input type="checkbox" class="checkbox-inline" name="no-canceled" value="1" checked />
    </label>
    &nbsp;
    <label>
        Exclude Training
        <input type="checkbox" class="checkbox-inline" name="no-training" value="1" checked />
    </label>
</div>
<div class="row form-group form-inline">
    <label>Tender Type</label>
    <select name="trans_subtype" class="form-control">
        <option value="">Select one...</option>
        <?php
        $numsQ = $dbc->prepare("SELECT TenderCode,TenderName FROM tenders 
            ORDER BY TenderName");
        $numsR = $dbc->execute($numsQ);
        while($numsW = $dbc->fetch_row($numsR)) {
            printf("<option value=%s>%s</option>",$numsW[0],$numsW[1]); 
        }
        ?>
    </select>
    <label>Tender Amount</label>
    <div class="input-group">
        <span class="input-group-addon">$</span>
        <input type="text" name="tenderTotal" class="form-control" />
    </div>
</div>
<div class="row form-group form-inline">
    <label>Department</label>
    <select name="department" class="form-control">
        <?php echo $depts; ?>
    </select>
    <button type="submit" name="submit" value="1" 
        class="btn btn-default">Find Receipts</button>
</div>
</div>
<div class="well">
    <b>Tips</b>:<br />
    <ul>
        <li>If no date is given, all matching receipts from the past 15 days will be returned</li>
        <li>A date and a receipt number is sufficient to find any receipt</li>
        <li>If you have a receipt number, you don't need to specify a lane or cashier number</li>
        <li>ALL fields are optional. You can specify a tender type without an amount (or vice versa)</li>
    </ul>
</div>
</form>
        <?php
        if (FormLib::get('json') !== '') {
            $init = FormLib::fieldJSONtoJavascript(base64_decode(FormLib::get('json')));
            $this->addOnloadCommand($init);
        }

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Find a receipt from a previous transaction. All fields are theoretically optional
            but that will give a very long list of transactions. Fill in a couple values to narrow
            down the list.</p>
            <ul>
                <li>If no date is given, all matching receipts from the past 15 days will be returned</li>
                <li>A date and a receipt number is sufficient to find any receipt</li>
                <li>If you have a receipt number, you don\'t need to specify a lane or cashier number</li>
                <li>ALL fields are optional. You can specify a tender type without an amount (or vice versa)</li>
            </ul>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->css_content()));
        $phpunit->assertNotEquals(0, strlen($this->get_date_view()));
        $this->date = date('Y-m-d');
        $phpunit->assertEquals(true, $this->preprocess());
        $phpunit->assertEquals(true, $this->get_date_handler());
        // other code path after handler runs
        $phpunit->assertNotEquals(0, strlen($this->get_date_view()));
    }
}

FannieDispatch::conditionalExec();

