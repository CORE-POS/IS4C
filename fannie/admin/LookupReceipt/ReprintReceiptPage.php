<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ReprintReceiptPage extends FanniePage 
{

    protected $title = 'Fannie :: Lookup Receipt';
    protected $header = 'Lookup Receipt';

    public $description  = '[Lookup Receipt] finds a POS transaction.';

    private $results = '';

    function preprocess()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        if (FormLib::get_form_value('submit', false) !== false) {
            $date = FormLib::get_form_value('date','');
            $date2 = FormLib::get_form_value('date2','');
            if ($date === '' && $date2 !== '') {
                // only one date is supplied and it's
                // via the secondary field, still use it
                $date = $date2;
            }
            $trans_num = FormLib::get_form_value('trans_num','');
            $card_no = FormLib::get_form_value('card_no','');
            $emp_no = FormLib::get_form_value('emp_no','');
            $register_no = FormLib::get_form_value('register_no','');
            $trans_subtype = FormLib::get_form_value('trans_subtype','');
            $tenderTotal = FormLib::get_form_value('tenderTotal','');
            $department = FormLib::get_form_value('department','');
            $trans_no="";

            if ($trans_num != "") {
                $temp = explode("-",$trans_num);
                $emp_no = $temp[0];
                $register_no=$temp[1];
                $trans_no=$temp[2];
            }

            $dbc = FannieDB::get($FANNIE_OP_DB);
            $dlog = $FANNIE_TRANS_DB . $dbc->sep() . "dlog_15";
            $query = "SELECT year(tdate),month(tdate),day(tdate),emp_no,register_no,trans_no FROM $dlog WHERE 1=1 ";
            $args = array();
            if ($date != "") {
                $date2 = ($date2 != "") ? $date2 : $date;
                $query .= ' AND tdate BETWEEN ? AND ? ';
                $args[] = $date.' 00:00:00';
                $args[] = $date2.' 23:59:59';
                $dlog = DTransactionsModel::selectDlog($date, $date2);
                // update the table we're searching
                $query = str_replace($FANNIE_TRANS_DB . $dbc->sep() . 'dlog_15', $dlog, $query);
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

            $tender_clause = "( 1=1";
            if ($trans_subtype != "") {
                $tender_clause .= " AND trans_subtype=? ";
                $args[] = $trans_subtype;
            }
            if ($tenderTotal != "") {
                $tender_clause .= " AND total=-1*? ";
                $args[] = $tenderTotal;
            }
            $tender_clause .= ")";

            /**
              There is no tender restriction
              replace with a not-true statements
              otherwise the OR will match everything
            */
            if ($tender_clause == '( 1=1)') {
                $tender_clause = '1=0';
            }

            $or_clause = '(' . $tender_clause;
            if ($department != "") {
                $or_clause .= " OR department=? ";
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

            $query .= " GROUP BY year(tdate),month(tdate),day(tdate),emp_no,register_no,trans_no ";
            $query .= " ORDER BY year(tdate),month(tdate),day(tdate),emp_no,register_no,trans_no ";

            $prep = $dbc->prepare_statement($query);
            $result = $dbc->exec_statement($prep,$args);
            if (!empty($trans_num) && !empty($date)) {
                header("Location: RenderReceiptPage.php?date=$date&receipt=$trans_num");
                return false;
            } else if ($dbc->num_rows($result) == 0) {
                $this->results = "<b>No receipts match the given criteria</b>";
            } else if ($dbc->num_rows($result) == 1){
                $row = $dbc->fetch_row($result);
                $year = $row[0];
                $month = $row[1];
                $day = $row[2];
                $trans_num = $row[3].'-'.$row[4].'-'.$row[5];
                header("Location: RenderReceiptPage.php?year=$year&month=$month&day=$day&receipt=$trans_num");
                return false;
            } else {
                $this->results = "<b>Matching receipts</b>:<br />";
                while ($row = $dbc->fetch_row($result)) {
                    $year = $row[0];
                    $month = $row[1];
                    $day = $row[2];
                    $trans_num = $row[3].'-'.$row[4].'-'.$row[5];
                    $this->results .= "<a href=RenderReceiptPage.php?year=$year&month=$month&day=$day&receipt=$trans_num>";
                    $this->results .= "$year-$month-$day $trans_num</a><br />";
                }
            }
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
        }';
    }

    function body_content()
    {
        if (!empty($this->results)) {
            return $this->results;
        } else {
            return $this->form_content();
        }
    }

    function form_content()
    {
        global $FANNIE_OP_DB,$FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $depts = "<option value=\"\">Select one...</option>";
        $p = $dbc->prepare_statement("SELECT dept_no,dept_name from departments order by dept_name");
        $r = $dbc->exec_statement($p);
        while($w = $dbc->fetch_row($r)) {
            $depts .= sprintf("<option value=%d>%s</option>",$w[0],$w[1]);
        }
        ob_start();
        ?>
<form action=ReprintReceiptPage.php method=get>
Receipt Search - Fill in any information available
<table id=mytable cellspacing=4 cellpadding=0>
<tr>
    <th>Date*</th><td colspan=2><input type=text name=date size=10 id="date" />
        <input type=text name=date2 size=10 id="date2" /></td>
    <th>Receipt #</th><td><input type=text name=trans_num size=6 /></td>
</tr>
<tr>
    <th>Member #</th><td><input type=text name=card_no size=6 /></td>
    <th>Cashier #</th><td><input type=text name=emp_no size=6 /></td>
    <th>Lane #</th><td><input type=text name=register_no size=6 /></td>
</tr>
<tr>
    <th>Refund</th><td><input type="checkbox" name="is_refund" value="1" /></td>
    <th>Mem Discount</th><td><input type="checkbox" name="mem_discount" value="1" /></td>
</tr>
<tr>
    <th>Tender type</th><td colspan=2><select name=trans_subtype>
        <option value="">Select one...</option>
        <?php
        $numsQ = $dbc->prepare_statement("SELECT TenderCode,TenderName FROM tenders 
            ORDER BY TenderName");
        $numsR = $dbc->exec_statement($numsQ);
        while($numsW = $dbc->fetch_row($numsR)) {
            printf("<option value=%s>%s</option>",$numsW[0],$numsW[1]); 
        }
        ?>
        </select></td>
    <th colspan=2>Tender amount</th><td><input type=text name=tenderTotal size=6 /></td>
</tr>
<tr>
    <th>Department</th><td colspan=2><select name=department><?php echo $depts ?></select></td>
    <td colspan=2><input name=submit type=submit value="Find recipt(s)" /></td>
</tr>

</table>
<i>* If no date is given, all matching receipts from the past 15 days will be returned</i><br />
<b>Tips</b>:<br />
<ul>
<li>A date and a receipt number is sufficient to find any receipt</li>
<li>If you have a receipt number, you don't need to specify a lane or cashier number</li>
<li>ALL fields are optional. You can specify a tender type without an amount (or vice versa)</li>
</ul>
</form>
        <?php
        $this->add_onload_command("\$('#date').datepicker();\n");
        $this->add_onload_command("\$('#date2').datepicker();\n");

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
