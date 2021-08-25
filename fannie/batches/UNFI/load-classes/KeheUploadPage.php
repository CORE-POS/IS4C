<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class KeheUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - KeHE Prices";
    public $header = "Upload KeHE price file";

    public $description = '[KeHE Catalog Import] specialized vendor import tool. Column choices
    default to KeHE price file layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 5,
            'required' => True
        ),
        'srp' => array(
            'display_name' => 'SRP *',
            'default' => 9,
            'required' => True
        ),
        'brand' => array(
            'display_name' => 'Brand *',
            'default' => 1,
            'required' => True
        ),
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 2,
            'required' => True
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 0,
            'required' => true
        ),
        'qty' => array(
            'display_name' => 'Case Qty *',
            'default' => 6,
            'required' => True
        ),
        'size' => array(
            'display_name' => 'Unit Size',
            'default' => 3,
        ),
        'cost' => array(
            'display_name' => 'Unit Cost (Reg) *',
            'default' => 8,
            'required' => True
        ),
    );

    protected $use_splits = true;
    protected $use_js = false;
    protected $vendor_name = 'KeHE';

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName=? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array($this->vendor_name));

        return $vid;
    }

    function process_file($linedata, $indexes)
    {
        $dbc = $this->connection;
        $VENDOR_ID = $this->getVendorID();
        if ($VENDOR_ID === false) {
            $this->error_details = 'Cannot find vendor';
            return false;
        }

        $itemP = $dbc->prepare("
            INSERT INTO vendorItems (
                brand, 
                sku,
                size,
                upc,
                units,
                cost,
                description,
                vendorDept,
                vendorID,
                saleCost,
                modified,
                srp
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )");
        $rounder = new \COREPOS\Fannie\API\item\PriceRounder();

        $dbc->startTransaction();

        foreach($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['upc']])) continue;

            // grab data from appropriate columns
            $sku = ($indexes['sku'] !== false) ? $data[$indexes['sku']] : '';
            $brand = $data[$indexes['brand']];
            $description = $data[$indexes['desc']];
            $qty = $data[$indexes['qty']];
            $size = ($indexes['size'] !== false) ? $data[$indexes['size']] : '';
            $size .= ($indexes['size'] !== false) ? $data[$indexes['size'] + 1] : '';
            $upc = $data[$indexes['upc']];
            $upc = substr($upc, 0, strlen($upc) - 1);
            $upc = BarcodeLib::padUPC($upc);
            // zeroes isn't a real item, skip it
            if ($upc == "0000000000000")
                continue;
            $reg_unit = trim($data[$indexes['cost']]);
            $srp = trim($data[$indexes['srp']]);
            // can't process items w/o price (usually promos/samples anyway)
            if (empty($reg_unit) or empty($srp))
                continue;

            // skip the item if prices aren't numeric
            // this will catch the 'label' line in the first CSV split
            // since the splits get returned in file system order,
            // we can't be certain *when* that chunk will come up
            if (!is_numeric($reg_unit) or !is_numeric($srp)) {
                continue;
            }

            $srp = $rounder->round($srp * $alias['multiplier']);

            $args = array(
                $brand, 
                $sku,
                $size === false ? '' : $size,
                $upc,
                $qty,
                $reg_unit,
                $description,
                0,
                $VENDOR_ID,
                0,
                date('Y-m-d H:i:s'),
                $srp,
            );
            $dbc->execute($itemP,$args);
        }

        $dbc->commitTransaction();

        return true;
    }

    /* clear tables before processing */
    function split_start()
    {
        $dbc = $this->connection;

        $VENDOR_ID = $this->getVendorID();
        if ($VENDOR_ID === false) {
            $this->error_details = 'Cannot find vendor';
            return false;
        }

        $viP = $dbc->prepare("DELETE FROM vendorItems WHERE vendorID=? AND upc NOT LIKE '000000%'");
        $vsP = $dbc->prepare("DELETE FROM vendorSRPs WHERE vendorID=? AND upc NOT LIKE '000000%'");
        $dbc->execute($viP,array($VENDOR_ID));
        $dbc->execute($vsP,array($VENDOR_ID));
    }

    function results_content()
    {
        $ret = "<p>Price data import complete</p>";
        $ret .= '<p><a href="'.filter_input(INPUT_SERVER, 'PHP_SELF').'">Upload Another</a></p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();


