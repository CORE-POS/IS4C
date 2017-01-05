<?php
include('../../../../config.php');
if (!class_exists('FannieAPI.php')) {
    include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');
}

class MarginStudyReport extends FannieReportPage
{
    protected $report_headers = array('Category-Store', 'Raw Sales', '% On Sale', '% Open Ring', '% SPO', '% DDD', 'Est. UNFI Sales', '% UNFI', 'Est. UNFI Purch', 'UNFI Margin');
    protected $required_fields = array('date1', 'date2');
    protected $title = 'Margin Study Report';
    protected $header = 'Margin Study Report';

    public function report_content() 
    {
        $default = parent::report_content();

        if ($this->report_format == 'html') {
            $default .= '<div id="chartDiv"></div>';
            $this->add_onload_command('showGraph()');
        }

        return $default;
    }

    private $COA= array(
    '41201' => 'DELI PREPARED',
    '41205' => 'DELI CHEESE',
    '41300' => 'PRODUCE',
    '41305' => 'GEN MERCH/SEEDS',
    '41310' => 'PRODUCE/TRANSPLANTS',
    '41315' => 'GEN MERCH/FLOWERS',
    '41400' => 'GROCERY',
    '41405' => 'GROCERY/PET/PAPER',
    '41407' => 'GROCERY/BULK WATER',
    '41410' => 'BULK A',
    '41415' => 'BULK B',
    '41420' => 'COOL/GROCERY',
    '41425' => 'COOL/BUTTER',
    '41430' => 'COOL/MILK',
    '41435' => 'FROZEN/GROCERY',
    '41500' => 'HABA/SPICES',
    '41505' => 'HABA/COFFEE',
    '41510' => 'HABA/BODY CARE',
    '41515' => 'HABA/VIT SUPPL',
    '41520' => 'GEN MERCH/BOOKS',
    '41600' => 'OUTSIDE BAKERY',
    '41605' => 'GEN MERCH/HOUSEWARES',
    '41610' => 'MARKETING',
    '41640' => 'GEN MERCH/CARDS',
    '41645' => 'GEN MERCH/MAGAZINES',
    '41700' => 'MEAT/FRESH',
    '41705' => 'MEAT/FROZEN',
    );

    public function fetch_report_data()
    {
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
        } catch (Exception $ex) {
            return array();
        }

        $poP = $this->connection->prepare("
            SELECT SUM(receivedTotalCost) AS ttl
            FROM PurchaseOrderItems AS i
                INNER JOIN PurchaseOrder AS o ON i.orderID=o.orderID
            WHERE i.receivedDate BETWEEN ? AND ?
                AND o.storeID=?
                AND o.vendorID=1
                AND i.salesCode=?");
        
        $dlog = DTransactionsModel::selectDlog($date1, $date2);
        $dtrans = DTransactionsModel::selectDtrans($date1, $date2);
        $shrinkP = $this->connection->prepare("
            SELECT SUM(total)
            FROM {$dtrans} AS t
                INNER JOIN departments AS d ON t.department=d.dept_no
            WHERE t.datetime BETWEEN ? AND ?
                AND t.store_id=?
                AND d.salesCode=?
                AND t.trans_status='Z'
                AND total < 10000
        ");
        $query = "
            SELECT SUM(total) AS rawTTL,
                SUM(CASE WHEN t.discounttype <> 0 THEN total ELSE 0 END) as saleTTL,
                SUM(CASE WHEN trans_type='D' THEN total ELSE 0 END) AS openTTL,
                SUM(CASE WHEN charflag='SO' THEN total ELSE 0 END) AS spoTTL,
                SUM(CASE WHEN p.default_vendor_id=1 OR v.vendorID=1 THEN total ELSE 0 END) AS unfiTTL,
                d.salesCode,
                t.store_id
            FROM {$dlog} AS t
                INNER JOIN departments AS d ON t.department=d.dept_no
                LEFT JOIN products AS p ON t.upc=p.upc AND p.store_id=1
                LEFT JOIN vendorItems AS v ON t.upc=v.upc AND v.upc=1
            WHERE t.tdate BETWEEN ? AND ?
                AND trans_type IN ('I','D')
            GROUP BY d.salesCode,
                t.store_id
            HAVING SUM(total) <> 0";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($date1 . ' 00:00:00', $date2 . ' 23:59:59'));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $name = isset($this->COA[$row['salesCode']]) ? $this->COA[$row['salesCode']] : $row['salesCode'];
            $poArgs = array($date1 . ' 00:00:00', $date2 . ' 23:59:59', $row['store_id'], $row['salesCode']);
            $poTTL = $this->connection->getValue($poP, $poArgs);
            $shrinkTTL = $this->connection->getValue($shrinkP, $poArgs);
            $data[] = array(
                $name . '-' . $row['store_id'],
                sprintf('%.2f', $row['rawTTL']),
                sprintf('%.3f%%', ($row['saleTTL']/$row['rawTTL'])*100),
                sprintf('%.3f%%', ($row['openTTL']/$row['rawTTL'])*100),
                sprintf('%.3f%%', ($row['spoTTL']/$row['rawTTL'])*100),
                sprintf('%.3f%%', ($shrinkTTL/$row['rawTTL'])*100),
                sprintf('%.2f', $row['unfiTTL']),
                sprintf('%.3f%%', ($row['unfiTTL']/$row['rawTTL'])*100),
                sprintf('%.2f', $poTTL),
                sprintf('%.3f%%', (($row['unfiTTL']-$poTTL)/$row['unfiTTL'])*100),
            );
        }

        return $data;
    }

    function calculate_footers($data)
    {
        return array();
    }

    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        return <<<HTML
<form method="get">
    {$dates}
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Get Report</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

