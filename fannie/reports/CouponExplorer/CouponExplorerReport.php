<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class CouponExplorerReport extends FannieReportPage 
{

    protected $title = "Fannie : Coupon Explorer";
    protected $header = "Coupon Explorer Report";
    protected $report_headers = array('Coupon UPC', 'Item UPC', 'Brand','Description','Qty','$');
    protected $required_fields = array('date1', 'date2', 'upc');

    public $description = '[Coupon Explorer] shows which items a coupon was used for';
    public $report_set = 'Tenders';

    protected $new_tablesorter = true;

    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $upc = $this->form->upc;
        if (is_numeric($upc)) {
            $upc = BarcodeLib::padUPC($upc);
        }
        $store = FormLib::get('store', 0);

        $dlog = DTransactionsModel::selectDlog($date1,$date2);

        $query = "SELECT 
                    tdate,
                    trans_num,
                    t.upc
                  FROM $dlog AS t 
                    " . DTrans::joinProducts('t', 'p', 'LEFT') . "
                  WHERE t.upc = ? AND
                    t.tdate BETWEEN ? AND ?
                    AND " . DTrans::isStoreID($store, 't');
        $args = array($upc,$date1.' 00:00:00',$date2.' 23:59:59', $store);
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);

        $itemP = $dbc->prepare("
            SELECT t.upc,
                p.brand,
                p.description,
                SUM(t.quantity) AS qty,
                SUM(total) AS ttl
            FROM $dlog AS t
              " . DTrans::joinProducts('t', 'p', 'LEFT') . "
            WHERE t.upc LIKE ? AND
                t.tdate BETWEEN ? AND ?
                AND trans_num = ?
                AND trans_type = 'I'
                AND " . DTrans::isStoreID($store, 't')
        );
    
        $ret = array();
        while ($row = $dbc->fetchRow($res)) {
            $upc = $row['upc'];
            $date = date('Y-m-d', strtotime($row['tdate']));
            $prefix = '%' . substr($upc, 3, 5) . '%';
            $args = array(
                $prefix,
                $date . ' 00:00:00',
                $date . ' 23:59:59',
                $row['trans_num'],
                $store,
            );
            $item = $dbc->getRow($itemP, $args);
            if (!isset($ret[$item['upc']])) {
                $ret[$item['upc']] = array(
                    'cupc' => $upc,
                    'upc' => $item['upc'],
                    'brand' => $item['brand'],
                    'description' => $item['description'],
                    'qty' => $item['qty'],
                    'ttl' => $item['ttl'],
                );
            } else {
                $ret[$item['upc']]['qty'] += $item['qty'];
                $ret[$item['upc']]['ttl'] += $item['ttl'];
            }
        }

        return $this->dekey_array($ret);
    }

    /**
      Sum the quantity and total columns
    */
    function calculate_footers($data){
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach($data as $row){
            $sumQty += $row[4];
            $sumSales += $row[5];
        }
        return array(
            array('Total',null,null,null,$sumQty,$sumSales)
        );
    }

    function form_content()
    {
        $stores = FormLib::storePicker();
        ob_start();
?>
<form method = "get" action="CouponExplorerReport.php" class="form-horizontal">
    <div class="col-sm-5">
        <div class="form-group"> 
            <label class="control-label col-sm-4">UPC</label>
            <div class="col-sm-8">
                <input type=text name=upc id=upc class="form-control" required />
            </div>
        </div>
        <div class="form-group"> 
            <label class="control-label col-sm-4">Store</label>
            <div class="col-sm-8">
                <?php echo $stores['html']; ?>
            </div>
        </div>
        <div class="form-group"> 
            <label class="control-label col-sm-4">
                <input type="checkbox" name="excel" id="excel" value="xls" /> Excel
            </label>
        </div>
        <div class="form-group"> 
            <button type=submit name=submit value="Submit" class="btn btn-default btn-core">Submit</button>
            <button type=reset name=reset class="btn btn-default btn-reset">Start Over</button>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="form-group">
            <label class="col-sm-4 control-label">Start Date</label>
            <div class="col-sm-8">
                <input type=text id=date1 name=date1 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">End Date</label>
            <div class="col-sm-8">
                <input type=text id=date2 name=date2 class="form-control date-field" required />
            </div>
        </div>
        <div class="form-group">
            <?php echo FormLib::date_range_picker(); ?>
        </div>
    </div>
</form>
<?php
        $this->add_onload_command('$(\'#upc\').focus();');

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>This report shows per-day total sales for
            a given item. You can type in item names to find the
            appropriate UPC if needed.</p>';
    }
}

FannieDispatch::conditionalExec();

