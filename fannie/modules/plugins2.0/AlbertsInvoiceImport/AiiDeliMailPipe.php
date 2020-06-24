<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('\\FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('\COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/data/pipes/AttachmentEmailPipe.php');
}

class AiiDeliMailPipe extends \COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe
{
    public function processMail($msg)
    {
        $info = $this->parseEmail($msg);
        $boundary = $this->hasAttachments($info['headers']);
        $dbc = \FannieDB::get(\FannieConfig::config('OP_DB'));
        $storeID = 1;
        if (stristr($info['body'], 'DENFELD')) {
            $storeID = 2;
        }

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
                if (substr($temp, -3) != 'csv') {
                    unlink($temp);
                    continue;
                }
                $orderID = false;
                $fp = fopen($temp, 'r');
                while (!feof($fp)) {
                    $data = fgetcsv($fp);
                    if (!is_numeric($data[0])) {
                        continue;
                    }
                    $date = date('Y-m-d', strtotime($data[23]));
                    if (!$orderID) {
                        $order = new PurchaseOrderModel($dbc);
                        $order->vendorID(28);
                        $order->storeID($storeID);
                        $order->creationDate($date);
                        $order->placed(1);
                        $order->placedDate($date);
                        $order->vendorInvoiceID($data[0]);
                        $orderID = $order->save();
                    }
                    $isBulk = false;
                    if (strstr($data[11], 'Bulk') && strstr($data[12], 'Meat')) {
                        $isBulk = true;
                    }
                    $poi = new PurchaseOrderItemsModel($dbc);
                    $poi->orderID($orderID);
                    $poi->sku($data[2]);
                    $poi->quantity($isBulk ? 1 : $data[20]);
                    $caseSize = (($data[5] ? $data[5] : 1) * $data[6]);
                    if ($isBulk) {
                        $caseSize = $data[20];
                    }
                    $poi->unitCost(($data[20] * $data[21]) / $caseSize);
                    $poi->caseSize($caseSize);
                    $poi->receivedDate($date);
                    $poi->receivedQty($isBulk ? $caseSize : $caseSize * $data[20]);
                    $poi->receivedTotalCost($data[20] * $data[21]);
                    $poi->unitSize($data[17]);
                    $poi->brand($data[4]);
                    $poi->description($data[10]);
                    $upc = $data[19];
                    if (strstr($upc, '-')) {
                        $upc = str_replace('-', '', $upc);
                        $upc = substr($upc, 0, strlen($upc) - 1);
                    }
                    $poi->internalUPC(BarcodeLib::padUPC($upc));
                    $poi->salesCode(51201);
                    $poi->save();
                }
                $dest = __DIR__ . '/../../../purchasing/noauto/invoices/' . $orderID . '.csv';
                rename($temp, $dest);
                chmod($dest, 0644);
                if (file_exists($temp)) {
                    unlink($temp);
                }
            }
        }
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    $obj = new AiiDeliMailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 

