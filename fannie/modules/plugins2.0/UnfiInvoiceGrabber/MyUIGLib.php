<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
use COREPOS\Fannie\API\data\FileData;

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

class MyUIGLib 
{
    /**
      Create purchase orders from zipfile
      @param $zipfile filename
      @param $vendorID integer vendor ID
      @param $repeat this date has been previously imported
    */
    static public function import($filename, $vendorID, $repeat=false)
    {
        echo $filename . "\n";
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $create = $dbc->prepare('INSERT INTO PurchaseOrder (vendorID, creationDate, placed,
                            placedDate, userID, vendorOrderID, vendorInvoiceID, storeID) VALUES
                            (?, ?, 1, ?, 0, ?, ?, ?)');
        $find = $dbc->prepare('SELECT orderID FROM PurchaseOrder WHERE vendorID=? AND storeID=? AND vendorInvoiceID=?');
        $plu = $dbc->prepare('SELECT upc FROM VendorAliases WHERE isPrimary=1 AND vendorID=? AND sku LIKE ?');
        $clear = $dbc->prepare('DELETE FROM PurchaseOrderItems WHERE orderID=?');
        $codeP = $dbc->prepare('SELECT sku, salesCode FROM PurchaseOrderItems WHERE orderID=?');
        $storeID = FannieConfig::config('STORE_ID');

        $header_info = array();
        $item_info = array();
        try {
            $data = FileData::fileToArray($filename);
        } catch (Exception $ex) {
            echo "Cannot open file!\n";
            unlink($filename); // typically JSON error message when download failed
            return;
        }
        $header_info = self::parseHeader($data);
        foreach ($data as $line) {
            $testVal = (string)$line[10];
            if (isset($testVal) && is_numeric($testVal)) {
                $item = self::parseItem($line, $vendorID);
                $item_info[] = $item;
            }
        }

        echo count($item_info) . "\n";


        if (count($item_info) > 0) {
            $id = false;
            // check whether order already exists
            $idR = $dbc->execute($find, array($vendorID, $storeID, $header_info['vendorInvoiceID']));
            $new = false;
            $codeMap = array();
            if ($dbc->num_rows($idR) > 0) {
                $idW = $dbc->fetch_row($idR);
                $id = $idW['orderID'];
                $codeR = $dbc->execute($codeP, array($id));
                while ($codeW = $dbc->fetchRow($codeR)) {
                    $codeMap[$codeW['sku']] = $codeW['salesCode'];
                }
                $dbc->execute($clear, array($id));
            }
            if (!$id) {
                // date has not been downloaded before OR
                // date previously did not include this invoice
                $dbc->execute($create, array($vendorID, $header_info['placedDate'], $header_info['placedDate'],
                                $header_info['vendorOrderID'], $header_info['vendorInvoiceID'], $storeID));
                $id = $dbc->insertID();
            }

            $fakeSku = 1;
            foreach($item_info as $item) {
                if ($item['sku'] == 0) {
                    $item['sku'] = $fakeSku;
                    $fakeSku++;
                }
                $model = new PurchaseOrderItemsModel($dbc);
                $model->orderID($id);
                $model->sku($item['sku']);
                if ($model->load()) {
                    // sometimes an invoice contains multiple
                    // lines with the same product SKU
                    // sum those so the single record in
                    // PurchaseOrderItems is correct
                    $item['quantity'] += $model->quantity();
                    $item['receivedQty'] += $model->receivedQty();
                    $item['receivedTotalCost'] += $model->receivedTotalCost();
                }
                $model->quantity($item['quantity']);
                $model->receivedQty($item['receivedQty']);
                $model->receivedTotalCost($item['receivedTotalCost']);

                $model->unitCost($item['unitCost']);
                $model->caseSize($item['caseSize']);
                $model->receivedDate($header_info['receivedDate']);
                $model->unitSize($item['unitSize']);
                $model->brand($item['brand']);
                $model->description($item['description']);
                $model->internalUPC($item['upc']);

                $pluCheck = $dbc->execute($plu, array($vendorID, $item['sku']));
                if ($dbc->num_rows($pluCheck) > 0) {
                    $pluInfo = $dbc->fetch_row($pluCheck);
                    $model->internalUPC($pluInfo['upc']);
                }
                if (!$new && isset($codeMap[$item['sku']])) {
                    $model->salesCode($codeMap[$item['sku']]);
                } elseif ($model->salesCode() == '') {
                    $code = $model->guessCode();
                    $model->salesCode($code);
                }

                switch ($item['sku']) { // anomoly handler
                    case '0473850';
                        $model->unitSize('#');
                        break;
                }

                $model->save();
            }

            rename($filename, __DIR__ . '/noauto/originals/' . $id . '.xlsx');
        } else {
            unlink($filename);
        }

        return true;
    }

