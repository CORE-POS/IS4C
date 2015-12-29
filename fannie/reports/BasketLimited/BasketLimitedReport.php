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

class BasketLimitedReport extends FannieReportPage 
{

    public $description = '[Small Basket Report] lists sales for transactions containing a limited
    number of items - i.e., what do people buy when they\'re only purchasing one or two things?';
    public $themed = true;
    public $report_set = 'Transaction Reports';

    protected $report_headers = array('UPC', 'Description', '# Trans', 'Qty', '$');
    protected $sort_column = 2;
    protected $sort_direction = 1;
    protected $report_cache = 'day';
    protected $title = "Fannie : Basket Limited Report";
    protected $header = "Basket Limited Report Report";
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $date1 = $this->form->date1;
        $date2 = $this->form->date2;
        $qty = FormLib::get('qty', 1);

        $create = $dbc->prepare("CREATE TABLE groupingTempBS (upc VARCHAR(13), quantity double, total decimal(10,2), trans_num varchar(50))");
        $dbc->execute($create);

        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $setupQ = $dbc->prepare("INSERT INTO groupingTempBS
            SELECT upc, quantity, total, trans_num
            FROM $dlog AS d WHERE tdate BETWEEN ? AND ?
            AND trans_type IN ('I','D')
            GROUP BY year(tdate),month(tdate),day(tdate),trans_num 
            HAVING COUNT(*) <= ?");
        $dbc->execute($setupQ,array($date1.' 00:00:00',$date2.' 23:59:59',$qty));

        $reportQ = $dbc->prepare('
            SELECT g.upc,
                p.description,
                SUM(g.quantity) AS qty,
                COUNT(DISTINCT trans_num) AS num,
                SUM(total) AS ttl
            FROM groupingTempBS as g '
                . DTrans::joinProducts('g', 'p') . '
            GROUP BY g.upc,
                p.description
            HAVING sum(total) <> 0
            ORDER BY count(*) DESC
        ');
        $reportR = $dbc->execute($reportQ);

        $data = array();
        while($w = $dbc->fetch_row($reportR)) {
            $record = array($w['upc'], 
                            empty($w['description']) ? 'n/a' : $w['description'], 
                            $w[3], 
                            sprintf('%.2f',$w[2]), 
                            sprintf('%.2f',$w[4]));
            $data[] = $record;
        }

        $drop = $dbc->prepare("DROP TABLE groupingTempBS");
        $dbc->execute($drop);

        return $data;
    }

    public function report_description_content()
    {
        return array(
            'Basket Size '.FormLib::get('qty', 1).' or less'
        );
    }
    
    public function form_content()
    {
        ob_start();
?>
<form method="get" action="BasketLimitedReport.php" class="form-horizontal">
<div class="col-sm-5">
    <div class="form-group">
        <label class="control-label col-sm-4">Size Limit (Qty)</label>
        <div class="col-sm-8">
            <input type=text name=qty id=qty value="1" 
                class="form-control" required />
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-sm-4">Start Date</label>
        <div class="col-sm-8">
            <input type=text id=date1 name=date1 
                class="form-control date-field" required />
        </div>
    </div>
    <div class="form-group">
        <label class="control-label col-sm-4">End Date</label>
        <div class="col-sm-8">
            <input type=text id=date2 name=date2 
                class="form-control date-field" required />
        </div>
    </div>
    <div class="form-group">
        <label class="col-sm-8">
            <input type="checkbox" name="excel" value="xls" />
            Excel
        </label>
    </div>
    <p>
        <button class="btn btn-default btn-core" type="submit">Submit</button>
        <button class="btn btn-default btn-reset" type="reset">Start Over</button>
    </p>
</div>
<div class="col-sm-5">
    <div class="form-group">
        <?php echo FormLib::date_range_picker(); ?>
    </div>
</div>
</form>
<?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            This report shows per-item sales for transactions
            containing a specific number of items or fewer.
            Canonically, if customers are buying just a single item,
            which item(s) are they buying most often?
            </p>';
    }
}

FannieDispatch::conditionalExec();

