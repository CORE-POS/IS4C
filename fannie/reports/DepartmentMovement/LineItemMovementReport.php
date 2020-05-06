<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LineItemMovementReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Line Item Discount Movement Report";
    protected $header = "Line Item Discount Movement Report";

    protected $required_fields = array('date1', 'date2');

    public $description = '[Line Item Discount Movement] shows movement for service-scale items that have been reduced';
    public $report_set = 'Movement Reports';
    protected $report_headers = array('UPC', 'Brand', 'Item', 'Qty', '$', 'Reduced Qty', 'RQ%', 'Reduced $', 'R$%');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $query = '';
        $from_where = FormLib::standardItemFromWhere();
        $query = "
            SELECT t.upc,
                COALESCE(p.brand, '') AS brand,
                CASE WHEN p.description IS NULL THEN t.description ELSE p.description END as description, "
                . DTrans::sumQuantity('t')." as qty,
                SUM(t.total) AS total,
                SUM(CASE 
                    WHEN trans_status='M' OR trans_subtype='OG' THEN 0
                    WHEN charflag='PO' AND unitPrice=0.01 THEN 1
                    WHEN charflag='PO' AND unitPrice<>0.01 THEN t.quantity
                    ELSE 0
                END) as reducedQty,
                SUM(CASE WHEN charflag='PO' THEN total ELSE 0 END) AS reducedTTL
            " . $from_where['query'] . "
                AND t.upc LIKE '002%'
            GROUP BY t.upc,
                COALESCE(p.brand, ''),
                CASE WHEN p.description IS NULL THEN t.description ELSE p.description END
            ORDER BY SUM(CASE WHEN charflag='RD' THEN total ELSE 0 END) DESC";

        $prep = $dbc->prepare($query);
        try {
            $result = $dbc->execute($prep, $from_where['args']);
        } catch (Exception $ex) {
            // MySQL 5.6 doesn't GROUP BY correctly
            return array();
        }
        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['total']),
                sprintf('%.2f', $row['reducedQty']),
                $this->percent($row['reducedQty'], $row['qty']),
                sprintf('%.2f', $row['reducedTTL']),
                $this->percent($row['reducedTTL'], $row['total']),
            );
        }

        return $data;
    }

    private function percent($a, $b)
    {
        if ($b == 0) return 0;
        return sprintf('%.2f', 100 * ($a/$b));
    }

    public function form_content()
    {
        ob_start();
        ?>
        <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <div class="row">
            <?php echo FormLib::standardItemFields(); ?>
            <?php echo FormLib::standardDateFields(); ?>
        </div>
        <div class="row form-horizontal">
            <div class="form-group">
                <label class="col-sm-1 control-label">Store</label>
                <div class="col-sm-2">
                    <?php $s = FormLib::storePicker(); echo $s['html']; ?>
                </div>
                <label class="col-sm-1 control-label">
                    <input type="checkbox" name="excel" value="csv" />
                    Excel
                </label>
            </div>
        </div>
        <p>
            <button type="submit" class="btn btn-default btn-core">Get Report</button>
            <button type="reset" class="btn btn-default btn-reset">Reset Form</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

