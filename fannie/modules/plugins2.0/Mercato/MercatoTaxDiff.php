<?php

use COREPOS\Fannie\API\FannieUploadPage;
use COREPOS\Fannie\API\item\ItemText;

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class MercatoTaxDiff extends FannieUploadPage
{
    protected $header = 'Mercato Tax Diff';
    protected $title = 'Mercato Tax Diff';

    protected $preview_opts = array(
        'sku' => array(
            'name' => 'sku',
            'display_name' => 'SKU',
            'default' => 0,
            'required' => true,
        ),
        'name' => array(
            'name' => 'name',
            'display_name' => 'Name',
            'default' => 1,
            'required' => true,
        ),
        'tax' => array(
            'name' => 'tax',
            'display_name' => 'Tax',
            'default' => 8,
            'required' => true,
        ),
    );

    private $results = array();

    public function process_file($linedata, $indexes)
    {
        $itemP = $this->connection->prepare("SELECT COALESCE(r.rate, 0) FROM products AS p
            LEFT JOIN taxrates AS r ON p.tax=r.id
            WHERE p.upc=?");
        foreach ($linedata as $data) {
            $sku = trim($data[$indexes['sku']]);
            if ($sku == '' || strtolower($sku) == 'sku') {
                continue;
            }
            $name = trim($data[$indexes['name']]);
            $tax = trim($data[$indexes['tax']]);
            $tax = trim($tax, '%');
            $rate = $this->connection->getValue($itemP, array(BarcodeLib::padUPC($sku)));
            if (abs(($rate * 100) - $tax) > 0.001) {
                $this->results[] = array(
                    'sku' => $sku,
                    'item' => $name,
                    'mrate' => sprintf('%.4f', $tax),
                    'prate' => sprintf('%.4f', $rate * 100),
                );
            }
        }

        return true;
    }

    public function results_content()
    {
        $ret = count($this->results) . ' discrepancies found';
        $ret .= '<table class="table table-bordered"><tr><th>SKU</th><th>Item</th><th>Mercato Tax</th><th>POS Tax</th></tr>';
        foreach ($this->results as $row) {
            $ret .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                $row['sku'], $row['item'], $row['mrate'], $row['prate']);
        }
        $ret .= '</table>';

        return $ret;
    }

}

FannieDispatch::conditionalExec();

