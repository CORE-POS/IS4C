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

class ProductPerCashier extends FannieReportPage 
{

    protected $title = "Fannie : Per-Cashier Movement";
    protected $header = "Per-Cashier Movement Report";
    protected $report_headers = array('Cashier','UPC','Brand','Description','Qty','$');
    protected $required_fields = array('date1', 'date2', 'upc');

    public $description = '[Per-Cashier Movement] lists each cashier\'s sales for a specific UPC over a given date range.';
    public $report_set = 'Movement Reports';

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
                    e.FirstName,
                    t.upc,
                    p.brand,
                    p.description,
                    " . DTrans::sumQuantity('t') . " AS qty,
                    SUM(t.total) AS total
                  FROM $dlog AS t 
                    " . DTrans::joinProducts('t', 'p', 'LEFT') . "
                    INNER JOIN employees AS e ON t.emp_no=e.emp_no 
                  WHERE t.upc = ? AND
                    t.tdate BETWEEN ? AND ?
                    AND " . DTrans::isStoreID($store, 't') . "
                  GROUP BY 
                    e.FirstName,
                    t.upc,
                    p.description
                  ORDER BY year(t.tdate),month(t.tdate),day(t.tdate)";
        $args = array($upc,$date1.' 00:00:00',$date2.' 23:59:59', $store);
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep,$args);

        /**
          Simple report
        
          Issue a query, build array of results
        */
        $ret = array();
        while ($row = $dbc->fetchRow($result)){
            $ret[] = $this->rowToRecord($row);
        }
        return $ret;
    }

    private function rowToRecord($row)
    {
        $record = array();
        $record[] = $row['FirstName'];
        $record[] = $row['upc'];
        $record[] = $row['brand'] === null ? '' : $row['brand'];
        $record[] = $row['description'] === null ? '' : $row['description'];
        $record[] = sprintf('%.2f', $row['qty']);
        $record[] = sprintf('%.2f', $row['total']);

        return $record;
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
        return array('Total',null,null,null,$sumQty,$sumSales);
    }

    function form_content()
    {
        global $FANNIE_URL;
        $stores = FormLib::storePicker();
        ob_start();
?>
<form method = "get" action="ProductPerCashier.php" class="form-horizontal">
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
        $this->add_script($FANNIE_URL . 'item/autocomplete.js');
        $ws = $FANNIE_URL . 'ws/';
        $this->add_onload_command("bindAutoComplete('#upc', '$ws', 'item');\n");
        $this->add_onload_command('$(\'#upc\').focus();');

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>This report shows per-cashier total sales for
            a given item. You can type in item names to find the
            appropriate UPC if needed.</p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('upc'=>'4011', 'brand'=>'test',
            'description'=>'test', 'qty'=>1, 'total'=>1, 'FirstName'=>'Foo');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

