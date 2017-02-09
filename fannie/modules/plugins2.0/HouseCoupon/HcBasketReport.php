<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class HcBasketReport extends FannieReportPage
{
    protected $header = 'Coupon Basket Report';
    protected $title = 'Coupon Basket Report';
    protected $required_fields = array('date1', 'date2', 'upc');
    protected $report_headers = array('Date', 'Transaction', 'Total');
    public $description = '[Coupon Basket Report] shows invidual transactions containing a given store coupon.';

    function fetch_report_data()
    {
        try {
            $dlog = DTransactionsModel::selectDlog($this->form->date1, $this->form->date2);
            $upc = BarcodeLib::padUPC($this->form->upc);
        } catch (Exception $ex) {
            return array();
        }
        $dbc = $this->connection;
        $store = FormLib::get('store', 0);

        $baseP = $dbc->prepare('
            SELECT trans_num,
                YEAR(tdate) AS year,
                DAY(tdate) AS day,
                MONTH(tdate) AS month
            FROM ' . $dlog . ' AS d
            WHERE tdate BETWEEN ? AND ?
                AND upc=?
                AND ' . DTrans::isStoreID($store, 'd') . '
            GROUP BY trans_num,
                YEAR(tdate),
                MONTH(tdate),
                DAY(tdate)
            HAVING SUM(total) <> 0');
        $detailP = $dbc->prepare('
            SELECT retailTotal
            FROM core_warehouse.transactionSummary
            WHERE date_id=?
                AND trans_num=?
        ');

        $baseR = $dbc->execute($baseP, array($this->form->date1 . ' 00:00:00', $this->form->date2 . ' 23:59:59', $upc, $store));
        $data = array();
        while ($baseW = $dbc->fetchRow($baseR)) {
            $date = date('Y-m-d', mktime(0, 0, 0, $baseW['month'], $baseW['day'], $baseW['year']));
            $dateID = date('Ymd', mktime(0, 0, 0, $baseW['month'], $baseW['day'], $baseW['year']));
            $detail = $dbc->getValue($detailP, array($dateID, $baseW['trans_num']));
            $data[] = array(
                $date,
                $baseW['trans_num'],
                sprintf('%.2f', $detail),
            );
        }

        return $data;
    }

    function calculate_footers($data)
    {
        $sum = array_reduce($data, function($c, $i) { return $c + $i[2]; }, 0);
        return array('Average', null, sprintf('%.2f', $sum / count($data)));
    }

    function form_content()
    {
        $stores = FormLib::storePicker();
        ob_start();
?>
<form method="get" action="HcBasketReport.php" class="form-horizontal">
    <div class="col-sm-5">
        <div class="form-group"> 
            <label class="control-label col-sm-4">Coupon UPC</label>
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
        return ob_get_clean();
    }


}

FannieDispatch::conditionalExec();

