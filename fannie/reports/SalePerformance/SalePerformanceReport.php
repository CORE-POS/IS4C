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

class SalePerformanceReport extends FannieReportPage
{
    public $description = '[Batch Performance] lists weekly sales totals for a batch.';

    protected $title = "Fannie : Sale Performance";
    protected $header = "Sale Performance";

    protected $report_headers = array('Week Start', 'Week End', 'Batch', 'Qty', '$');

    protected $required_fields = array('ids');

    public function preprocess()
    {
        // custom: ajax lookup up feeds into form fields
        if (FormLib::get('lookup') !== '') {
            echo $this->ajaxCallback();
            return false;
        } 

        return parent::preprocess();
    }

    private function ajaxCallback()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $m = FormLib::get('month', 1);
        $y = FormLib::get('year', date('Y'));
        if (!is_numeric($y)) {
            return "Error: Invalid year";
        } elseif(!is_numeric($m)) {
            return "Error: Invalid month";
        }

        $ret = "<br /><form action=\"SalePerformanceReport.php\" method=\"get\">
                <input type=submit value=\"Get Report\" />";
        $ret .= sprintf("<input type=hidden name=month value=%d />
                <input type=hidden name=year value=%d />",
                $m,$y);
        $ret .= "<table class=\"tablesorter\" cellspacing=0 cellpadding=4 border=1>";
        $ret .= "<tr><th>&nbsp;</th><th>Batch</th><th>Start</th><th>End</th></tr>";
        $q = $dbc->prepare_statement("SELECT batchID,batchName,startDate,endDate FROM
                                batches WHERE discounttype <> 0 AND (
                                (year(startDate)=? and month(startDate)=?) OR
                                (year(endDate)=? and month(endDate)=?)
                                ) ORDER BY startDate,batchType,batchName");
        $r = $dbc->exec_statement($q,array($y,$m,$y,$m));
        while($w = $dbc->fetch_row($r)) {
            list($start, $time) = explode(' ',$w[2], 2);
            list($end, $time) = explode(' ',$w[3], 2);
            $ret .= sprintf("<tr><td><input type=checkbox name=ids[] value=%d /></td>
                    <td>%s</td><td>%s</td><td>%s</td>
                    <input type=hidden name=bnames[] value=\"%s\" /></tr>",
                    $w[0],$w[1],$start,$end,$w[1]." (".$start." ".$end.")");
        }
        $ret .= "</table><br />
            <input type=submit value=\"Get Report\" />
            </form>";

        return $ret;
    }
    
    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $data = array();
        $model = new BatchesModel($dbc); 
        foreach(FormLib::get('ids') as $batchID) {
            $model->batchID($batchID);
            $model->load();
            
            list($start, $time) = explode(' ', $model->startDate(), 2);
            list($end, $time) = explode(' ', $model->endDate(), 2);

            $dlog = DTransactionsModel::selectDlog($start, $end);

            $q = "SELECT MIN(tdate) as weekStart, MAX(tdate) as weekEnd,
                batchName, sum(total) as sales, sum(d.quantity) as qty
                FROM $dlog AS d INNER JOIN
                batchList AS l ON l.upc=d.upc
                LEFT JOIN batches AS b ON l.batchID=b.batchID
                WHERE l.batchID = ?
                AND d.tdate BETWEEN ? AND ?
                GROUP BY ".$dbc->week('tdate').", batchName
                ORDER BY batchName, MIN(tdate)";
            $p = $dbc->prepare_statement($q);
            $r = $dbc->exec_statement($p, array($batchID, $start.' 00:00:00', $end.' 23:59:59'));
            while($w = $dbc->fetch_row($r)) {
                list($s, $time) = explode(' ', $w['weekStart'], 2);
                list($e, $time) = explode(' ', $w['weekEnd'], 2);
                $record = array(
                            $s,
                            $e,
                            $w['batchName'],
                            sprintf('%.2f', $w['qty']),
                            sprintf('%.2f', $w['sales']),
                          );
                $data[] = $record;
            }
        }

        return $data;
    }

    public function javascriptContent()
    {
        if ($this->report_format != 'html') {
            return '';
        }

        ob_start();
        ?>
function lookupSales(){
    var dstr = "lookup=yes&year=";
    dstr += $('#syear').val();
    dstr += "&month="+$('#smonth :selected').val();
    $.ajax({url: 'SalePerformanceReport.php',
        method: 'get',
        cache: false,
        data: dstr,
        success: function(data){
            $('#result').html(data);
        }
    });
}
        <?php
        return ob_get_clean();
    }

    public function form_content()
    {
        ob_start();
        ?>
<div id="#myform">
<select id="smonth">
<?php 
for ($i=1;$i<=12;$i++) {
    printf("<option %s value=%d>%s</option>",
        ($i == date('m') ? 'selected' : ''),
        $i,date("F",mktime(0,0,0,$i,1,2000)));
}
?>
</select>

<input type="text" size="4" id="syear" value="<?php echo date("Y"); ?>" />

<input type="submit" value="Lookup Sales" onclick="lookupSales();" />
</div>
<div id="result"></div>

        <?php
        $this->add_onload_command('lookupSales();');

        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();

?>
