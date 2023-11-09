<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class LbmxReformat2 extends COREPOS\Fannie\API\FannieUploadPage
{
    protected $header = 'LBMX Data Reformat (Input: CSV)';
    protected $title = 'LBMX Data Reformat';

    protected $preview_opts = array(
        'vendorID' => array(
            'display_name' => 'Vendor ID',
            'default' => 60,
            'required' => true
        ),
        'invoice' => array(
            'display_name' => 'Invoice #',
            'default' => 12,
            'required' => true
        ),
        'date' => array(
            'display_name' => 'Invoice Date',
            'default' => 11,
            'required' => true
        ),
        'customerID' => array(
            'display_name' => 'Customer ID',
            'default' => 42,
            'required' => true
        ),
        'due' => array(
            'display_name' => 'Due Date',
            'default' => 73,
            'required' => true
        ),
        'po' => array(
            'display_name' => 'PO #',
            'default' => 26,
            'required' => true
        ),
        'coding' => array(
            'display_name' => 'GL Code',
            'default' => 156,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'GL Cost',
            'default' => 157,
            'required' => true
        ),
        'total' => array(
            'display_name' => 'Invoice Cost',
            'default' => 152,
            'required' => true
        ),
        'type' => array(
            'display_name' => 'Invoice Type',
            'default' => 13,
            'required' => true
        ),
    );

    private $results = '';

    public function process_file($linedata, $indexes)
    {
        $getStoreP = $this->connection->prepare("SELECT posID FROM LbmxStores WHERE lbmxID=?");
        $getVendorP = $this->connection->prepare("SELECT vendorName, outputName, paymentMethod FROM LbmxVendors AS l
            LEFT JOIN vendors AS v on l.posID=v.vendorID WHERE lbmxID=?");

        /*
         * Find existing POs and/or generate new POs
         * as needed. Re-importing the same PO should
         * just update its data
         */
        $header = true;
        $fp = fopen(__DIR__ . '/noauto/' . $this->original_file_name . '.csv', 'w');
        $invoices = array();
        foreach ($linedata as $line) {
            if ($header) {
                $header = false;
                continue;
            }
            if (!isset($line[$indexes['vendorID']])) {
                continue;
            } 
            if (!isset($line[$indexes['customerID']])) {
                continue;
            } 
            if (!isset($line[$indexes['invoice']])) {
                continue;
            } 

            $storeID = $this->connection->getValue($getStoreP, array($line[$indexes['customerID']]));
            $vendorRow = $this->connection->getRow($getVendorP, array($line[$indexes['vendorID']]));
            $vendorID = $vendorRow['outputName'] ? $vendorRow['outputName'] : $vendorRow['vendorName'];
            $paymentMethod = $vendorRow['paymentMethod'];
            $invDate = date('m/d/Y', strtotime($line[$indexes['date']]));
            $dueDate = date('m/d/Y', strtotime($line[$indexes['due']]));
            $coding = str_replace('-', '', $line[$indexes['coding']]);
            $cost = $line[$indexes['cost']];
            $total = $line[$indexes['total']];
            $type = $line[$indexes['type']];
            if ($type == 'CN') {
                $cost *= -1;
                $total *= -1;
            }
            $invoice = $line[$indexes['invoice']];
            $po = $line[$indexes['po']];

            fprintf($fp, $paymentMethod .','
                . $vendorID . ','
                . $invoice . ','
                . $invDate . ','
                . $dueDate . ','
                . $total . ','
                . $cost . ','
                . $coding . ','
                . $po . "\r\n");

        }
        fclose($fp);

        return true;
    }

    public function results_content()
    {
        return <<<HTML
<p>
Conversion complete.
</p>
<p>
<a href="noauto/{$this->original_file_name}.csv">{$this->original_file_name}.csv</a><br />
</p>
<p>
<a href="LbmxReformat2.php">Process another file</a>
</p>
HTML;
    }

}

FannieDispatch::conditionalExec();

