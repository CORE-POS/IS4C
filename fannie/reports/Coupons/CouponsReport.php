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
    public $report_set = 'Tenders';

    protected $title = "Fannie : Coupons Report";
    protected $header = "Coupons Report";
    protected $report_headers = array('UPC', 'Brand', 'Qty', '$ Total');
    protected $required_fields = array('date1', 'date2');

    public function calculate_footers($data)
    {
        $sum = 0;
        $sum2 = 0;
        foreach($data as $row) {
            $sum += $row[1];
            $sum2 += $row[2];
        }

        return array('Total', null, sprintf('%.2f', $sum), sprintf('%.2f',$sum2));
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $d1 = $this->form->date1;
        $d2 = $this->form->date2;

        $dlog = DTransactionsModel::selectDlog($d1,$d2);

        $query = $dbc->prepare("SELECT 
            CASE WHEN upc='0' THEN 'NOT SCANNED' ELSE upc END as upc, 
            sum(CASE WHEN upc='0' THEN 1 ELSE quantity END) as qty,
            sum(-total) as ttl FROM $dlog
            WHERE trans_subtype='CP'
            AND tdate BETWEEN ? AND ?
            GROUP BY upc
            ORDER BY upc");
        $result = $dbc->execute($query, array($d1.' 00:00:00', $d2.' 23:59:59'));

        $brandP = $dbc->prepare('
            SELECT p.brand
            FROM products AS p
            WHERE upc LIKE ?
            GROUP BY p.brand
            ORDER BY COUNT(*) DESC');

        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $prefix = substr($row['upc'], 3, 5);
            $row['brand'] = $dbc->getValue($brandP, array('%' . $prefix . '%'));
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['upc'],
            $row['brand'],
            sprintf('%.2f', $row['qty']),
            sprintf('%.2f', $row['ttl'])
        );
    }

    public function form_content()
    {
        list($lastMonday, $lastSunday) = \COREPOS\Fannie\API\lib\Dates::lastWeek();

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

    public function helpContent()
    {
        return '<p>
            List usage of manufacturer coupons by UPC for
            a given date range. Can be faster than counting
            paper coupons if redemption agency accepts counts
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('upc'=>'4011', 'qty'=>1, 'ttl'=>1, 'brand'=>'n/a');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
        $phpunit->assertInternalType('array', $this->calculate_footers($this->dekey_array(array($data))));
    }
}

FannieDispatch::conditionalExec();

