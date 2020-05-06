<?php

use COREPOS\Fannie\API\data\DataConvert;

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('DeliInventoryCatModel')) {
    include(__DIR__ . '/models/DeliInventoryCatModel.php');
}

class DIExport extends FannieRESTfulPage
{
    protected $header = 'Deli Inventory Export';
    protected $title = 'Deli Inventory Export';

    protected function post_handler()
    {
        $model = new DeliInventoryCatModel($this->connection);
        $model->storeID(FormLib::get('store'));
        $format = FormLib::get('format');
        $data = array();
        if ($format == 'Excel') {
            $data[] = array(
                'Category',
                'Item',
                'Case Size',
                'Unit Size',
                'Case Cost',
                'SKU',
                'UPC',
                'Vendor',
            );
        }
        foreach ($model->find() as $obj) {
            $data[] = $this->getLine($obj, $format);
        }

        if ($format == 'Excel') {
            $xls = DataConvert::arrayToExcel($data);
            $filename = 'inventory-export.' . DataConvert::excelFileExtension();
            header('Content-Type: application/ms-excel');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            echo $xls;
        } else {
            $csv = DataConvert::arrayToCsv($data);
            $filename = 'inventory-export.csv';
            header('Content-Type: application/ms-excel');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            echo $csv;
        }

        return false;
    }

    private $vendorCache = array();

    private function getLine($obj, $format)
    {
        if ($format == 'Excel') {
            if (!isset($this->vendorCache[$obj->vendorID()])) {
                $nameP = $this->connection->prepare("SELECT vendorName FROM vendors WHERE vendorID=?");
                $this->vendorCache[$obj->vendorID()] = $this->connection->getValue($nameP, array($obj->vendorID()));
            }
            return array(
                $obj->category(),
                $obj->item(),
                $obj->units(),
                $obj->size(),
                $obj->price(),
                $obj->orderno(),
                $obj->upc(),
                $this->vendorCache[$obj->vendorID()]
            );
        } else {
            $unit = $obj->size();
            $qty = $obj->units();
            if (preg_match('/([0-9\.]+)\s*(.+)/', $obj->size(), $matches)) {
                $unit = $matches[2];
                $qty = $obj->units() * $matches[1];
            }
            return array(
                $obj->orderno(),
                $obj->item(),
                999999,
                date('Ymd'),
                $unit,
                $qty,
                $obj->price(),
                $obj->item(),
            );
        }
    }

    protected function get_view()
    {
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="post" action="DIExport.php">
    <div class="form-group">
        <label>Store</label>
        {$stores['html']}
    </div>
    <div class="form-group">
        <label>Format</label>
        <select name="format" class="form-control">
            <option>Excel</option>
            <option>ChefTec</option>
        </select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Export</button>
    </div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

