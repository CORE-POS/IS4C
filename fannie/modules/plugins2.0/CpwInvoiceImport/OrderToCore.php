<?php

namespace COREPOS\Fannie\Plugin\CpwInvoiceImport;

class OrderToCore
{
    private $dbc = null;
    public function __construct($dbc)
    {
        $this->dbc = $dbc; 
    }

    public function import($order)
    {
        $porder = new \PurchaseOrderModel($this->dbc);
        $storeID = $this->addressToStoreID($order['shipTo']);
        $vendorID = $this->getVendorID();
        if (($orderID=$this->orderExists($order['invoiceNo'])) !== false) {
            $porder->orderID($orderID); 
            $porder->creationDate($order['orderDate']);
            $porder->placedDate($order['orderDate']);
            $porder->placed(1);
            $porder->storeID($storeID);
            $porder->vendorID($vendorID);
            $porder->save();
        } else {
            $porder->creationDate($order['orderDate']);
            $porder->placedDate($order['orderDate']);
            $porder->placed(1);
            $porder->storeID($storeID);
            $porder->vendorID($vendorID);
            $porder->vendorInvoiceID($order['invoiceNo']);
            $orderID = $porder->save();
        }

        $items = new \PurchaseOrderItemsModel($this->dbc); 
        $items->orderID($orderID);
        foreach ($order['items'] as $i) {
            $items->description($i['description']);
            $items->quantity($i['orderedQty']);
            $items->caseSize(1);
            $items->unitCost($i['casePrice']);
            $items->sku($i['sku']);
            $items->receivedDate($order['shipDate']);
            $items->receivedQty($i['shippedQty']);
            $items->receivedTotalCost($i['total']);
            $upc = $this->getUPC($vendorID, $i['sku']);
            if ($upc) {
                $items->internalUPC($upc);
            } else {
                $items->internalUPC(str_repeat('0', 13));
            }
            $items->save();
        }
    }

    private function getUPC($vendorID, $sku)
    {
        $prep = $this->dbc->prepare('SELECT upc FROM vendorItems WHERE sku=? AND vendorID=?');
        return $this->dbc->getValue($prep, array($sku, $vendorID));
    }

    private function addressToStoreID($addr)
    {
        foreach ($addr as $line) {
            if (strstr($line, '610 ')) {
                return 1;
            } elseif (strstr($line, '4426 ')) {
                return 2;
            }
        }

        return 1;
    }

    private function orderExists($invoice)
    {
        $prep = $this->dbc->prepare("SELECT orderID FROM PurchaseOrder WHERE vendorInvoiceID=?");
        return $this->dbc->getValue($prep, array($invoice));
    }

    private function getVendorID()
    {
        $prep = $this->dbc->prepare("SELECT vendorID FROM vendors WHERE vendorName='CPW'");
        return $this->dbc->getValue($prep);
    }
}

