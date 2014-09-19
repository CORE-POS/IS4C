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

class TrendsReport extends FannieReportPage
{
    protected $title = "Fannie : Trends";
    protected $header = "Trend Report";

    protected $required_fields = array('date1', 'date2');

    public $description = '[Trends] shows daily sales totals for items over a given date range. Items can be included by UPC, department, or manufacturer.';
    public $report_set = 'Movement Reports';

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $date1 = FormLib::get('date1', date('Y-m-d'));
        $date2 = FormLib::get('date2', date('Y-m-d'));
        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        
        $joins = '';
        $where = '1=1';
        $groupby = 'd.upc, CASE WHEN p.description IS NULL THEN d.description ELSE p.description END';
        $args = array($date1.' 00:00:00', $date2.' 23:59:59');
        switch (FormLib::get('type', 'dept')) {
            case 'dept':
                $where = 'd.department BETWEEN ? AND ?';
                $args[] = FormLib::get('dept1', 0);
                $args[] = FormLib::get('dept2', 0);
                break;
            case 'manu':
                if (FormLib::get('mtype', 'name') == "name") {
                    $args = array($manufacturer);
                    $where = 'p.brand = ?';
                    $args[] = FormLib::get('manufacturer');
                } else {
                    $where = 'd.upc LIKE ?';
                    $args[] = '%'.FormLib::get('manufacturer').'%';
                }
                break;
            case 'upc':
                $where = 'd.upc = ?';
                $args[] = BarcodeLib::padUPC(FormLib::get('upc'));
                break;
            case 'likecode':
                $joins = 'LEFT JOIN upcLike AS u ON d.upc=u.upc
                          LEFT JOIN likeCodes AS l ON u.likeCode=l.likeCode';
                $groupby = 'u.likeCode, l.likeCodeDesc';
                $where = 'u.likeCode BETWEEN ? AND ?';
                $args[] = FormLib::get('likeCode1', 0);
                $args[] = FormLib::get('likeCode2', 0);
                break;
        }

        $query = "
            SELECT 
                YEAR(d.tdate) AS year,
                MONTH(d.tdate) AS month,
                DAY(d.tdate) AS day,
                $groupby, "
                . DTrans::sumQuantity('d') . " AS total
            FROM $dlog as d "
                . DTrans::joinProducts('d', 'p')
                . $joins . "
            WHERE d.tdate BETWEEN ? AND ?
                AND trans_status <> 'M'
                AND trans_type = 'I'
                AND $where
            GROUP BY YEAR(d.tdate),
                MONTH(d.tdate),
                DAY(d.tdate),
                $groupby
            ORDER BY d.upc,
                YEAR(d.tdate),
                MONTH(d.tdate),
                DAY(d.tdate)";
        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep,$args);
    
        // variable columns. one per dates
        $dates = array();
        while($date1 != $date2) {
            $dates[] =  $date1;
            $parts = explode("-",$date1);
            if (count($parts) != 3) break;
            $date1 = date("Y-m-d",mktime(0,0,0,$parts[1],$parts[2]+1,$parts[0]));
        } 
        $dates[] = $date2;
        
        $this->report_headers = array('UPC', 'Description');
        foreach ($dates as $i) {
            $this->report_headers[] = $i;
        }
        $this->report_headers[] = 'Total';
    
        $current = array('upc'=>'', 'description'=>'');
        $data = array();
        // track upc while going through the rows, storing 
        // all data about a given upc before printing
        while ($row = $dbc->fetch_array($result)){  
            if ($current['upc'] != $row[3]){
                if ($current['upc'] != ""){
                    $record = array($current['upc'], $current['description']);
                    $sum = 0.0;
                    foreach ($dates as $i){
                        if (isset($current[$i])){
                            $record[] = sprintf('%.2f', $current[$i]);
                            $sum += $current[$i];
                        } else {
                            $record[] = 0.0;
                        }
                    }
                    $record[] = sprintf('%.2f', $sum);
                    $data[] = $record;
                }
                // update 'current' values and clear data
                $current = array('upc'=>$row[3], 'description'=>$row[4]);
            }
            // get a yyyy-mm-dd format date from sql results
            $year = $row['year'];
            $month = str_pad($row['month'],2,'0',STR_PAD_LEFT);
            $day = str_pad($row['day'],2,'0',STR_PAD_LEFT);
            $datestr = $year."-".$month."-".$day;
            
            // index result into data based on date string
            // this is to properly place data in the output table
            // even when there are 'missing' days for a given upc
            $current[$datestr] = $row['total'];
        }

        // add the last data set
        $record = array($current['upc'], $current['description']);
        $sum = 0.0;
        foreach ($dates as $i){
            if (isset($current[$i])){
                $record[] = sprintf('%.2f', $current[$i]);
                $sum += $current[$i];
            } else {
                $record[] = 0.0;
            }
        }
        $record[] = sprintf('%.2f', $sum);
        $data[] = $record;

        return $data;
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $deptsQ = $dbc->prepare_statement("select dept_no,dept_name from departments order by dept_no");
        $deptsR = $dbc->exec_statement($deptsQ);
        $deptsList = "";
        while ($deptsW = $dbc->fetch_array($deptsR)) {
          $deptsList .= "<option value=$deptsW[0]>$deptsW[0] $deptsW[1]</option>";  
        }
        $this->add_onload_command('doShow();');

        ob_start();
        ?>
