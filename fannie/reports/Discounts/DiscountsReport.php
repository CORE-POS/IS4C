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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DiscountsReport extends FannieReportPage {

    public $description = '[Discounts Reports] lists member percentage discounts by member type for a
        a given date range.';
    public $report_set = 'Membership';

    protected $report_headers = array('Type', 'Total');
    protected $title = "Fannie : Discounts Report";
    protected $header = "Discount Report";
    protected $required_fields = array('date1', 'date2');

    public function calculate_footers($data)
    {
        $sum = 0;
        foreach($data as $row) {
            $sum += $row[1];
        }

        return array('Total', sprintf('%.2f', $sum));
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $d1 = FormLib::get('date1', date('Y-m-d'));
        $d2 = FormLib::get('date2', date('Y-m-d'));

        $dlog = DTransactionsModel::selectDlog($d1,$d2);

        $query = $dbc->prepare_statement("SELECT m.memDesc,sum(total) as total FROM $dlog AS d
            LEFT JOIN custdata AS c ON d.card_no=c.CardNo
            AND c.personNum=1 LEFT JOIN memtype AS m ON
            c.memType=m.memtype
            WHERE d.upc='DISCOUNT'
            AND tdate BETWEEN ? AND ?
            GROUP BY m.memDesc
            ORDER BY m.memDesc");
        $result = $dbc->exec_statement($query, array($d1.' 00:00:00', $d2.' 23:59:59'));

        $data = array();
        while($row = $dbc->fetch_row($result)){
            $data[] = array(
                        $row['memDesc'],
                        sprintf('%.2f', $row['total'])
                        );
        }

        return $data;
    }

    public function form_content()
    {
        $lastMonday = "";
        $lastSunday = "";

        $ts = mktime(0,0,0,date("n"),date("j")-1,date("Y"));
        while($lastMonday == "" || $lastSunday == "") {
            if (date("w",$ts) == 1 && $lastSunday != "") {
                $lastMonday = date("Y-m-d",$ts);
            } elseif(date("w",$ts) == 0) {
                $lastSunday = date("Y-m-d",$ts);
            }
            $ts = mktime(0,0,0,date("n",$ts),date("j",$ts)-1,date("Y",$ts));    
        }

        ob_start();
        ?>
<form action=DiscountsReport.php method=get>
<table cellspacing=4 cellpadding=4>
<tr>
<th>Start Date</th>
<td><input type=text id="date1" name=date1 value="<?php echo $lastMonday; ?>" /></td>
<td rowspan="3">
<?php echo FormLib::date_range_picker(); ?>
</td>
</tr><tr>
<th>End Date</th>
<td><input type=text id="date2" name=date2 value="<?php echo $lastSunday; ?>" /></td>
</tr><tr>
<td>Excel <input type=checkbox name=excel value="xls" /></td>
<td><input type=submit name=submit value="Submit" /></td>
</tr>
</table>
</form>
        <?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');

        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();

?>
