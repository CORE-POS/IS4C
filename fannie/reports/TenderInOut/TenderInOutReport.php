<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class TenderInOutReport extends FannieReportPage
{
    public $description = '[Tender Usages] lists each transaction for a given tender in a given date range.';
    public $report_set = 'Tenders';

    protected $title = "Fannie : Tender Usage";
    protected $header = "Tender Usage Report";

    protected $report_headers = array('Date', 'Receipt#', 'Employee', 'Register', 'Amount');
    protected $required_fields = array('date1', 'date2');

    public function report_description_content()
    {
        $code = FormLib::get('tendercode');

        return array(
            'For tender '.$code,
        );
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $code = FormLib::get('tendercode');

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $query = $dbc->prepare_statement("select tdate,trans_num,-total as total,emp_no, register_no
              FROM $dlog as t 
              where t.trans_subtype = ? AND
              trans_type='T' AND
              tdate BETWEEN ? AND ?
              AND total <> 0
              order by tdate");
        $result = $dbc->exec_statement($query,array($code,$date1.' 00:00:00',$date2.' 23:59:59'));


        $data = array();
        while ($row = $dbc->fetch_array($result)) {
            $record = array(
                $row['tdate'],
                $row['trans_num'],
                $row['emp_no'],
                $row['register_no'],
                $row['total'],
            );
            $data[] = $record;
        }

        return $data;
    }

    public function calculate_footers($data)
    {
        $sum = 0.0;
        foreach($data as $row) {
            $sum += $row[4];
        }

        return array('Total', '', null, null, $sum);
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $tenders = array();
        $p = $dbc->prepare_statement("SELECT TenderCode,TenderName FROM tenders ORDER BY TenderName");
        $r = $dbc->exec_statement($p);
        while($w = $dbc->fetch_row($r)) {
            $tenders[$w['TenderCode']] = $w['TenderName'];
        }

        ob_start();
        ?>
<div id=main>   
<form method = "get" action="TenderInOutReport.php">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr> 
            <td> <p><b>Tender</b></p>
            <p><b>Excel</b></p>
            </td>
            <td><p>
            <select name="tendercode">
            <?php foreach($tenders as $code=>$name) {
                printf('<option value="%s">%s</option>',$code,$name);
            } ?>
            </select>
            </p>
            <p>
            <input type=checkbox name=excel id=excel value=xls /> 
            </p>
            </td>

             <td>
            <p><b>Date Start</b> </p>
                 <p><b>End</b></p>
               </td>
                    <td>
                     <p>
                       <input type=text size=25 name=date1 id="date1" />
                       </p>
                       <p>
                        <input type=text size=25 name=date2 id="date2" />
                 </p>
               </td>

        </tr>
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
            <td colspan="2"><?php echo FormLib::date_range_picker(); ?></td>
        </tr>
    </table>
</form>
</div>
        <?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

?>
