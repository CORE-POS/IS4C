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
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class ZipCodeReport extends FannieReportPage {

    public $description = '[Zip Code Report] lists number of customers and sales total by postal code
        for a given date range.';
    public $report_set = 'Membership';

    protected $title = "Fannie : Zip Code Report";
    protected $header = "Zip Code Report";
    protected $required_fields = array('date1', 'date2');

    function fetch_report_data(){
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $type = FormLib::get_form_value('rtype','Purchases');
        $exclude = FormLib::get_form_value('excludes','');

        $ex = preg_split('/\D+/',$exclude, 0, PREG_SPLIT_NO_EMPTY);
        $exCondition = '';
        $exArgs = array();
        foreach($ex as $num){
            $exCondition .= '?,';
            $exArgs[] = $num;
        }
        $exCondition = substr($exCondition, 0, strlen($exCondition)-1);

        $ret = array();
        switch($type){
        case 'Join Date':
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $query = "SELECT CASE WHEN m.zip='' THEN 'none' ELSE m.zip END as zipcode,
                COUNT(*) as num FROM meminfo AS m INNER JOIN memDates AS d
                ON m.card_no=d.card_no WHERE ";
            if (!empty($exArgs))
                $query .= "m.card_no NOT IN ($exCondition) AND ";
            $query .= "d.start_date >= ?
                GROUP BY zipcode
                ORDER BY COUNT(*) DESC";
            $exArgs[] = $date1.' 00:00:00';
            $prep = $dbc->prepare_statement($query);
            $result = $dbc->exec_statement($prep, $exArgs);
            while($row = $dbc->fetch_row($result)){
                $record = array($row['zipcode'], $row['num']);
                $ret[] = $record;
            }
            break;  

        case 'Purchases':
        default:
            $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['WarehouseDatabase']);
            $query = "SELECT CASE WHEN m.zip='' THEN 'none' ELSE m.zip END as zipcode,
                COUNT(*) as num_trans, SUM(total) as spending,
                COUNT(DISTINCT s.card_no) as uniques
                FROM sumMemSalesByDay AS s INNER JOIN "
                .$FANNIE_OP_DB.$dbc->sep()."meminfo AS m 
                ON s.card_no=m.card_no WHERE ";
            if (!empty($exArgs))
                $query .= "s.card_no NOT IN ($exCondition) AND ";
            $query .= "s.date_id BETWEEN ? AND ?
                GROUP BY zipcode
                ORDER BY SUM(total) DESC";
            $date_id1 = date('Ymd',strtotime($date1));
            $date_id2 = date('Ymd',strtotime($date2));
            $exArgs[] = $date_id1;
            $exArgs[] = $date_id2;
            $prep = $dbc->prepare_statement($query);
            $result = $dbc->exec_statement($prep, $exArgs);
            while($row = $dbc->fetch_row($result)){
                $record = array($row['zipcode'],$row['num_trans'],$row['uniques'],$row['spending']);
                $ret[] = $record;
            }
        }

        return $ret;
    }

    function calculate_footers($data){
        switch(count($data[0])){
        case 2:
            $this->report_headers = array('Zip Code', '# of Customers');    
            $this->sort_column = 1;
            $this->sort_direction = 1;
            $sum = 1;
            foreach($data as $row) $sum += $row[1];
            return array('Total', $sum);
        case 4:
        default:
            $this->report_headers = array('Zip Code', '# Transactions', '# of Customers', 'Total $');
            $this->sort_column = 3;
            $this->sort_direction = 1;
            $sumQty = 0.0;
            $sumSales = 0.0;
            $sumUnique = 0.0;
            foreach($data as $row){
                $sumQty += $row[1];
                $sumUnique += $row[2];
                $sumSales += $row[3];
            }
            return array('Total',$sumQty, $sumUnique, $sumSales);
        }
    }

    function form_content(){
        global $FANNIE_URL;
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');
        return '<form action="ZipCodeReport.php" method="get">
            <table>
            <tr>
                <th>Start Date</th>
                <td><input type="text" name="date1" id="date1" /></td>  
                <td rowspan="4">' . FormLib::dateRangePicker() . '</td>
            </tr>
            <tr>
                <th>End Date</th>
                <td><input type="text" name="date2" id="date2" /></td>  
            </tr>
            <tr>
                <th>Based on</th>
                <td><select name="rtype"><option>Purchases</option><option>Join Date</option></select></td>
            </tr>
            <tr>
                <th>Exclude #(s)</th>
                <td><input type="text" name="excludes" /></td>
            </tr>
            <tr>
                <td colspan="2"><input type="submit" value="Get Report" /></td>
            </tr>
            </table>
            </form>';   
    }

}

FannieDispatch::conditionalExec();

?>
