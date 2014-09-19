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

class ProductMovementModular extends FannieReportPage 
{

    protected $title = "Fannie : Product Movement";
    protected $header = "Product Movement Report";
    protected $report_headers = array('Date','UPC','Description','Qty','$');
    protected $required_fields = array('date1', 'date2');

    public $description = '[Product Movement] lists sales for a specific UPC over a given date range.';
    public $report_set = 'Movement Reports';

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
        global $FANNIE_OP_DB, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $date2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $upc = FormLib::get_form_value('upc','0');
        if (is_numeric($upc))
            $upc = BarcodeLib::padUPC($upc);

        $dlog = DTransactionsModel::selectDlog($date1,$date2);
        $sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep()."sumUpcSalesByDay";

        $query = "SELECT 
                    MONTH(t.tdate),
                    DAY(t.tdate),
                    YEAR(t.tdate),
                    t.upc,
                    p.description,
                    " . DTrans::sumQuantity('t') . " AS qty,
                    SUM(t.total) AS total
                  FROM $dlog AS t 
                    " . DTrans::joinProducts('t', 'p') . "
                  WHERE t.upc = ? AND
                    t.tdate BETWEEN ? AND ?
                  GROUP BY 
                    YEAR(t.tdate),
                    MONTH(t.tdate),
                    DAY(t.tdate),
                    t.upc,
                    p.description
                  ORDER BY year(t.tdate),month(t.tdate),day(t.tdate)";
        $args = array($upc,$date1.' 00:00:00',$date2.' 23:59:59');
    
        if (strtolower($upc) == "rrr" || $upc == "0000000000052"){
            if ($dlog == "dlog_90_view" || $dlog=="dlog_15")
                $dlog = "transarchive";
            else {
                $dlog = "trans_archive.bigArchive";
            }

            $query = "select MONTH(datetime),DAY(datetime),YEAR(datetime),
                upc,'RRR',
                sum(case when upc <> 'rrr' then quantity when volSpecial is null or volSpecial > 9999 then 0 else volSpecial end) as qty,
                sum(t.total) AS total from
                $dlog as t
                where upc = ?
                AND datetime BETWEEN ? AND ?
                and emp_no <> 9999 and register_no <> 99
                and trans_status <> 'X'
                GROUP BY YEAR(datetime),MONTH(datetime),DAY(datetime)
                ORDER BY YEAR(datetime),MONTH(datetime),DAY(datetime)";
            
        } else if (!is_numeric($upc)) {
            $dlog = DTransactionsModel::selectDTrans($date1, $date2);

            $query = "select MONTH(datetime),DAY(datetime),YEAR(datetime),
                upc,description,
                sum(CASE WHEN quantity=0 THEN 1 ELSE quantity END) as qty,
                sum(t.total) AS total from
                $dlog as t
                where upc = ?
                AND datetime BETWEEN ? AND ?
                and emp_no <> 9999 and register_no <> 99
                and (trans_status <> 'X' || trans_type='L')
                GROUP BY YEAR(datetime),MONTH(datetime),DAY(datetime)";
        }
        $prep = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($prep,$args);

        /**
          Simple report
        
          Issue a query, build array of results
        */
        $ret = array();
        while ($row = $dbc->fetch_array($result)){
            $record = array();
            $record[] = $row[0]."/".$row[1]."/".$row[2];
            $record[] = $row['upc'];
            $record[] = $row['description'];
            $record[] = sprintf('%.2f', $row['qty']);
            $record[] = sprintf('%.2f', $row['total']);
            $ret[] = $record;
        }
        return $ret;
    }
    
    /**
      Sum the quantity and total columns
    */
    function calculate_footers($data){
        $sumQty = 0.0;
        $sumSales = 0.0;
        foreach($data as $row){
            $sumQty += $row[3];
            $sumSales += $row[4];
        }
        return array('Total',null,null,$sumQty,$sumSales);
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
?>
<div id=main>   
<form method = "get" action="ProductMovementModular.php">
    <table border="0" cellspacing="0" cellpadding="5">
        <tr> 
            <th>UPC</th>
            <td>
            <input type=text name=upc size=14 id=upc  />
            </td>
            <td>
            <input type="checkbox" name="excel" id="excel" value="xls" />
            <label for="excel">Excel</label>
            </td>   
        </tr>
        <tr>
            <th>Date Start</th>
            <td>    
                       <input type=text size=14 id=date1 name=date1 />
            </td>
            <td rowspan="3">
            <?php echo FormLib::date_range_picker(); ?>
            </td>
        </tr>
        <tr>
            <th>Date End</th>
            <td>
                        <input type=text size=14 id=date2 name=date2 />
               </td>

        </tr>
        <tr>
            <td> <input type=submit name=submit value="Submit"> </td>
            <td> <input type=reset name=reset value="Start Over"> </td>
        </tr>
    </table>
</form>
</div>
<?php
        $this->add_onload_command('$(\'#date1\').datepicker();');
        $this->add_onload_command('$(\'#date2\').datepicker();');
        $this->add_script($FANNIE_URL . 'item/autocomplete.js');
        $ws = $FANNIE_URL . 'ws/';
        $this->add_onload_command("bindAutoComplete('#upc', '$ws', 'item');\n");
        $this->add_onload_command('$(\'#upc\').focus();');
    }
}

FannieDispatch::conditionalExec(false);

?>
