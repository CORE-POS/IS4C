<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CTNabsInvoice extends FannieReportPage
{
    protected $header = 'Nabs Invoice';
    protected $title = 'Nabs Invoice';
    public $description = '[Nabs Invoice] lists purchases by a given nabs account in an invoice-like format';
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('Product Code', 'Inventory Item', 'Invoice Number', 'Date', 'Unit', 'Quantity', 'Cost', 'Description', 'Alt. Unit Indicator', 'Alternate Unit');

    public function fetch_report_data()
    {
        $store = FormLib::get('store');
        $card = FormLib::get('card_no');
        $dlog = DTransactionsModel::selectDlog($this->form->date1, $this->form->date2);

        $invoice = $card . $store;
        $invoice .= date('Ymd', strtotime($this->form->date1));
        $invoice .= date('Ymd', strtotime($this->form->date2));

        $prep = $this->connection->prepare("
            SELECT t.upc, p.brand, t.description, p.size, p.scale,
                SUM(CASE WHEN ABS(t.cost) < 1000 THEN t.cost ELSE t.total * d.margin END) AS ttl,
                " . DTrans::sumQuantity('t') . " AS qty
            FROM {$dlog} AS t
                " . DTrans::joinProducts('t', 'p', 'INNER') . "
                INNER JOIN departments AS d ON t.department=d.dept_no
            WHERE t.tdate BETWEEN ? AND ?
                AND t.card_no=?
                AND " . DTrans::isStoreID($store, 't') . "
            GROUP BY t.upc, p.brand, t.description,p.size,p.scale");
        $res = $this->connection->execute($prep, array($this->form->date1, $this->form->date2 . ' 23:59:59', $card, $store));
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            if ($row['qty'] == 0) {
                continue;
            }
            if ($row['scale']) {
                $row['size'] = '#';
            }
            list($units, $measure) = $this->getUnits($row['size']);
            $units *= $row['qty'];
            $data[] = array(
                $row['upc'],
                $row['brand'] . ' ' . $row['description'],
                $invoice,
                date('Ymd'),
                $measure,
                $units,
                sprintf('%.2f', $row['ttl']),
                $row['brand'] . ' ' . $row['description'],
                '',
                '',
            );
        }

        return $data;
    }

    public function render_data($data,$headers=array(),$footers=array(),$format='html')
    {
        if ($format == 'csv') {
            if (!headers_sent()) {
                header('Content-Type: application/ms-excel');
                header('Content-Disposition: attachment; filename="'.$this->header.'.csv"');
            }
            $ret = '';
            for ($i=0;$i<count($data);$i++) {
                $ret .= $this->csvLine($data[$i]);
            }

            return $ret;
        }

        return parent::render_data($data, $headers, $footers, $format);
    }

    private function getUnits($unitSize)
    {
        $units = 1.0;
        $unit_of_measure = $unitSize;
        if (strstr($unitSize, ' ')) {
            list($units, $unit_of_measure) = explode(' ', $unitSize, 2);
        }
        if ($unit_of_measure == '#') {
            $unit_of_measure = 'lb';
        } else if ($unit_of_measure == 'FZ') {
            $unit_of_measure = 'fl oz';
        }
        if (strstr($units, '/')) { // 6/12 oz on six pack of soda
            list($a, $b) = explode('/', $units, 2);
            $units = $a * $b;
        }
        if (strstr($unit_of_measure, '/')) { // space probably omitted
            if (preg_match('/([0-9.]+)\/([0-9.]+)(.+)/', $unit_of_measure, $matches)) {
                $units = $matches[1] * $matches[2];
                $unit_of_measure = $matches[3];
            }
        }

        return array($units, $unit_of_measure);
    }

    public function form_content()
    {
        $stores = FormLib::storePicker();
        ob_start();
?>
<form method = "get"> 
<div class="col-sm-4">
    <div class="form-group">
        <label><?php echo _('Account #'); ?></label>
        <input type=text name=card_no id=card_no  class="form-control" />
    </div>
    <div class="form-group">
        <label>Date Start</label>
        <input type=text id=date1 name=date1 class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>End Start</label>
        <input type=text id=date2 name=date2 class="form-control date-field" required />
    </div>
    <div class="form-group">
        <label>Store</label>
        <?php echo $stores['html']; ?>
    </div>
    <div class="form-group">
        <input type="checkbox" name="excel" id="excel" value="xls" />
        <label for="excel">Excel</label>
    </div>
    <p>
        <button type=submit class="btn btn-default btn-core">Submit</button>
        <button type=reset class="btn btn-default btn-reset">Start Over</button>
    </p>
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

