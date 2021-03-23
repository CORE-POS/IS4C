<?php

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class CpwProduceUploadPage extends \COREPOS\Fannie\API\FannieUploadPage {

    public $title = "Fannie - CPW Produce Prices";
    public $header = "Upload CPW Produce price file";
    public $themed = true;

    public $description = '[CPW Produce Catalog Import] specialized vendor import tool. Column choices
    default to CPW price file layout.';

    protected $preview_opts = array(
        'upc' => array(
            'display_name' => 'UPC *',
            'default' => 7,
            'required' => true
        ),
        'desc' => array(
            'display_name' => 'Description *',
            'default' => 4,
            'required' => true
        ),
        'sku' => array(
            'display_name' => 'SKU *',
            'default' => 8,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Case Cost (Reg) *',
            'default' => 6,
            'required' => true
        ),
        'size' => array(
            'display_name' => 'Size Info',
            'default' => 11,
            'required' => true
        ),
    );

    private function validateLine($data)
    {
        $ret = array('valid'=>false);
        $ret['sku'] = trim($data[8]);
        $ret['upc'] = trim($data[7]);
        if (empty($ret['upc']) && empty($ret['sku'])) {
            return $ret;
        }
        $ret['upc'] = str_replace('-', '', $ret['upc']);
        $ret['upc'] = substr($ret['upc'], 0, strlen($ret['upc'])-1);
        if (!is_numeric($ret['sku']) && !is_numeric($ret['sku'])) {
            return $ret;
        }
        $ret['upc'] = BarcodeLib::padUPC($ret['upc']);

        $ret['price'] = trim($data[6]);
        if (!is_numeric($ret['price'])) {
            return $ret;
        }

        $ret['valid'] = true;
        return $ret;
    }

    private function sizeItem($item, $data)
    {
        $item['size'] = trim($data[11]);
        if (is_numeric($item['size'])) {
            $item['size'] .= ' ' . trim($data[12]);
        }

        $item['caseSize'] = trim($data[13]);
        if ($item['caseSize'] == 1 && strpos($data[11], '-')) {
            list($start,$end) = explode('-', trim($data[11]));
            $item['caseSize'] = ($start + $end) / 2;
            $item['size'] = trim($data[12]);
        } elseif ($item['caseSize'] == 1 && is_numeric(trim($data[11]))) {
            $item['caseSize'] = trim($data[11]);
            $item['size'] = trim($data[12]);
        }

        return $item;
    }

    private function priceItem($item, $data)
    {
        $item['reg'] = trim($data[15]);
        $item['sale'] = 0;
        if ($item['price'] != $item['reg']) {
            $item['sale'] = $item['price'];
        }
        $item['reg'] /= $item['caseSize'];
        $item['sale'] /= $item['caseSize'];

        return $item;
    }

    protected function getVendorID()
    {
        $idP = $this->connection->prepare("SELECT vendorID FROM vendors WHERE vendorName=? ORDER BY vendorID");
        $vid = $this->connection->getValue($idP, array('CPW (Produce)'));

        return $vid !== false ? $vid : $this->presetID;
    }

    function process_file($linedata, $indexes)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $vendorID = $this->getVendorID();
        if ($vendorID === false) {
            $this->error_details = 'Cannot find vendor';
            return false;
        }

        $resetP = $dbc->prepare('UPDATE vendorItems SET vendorDept=0 WHERE vendorID=? AND (vendorDept < 5 OR vendorDept=999999)');
        $dbc->execute($resetP, array($vendorID));

        $vendP = $dbc->prepare('SELECT sku FROM vendorItems WHERE sku=? AND vendorID=?');
        $insP = $dbc->prepare('INSERT INTO vendorItems 
            (upc, sku, brand, description, size, units, cost, saleCost, vendorDept, vendorID, modified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ' . $dbc->now() . ')');
        $upP = $dbc->prepare('UPDATE vendorItems SET cost=?, saleCost=?, vendorDept=?, units=?, size=?, modified=' . $dbc->now() . '
            WHERE sku=? AND vendorID=?');

        $deptP = $dbc->prepare('SELECT deptID FROM vendorDepartments WHERE deptID=? AND vendorID=?');
        $newDeptP = $dbc->prepare('INSERT INTO vendorDepartments (vendorID, deptID, name, margin, testing, posDeptID)
            VALUES (?, ?, ?, 0, 0, 0)');

        $seenSKUs = array();
        $dbc->startTransaction();
        foreach ($linedata as $data) {
            $item = $this->validateLine($data);
            if (!$item['valid']) {
                continue;
            }
            if (isset($seenSKUs[$item['sku']])) {
                continue;
            }

            $item = $this->sizeItem($item, $data);
            $item = $this->priceItem($item, $data);

            $dept = trim($data[16]);
            if ($dept != 2 && $dept != 3 && $dept != 4) {
                continue;
            }
            $brand = trim($data[1]);
            if (!empty(trim($data[2]))) {
                $brand = trim($data[2]);
            }
            $brand = preg_replace('/\(.+\)/', '', $brand);
            $description = trim($data[4]);

            $exists = $dbc->getValue($vendP, array($item['sku'], $vendorID));
            if ($exists) {
                $dbc->execute($upP, array($item['reg'], $item['sale'], $dept, $item['caseSize'], $item['size'], $item['sku'], $vendorID));
            } else {
                $dbc->execute($insP, array($item['upc'], $item['sku'], $brand, $description, $item['size'], $item['caseSize'], $item['reg'], $item['sale'], $dept, $vendorID));
            }
            $seenSKUs[$item['sku']] = true;

            $exists = $dbc->getValue($deptP, array($dept, $vendorID));
            if (!$exists) {
                $dbc->execute($newDeptP, array($vendorID, $dept, trim($data[0])));
            }
        }
        $dbc->commitTransaction();
        $clearP = $dbc->prepare('DELETE FROM vendorItems WHERE vendorID=? AND modified < ?');
        $dbc->execute($clearP, array($vendorID, date('Y-m-d')));

        return true;
    }
}

FannieDispatch::conditionalExec();

