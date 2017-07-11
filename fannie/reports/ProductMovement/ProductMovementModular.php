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

class ProductMovementModular extends FannieReportPage 
{

    protected $title = "Fannie : Product Movement";
    protected $header = "Product Movement Report";
    protected $report_headers = array('Date','UPC','Brand','Description','Qty','$');
    protected $required_fields = array('date1', 'date2', 'upc');

    public $description = '[Product Movement] lists sales for a specific UPC over a given date range.';
    public $report_set = 'Movement Reports';
    public $themed = true;

    protected $new_tablesorter = true;

    function preprocess()
    {
        $ret = parent::preprocess();
        // custom: needs graphing JS/CSS
        if ($this->content_function == 'report_content' && $this->report_format == 'html') {
            $this->add_script('../../src/javascript/d3.js/d3.v3.min.js');
            $this->add_script('../../src/javascript/d3.js/charts/singleline/singleline.js');
            $this->add_css_file('../../src/javascript/d3.js/charts/singleline/singleline.css');
        }

        return $ret;
    }

    public function report_content() 
    {
        $default = parent::report_content();

        if ($this->report_format == 'html') {
            $default .= '<div id="chartDiv"></div>';

            $this->add_onload_command('showGraph()');
        }

        return $default;
    }

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
                    MONTH(t.tdate),
                    DAY(t.tdate),
                    YEAR(t.tdate),
                    t.upc,
                    p.brand,
                    p.description,
                    " . DTrans::sumQuantity('t') . " AS qty,
                    SUM(t.total) AS total
                  FROM $dlog AS t 
                    " . DTrans::joinProducts('t', 'p', 'LEFT') . "
                  WHERE t.upc = ? AND
                    t.tdate BETWEEN ? AND ?
                    AND " . DTrans::isStoreID($store, 't') . "
                  GROUP BY 
                    YEAR(t.tdate),
                    MONTH(t.tdate),
                    DAY(t.tdate),
                    t.upc,
                    p.description
                  ORDER BY year(t.tdate),month(t.tdate),day(t.tdate)";
        $args = array($upc,$date1.' 00:00:00',$date2.' 23:59:59', $store);
    
        if (strtolower($upc) == "rrr" || $upc == "0000000000052"){
            if ($dlog == "dlog_90_view" || $dlog=="dlog_15")
                $dlog = "transarchive";
            else {
                $dlog = "trans_archive.bigArchive";
            }

            $query = "select MONTH(datetime),DAY(datetime),YEAR(datetime),
                upc,'' AS brand,'RRR' AS description,
                sum(case when upc <> 'rrr' then quantity when volSpecial is null or volSpecial > 9999 then 0 else volSpecial end) as qty,
                sum(t.total) AS total from
                $dlog as t
                where upc = ?
                AND datetime BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 't') . "
                and emp_no <> 9999 and register_no <> 99
                and trans_status <> 'X'
                GROUP BY YEAR(datetime),MONTH(datetime),DAY(datetime)
                ORDER BY YEAR(datetime),MONTH(datetime),DAY(datetime)";
            
        } else if (!is_numeric($upc)) {
            $dlog = DTransactionsModel::selectDTrans($date1, $date2);

            $query = "select MONTH(datetime),DAY(datetime),YEAR(datetime),
                upc,'' AS brand, description,
                sum(CASE WHEN quantity=0 THEN 1 ELSE quantity END) as qty,
                sum(t.total) AS total from
                $dlog as t
                where upc = ?
                AND datetime BETWEEN ? AND ?
                AND " . DTrans::isStoreID($store, 't') . "
                and emp_no <> 9999 and register_no <> 99
                and (trans_status <> 'X' || trans_type='L')
                GROUP BY YEAR(datetime),MONTH(datetime),DAY(datetime)";
        }
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
        $record[] = $row[0]."/".$row[1]."/".$row[2];
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
        $divisor = count($data) > 0 ? count($data) : 1;
        return array(
            array('Daily Avg.',null,null,null,round($sumQty/$divisor,2),round($sumSales/$divisor,2)),
            array('Total',null,null,null,$sumQty,$sumSales)
        );
    }

    public function javascriptContent()
    {
        if ($this->report_format != 'html') {
            return;
        }

        ob_start();
        ?>
function showGraph() {
    var ymin = 999999999;
    var ymax = 0;

    var ydata = Array();
    $('td.reportColumn4').each(function(){
        var y = Number($(this).html());
        ydata.push(y);
        if (y > ymax) {
            ymax = y;
        }
        if (y < ymin) {
            ymin = y;
        }
    });

    var xmin = new Date();
    var xmax = new Date(1900, 01, 01); 
    var xdata = Array();
    $('td.reportColumn0').each(function(){
        var x = new Date( Date.parse($(this).html()) );
        xdata.push(x);
        if (x > xmax) {
            xmax = x;
        }
        if (x < xmin) {
            xmin = x;
        }
    });

    var data = Array();
    for (var i=0; i < xdata.length; i++) {
        data.push(Array(xdata[i], ydata[i]));
    }

    singleline(data, Array(xmin, xmax), Array(ymin, ymax), '#chartDiv');
}
        <?php
        return ob_get_clean();
    }

    function form_content()
    {
        global $FANNIE_URL;
        $stores = FormLib::storePicker();
        ob_start();
?>
<form method = "get" action="ProductMovementModular.php" class="form-horizontal">
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
        return '<p>This report shows per-day total sales for
            a given item. You can type in item names to find the
            appropriate UPC if needed.</p>';
    }

    public function unitTest($phpunit)
    {
        $data = array(0=>1, 1=>1, 2=>2000, 'upc'=>'4011', 'brand'=>'test',
            'description'=>'test', 'qty'=>1, 'total'=>1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