<script type="text/javascript">
function swap(src,dst){
    var val = document.getElementById(src).value;
    document.getElementById(dst).value = val;
}

function doShow(){
    var which = "dept";
    var selected = $("input[type='radio'][name='type']:checked");
    if (selected.length > 0) {
            which = selected.val();
    }
    $('.deptField').hide();
    $('.upcField').hide();
    $('.lcField').hide();
    $('.manuField').hide();
    if (which == "manu") {
        $('.manuField').show();
    } else if (which == "dept") {
        $('.deptField').show();
    } else if (which == "upc") {
        $('.upcField').show();
    } else if (which == "likecode") {
        $('.lcField').show();
    }
}
</script>

<form method=get action=TrendsReport.php>
<b>Type</b>: <input type=radio name=type id=rt1 checked value=dept onchange="doShow();" /><label for="rt1">Department</label>
<input type=radio name=type id=rt2 value=manu onchange="doShow();" /><label for="rt2"><?php echo _('Manufacturer'); ?></label>
<input type=radio name=type id=rt3 value=upc onchange="doShow();" /><label for="rt3">Single item </label>
<input type=radio name=type id=rt4 value=likecode onchange="doShow();" /><label for="rt4">Like code</label><br />

<table>
<tr>
    <td class="deptField">Start department:</td><td class="deptField">
    <select id=dept1Sel onchange="swap('dept1Sel','dept1');">
    <?php echo $deptsList ?>
    </select>
    <input type=text name=dept1 id=dept1 size=5 value=1 />
    </td>

    <td class="manuField"><?php echo _('Manufacturer'); ?>:</td><td class="manuField">
    <input type=text name=manufacturer />
    </td>

    <td class="upcField">UPC:</td><td class="upcField">
    <input type=text name=upc />
    </td>

    <td class="lcField">LikeCode Start:</td><td class="lcField">
    <input type=text name=likeCode />
    </td>

    <td>Start date:</td><td><input type=text id=date1 name=date1 /></td></tr>

    <tr>
    <td class="deptField">End department:</td><td class="deptField">
    <select id=dept2Sel onchange="swap('dept2Sel','dept2');">
    <?php echo $deptsList ?>
    </select>
    <input type=text name=dept2 id=dept2 size=5 value=1 />
    </td>

    <td class="manuField" colspan="2">
    <input type=radio name=mtype value=name checked />Name
    <input type=radio name=mtype value=prefix />UPC prefix
    </td>

    <td class="upcField" colspan="2"></td>

    <td class="lcField">LikeCode End:</td><td class="lcField">
    <input type=text name=likeCode2 />
    </td>

    <td>End date:</td><td><input type=text id=date2 name=date2 /></td></tr>

</tr></table>
<br />
<table>
<tr>
    <td>
    Excel <input type=checkbox name=excel value="xls" /><br />
    <input type=submit value=Submit />
    </td><td>
    <?php echo FormLib::date_range_picker(); ?>
    </td>
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
