<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('\COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}

class USFoodsMailPipe extends \COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe
{
    public function processMail($msg)
    {
        $info = $this->parseEmail($msg);
        $boundary = $this->hasAttachments($info['headers']);
        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $storeID = 2;
        $vendorID = 35;

        if ($boundary) {
            $pieces = $this->extractAttachments($info['body'], $boundary);
            foreach ($pieces['attachments'] as $a) {
                $temp = tempnam(sys_get_temp_dir(), 'alb');
                if (strstr($a['name'], '";')) {
                    list($a['name'],) = explode('";', $a['name'], 2);
                }
                $orig = explode('.', $a['name'], 2);
                if (count($orig) > 1) { // preserve file extension
                    $temp .= '.' . $orig[count($orig)-1];
                }
                file_put_contents($temp, $a['content']);
                if (substr(strtolower($temp), -3) != 'csv') {
                    unlink($temp);
                    continue;
                }

                $orderID = false;
                $fp = fopen($temp, 'r');
                while (!feof($fp)) {
                    $data = fgetcsv($fp);
                    if (!is_numeric($data[3])) {
                        continue;
                    }
                    $storeID = $data[3] == 53809885 ? 2 : 1;
                    $invDate = date('Y-m-d', strtotime($data[9]));
                    $invID = $data[10];
                    $total = $data[12];

                    $order = new PurchaseOrderModel($dbc);
                    $order->vendorID($vendorID);
                    $order->storeID($storeID);
                    $order->creationDate($invDate);
                    $order->placed(1);
                    $order->placedDate($invDate);
                    $order->vendorInvoiceID($invID);
                    $order->inventoryIgnore(1);
                    $orderID = $order->save();

                    $poi = new PurchaseOrderItemsModel($dbc);
                    $poi->orderID($orderID);
                    $poi->sku('0000000');
                    $poi->quantity(1);
                    $poi->unitCost($total);
                    $poi->caseSize(1);
                    $poi->receivedDate($invDate);
                    $poi->receivedQty(1);
                    $poi->receivedTotalCost($total);
                    $poi->unitSize('');
                    $poi->brand('GENERIC');
                    $poi->description('ITEM');
                    $poi->internalUPC('0000000000000');
                    $poi->salesCode(51201);
                    $poi->save();
                }

                if (file_exists($temp)) {
                    unlink($temp);
                }
            }
        }
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new USFoodsMailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 