    static private function parseHeader($data)
    {

        return array(
                'placedDate' => date('Y-m-d', strtotime($data[13][8])),
                'receivedDate' => date('Y-m-d', strtotime($data[3][7])),
                'vendorOrderID' => (string)$data[7][8],
                'vendorInvoiceID' => (string)$data[3][8],
        );
    }

    static private $lookups = null;
    static private function getCaseSize($dbc, $upc, $sku, $vendorID)
    {
        if (self::$lookups === null) { 
            $vend = $dbc->prepare('
                SELECT units 
                FROM vendorItems 
                WHERE vendorID=? 
                    AND (upc=? OR sku=?)
                    AND units IS NOT NULL
                    AND units > 0');
            $order = $dbc->prepare('
                SELECT caseSize
                FROM PurchaseOrderItems AS i
                    INNER JOIN PurchaseOrder AS o ON i.orderID=o.orderID
                WHERE o.vendorID=?
                    AND (i.internalUPC=? OR i.sku=?)
                    AND i.caseSize <> 1
                ORDER BY i.receivedDate DESC');
            self::$lookups = array($vend, $order);
        }

        foreach (self::$lookups as $lookup) {
            $size = $dbc->getValue($lookup, array($vendorID, $upc, $sku));
            if ($size !== false) {
                return $size;
            }
        }

        return 1;
    }

    static private function parseItem($line, $vendorID)
    {
        global $FANNIE_OP_DB;

        $UPC = 8;
        $SKU = 3;
        $RECVQTY = 2;
        $CASESIZE = 5;
        $BRAND = 6;
        $DESCRIPTION = 7;
        $TOTALCOST = 15;
        $UNITCOST = 14;
        $ORDERQTY = 1;

        // remove non-digits and check digits
        // then pad to length
        $upc = str_replace('-', '', (string)$line[$UPC]);
        $upc = str_replace(' ', '', $upc);
        $upc = substr($upc, 0, strlen($upc)-1);
        $upc = BarcodeLib::padUPC($upc);
        $line[$SKU] = str_pad((string)$line[$SKU], 7, '0', STR_PAD_LEFT);

        $caseSize = (string)$line[$CASESIZE];
        $unitSize = '';
        if (strstr($caseSize, '/')) {
            list($caseSize, $unitSize) = explode('/', $caseSize, 2);
        } elseif (strstr($caseSize, '#')) {
            $caseSize = trim(str_replace('#', '', $caseSize));
            $unitSize = '#';
        } elseif (strstr($caseSize, 'LB')) {
            $caseSize = trim(str_replace('LB', '', $caseSize));
            $unitSize = '#';
        }
        // invoice does not include proper case size
        // try to find actual size in vendorItems table
        // via SKU or UPC
        if (strtoupper($caseSize) == 'CS' || !is_numeric($caseSize)) {
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $caseSize = self::getCaseSize($dbc, $upc, $line[$SKU], $vendorID);
        } 

        return array(
            'sku' => $line[$SKU],
            'quantity' => (string)$line[$ORDERQTY],
            'unitCost' => (string)$line[$UNITCOST],
            'caseSize' => $caseSize,
            'receivedQty' => ((string)$line[$RECVQTY]) * ((string)$caseSize),
            'receivedTotalCost' => (string)$line[$TOTALCOST],
            'unitSize' => $unitSize,
            'brand' => (string)$line[$BRAND],
            'description' => (string)$line[$DESCRIPTION],
            'upc' => $upc,
        );
    }
}

