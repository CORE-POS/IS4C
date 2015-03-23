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

class CouponsReport extends FannieReportPage {

    public $description = '[Manufacturer Coupons] lists coupons totals by UPC for a given date range.';
    public $themed = true;

    protected $title = "Fannie : Coupons Report";
    protected $header = "Coupons Report";
    protected $report_headers = array('UPC', 'Qty', '$ Total');
    protected $required_fields = array('date1', 'date2');

    public function calculate_footers($data)
    {
        $sum = 0;
        $sum2 = 0;
        foreach($data as $row) {
            $sum += $row[1];
            $sum2 += $row[2];
        }

        return array('Total', sprintf('%.2f', $sum), sprintf('%.2f',$sum2));
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $d1 = FormLib::get('date1', date('Y-m-d'));
        $d2 = FormLib::get('date2', date('Y-m-d'));

        $dlog = DTransactionsModel::selectDlog($d1,$d2);

        $query = $dbc->prepare_statement("SELECT 
            CASE WHEN upc='0' THEN 'NOT SCANNED' ELSE upc END as upc, 
            sum(CASE WHEN upc='0' THEN 1 ELSE quantity END) as qty,
            sum(-total) as ttl FROM $dlog
            WHERE trans_subtype='CP'
            AND tdate BETWEEN ? AND ?
            GROUP BY upc
            ORDER BY upc");
        $result = $dbc->exec_statement($query, array($d1.' 00:00:00', $d2.' 23:59:59'));

        $data = array();
        while($row = $dbc->fetch_row($result)){
            $data[] = array(
                        $row['upc'],
                        sprintf('%.2f', $row['qty']),
                        sprintf('%.2f', $row['ttl'])
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
<form action=CouponsReport.php method=get>
<div class="col-sm-4">
<div class="form-group">
    <label>Start Date</label>
    <input type=text id="date1" name=date1 
        class="form-control date-field" value="<?php echo $lastMonday; ?>" />
</div>
<div class="form-group">
    <label>End Date</label>
    <input type=text id="date2" name=date2 
        class="form-control date-field" value="<?php echo $lastSunday; ?>" />
</div>
<div class="form-group">
    <label>
        Excel <input type=checkbox name=excel value="xls" />
    </label>
    <button type=submit name=submit value="Submit" 
        class="btn btn-default">Submit</button>
</div>
</div>
<div class="col-sm-4">
<?php echo FormLib::date_range_picker(); ?>
</div>
</form>
        <?php

        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();

?>
