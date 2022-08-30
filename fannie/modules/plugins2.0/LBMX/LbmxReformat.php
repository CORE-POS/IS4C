<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class LbmxReformat extends COREPOS\Fannie\API\FannieUploadPage
{
    protected $header = 'LBMX Data Reformat (Input: TXT)';
    protected $title = 'LBMX Data Reformat';

    protected $preview_opts = array(
        'vendorID' => array(
            'display_name' => 'Vendor ID',
            'default' => 0,
            'required' => true
        ),
        'invoice' => array(
            'display_name' => 'Invoice #',
            'default' => 2,
            'required' => true
        ),
        'date' => array(
            'display_name' => 'Invoice Date',
            'default' => 5,
            'required' => true
        ),
        'customerID' => array(
            'display_name' => 'Customer ID',
            'default' => 9,
            'required' => true
        ),
        'due' => array(
            'display_name' => 'Due Date',
            'default' => 18,
            'required' => true
        ),
        'po' => array(
            'display_name' => 'PO #',
            'default' => 32,
            'required' => true
        ),
        'coding' => array(
            'display_name' => 'GL Code',
            'default' => 41,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Cost',
            'default' => 47,
            'required' => true
        ),
    );

    private $results = '';

    public function process_file($linedata, $indexes)
    {
        $getStoreP = $this->connection->prepare("SELECT posID FROM LbmxStores WHERE lbmxID=?");
        $getVendorP = $this->connection->prepare("SELECT vendorName FROM LbmxVendors AS l
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
            $vendorID = $this->connection->getValue($getVendorP, array($line[$indexes['vendorID']]));
            $invDate = date('m/d/Y', strtotime($line[$indexes['date']]));
            $dueDate = date('m/d/Y', strtotime($line[$indexes['due']]));
            $coding = str_replace('-', '', $line[$indexes['coding']]);
            $cost = $line[$indexes['cost']];
            $invoice = $line[$indexes['invoice']];
            $po = $line[$indexes['po']];

            fprintf($fp, '2,'
                . $vendorID . ','
                . $invoice . ','
                . $invDate . ','
                . $dueDate . ','
                . $cost . ','
                . $cost . ','
                . $coding . ','
                . $po . "\r\n");

            $key = $storeID . '-' . $vendorID . '-' . $invoice;
            if (!isset($invoices[$key])) {
                $invoices[$key] = array(
                    'date' => $invDate,
                    'due' => $dueDate,
                    'invoiceID' => $invoice,
                    'vendorID' => $vendorID,
                    'po' => $po,
                    'totalCost' => 0,
                    'codes' => array(),
                );
            }
            if (!isset($invoices[$key]['codes'][$coding])) {
                $invoices[$key]['codes'][$coding] = 0;
            }
            $invoices[$key]['codes'][$coding] += $cost;
            $invoices[$key]['totalCost'] += $cost;
        }
        fclose($fp);

        $fp = fopen(__DIR__ . '/noauto/' . $this->original_file_name . '_ROLLUP.csv', 'w');
        foreach ($invoices as $inv) {
            foreach($inv['codes'] as $coding => $cost) {
                fprintf($fp, '2,'
                    . $inv['vendorID'] . ','
                    . $inv['invoiceID'] . ','
                    . $inv['date'] . ','
                    . $inv['due'] . ','
                    . $inv['totalCost'] . ','
                    . $cost . ','
                    . $coding . ','
                    . $inv['po'] . "\r\n");
            }
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
<a href="noauto/{$this->original_file_name}_ROLLUP.csv">{$this->original_file_name}_ROLLUP.csv</a><br />
</p>
<p>
<a href="LbmxReformat.php">Process another file</a>
</p>
HTML;
    }

}

FannieDispatch::conditionalExec();

