<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class UIGLib 
{
    /**
      Create purchase orders from zipfile
      @param $zipfile filename
      @param $repeat this date has been previously imported
    */
    static public function import($zipfile, $repeat=false)
    {
        global $FANNIE_OP_DB;
        $za = new ZipArchive();
        $try = $za->open($zipfile);
        if ($try !== true) {
            // invalid file
            return $try;
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $create = $dbc->prepare('INSERT INTO PurchaseOrder (vendorID, creationDate, placed,
                            placedDate, userID, vendorOrderID, vendorInvoiceID) VALUES
                            (?, ?, 1, ?, 0, ?, ?)');
        $find = $dbc->prepare('SELECT orderID FROM PurchaseOrder WHERE vendorID=? AND userID=0
                            AND vendorInvoiceID=?');
        $plu = $dbc->prepare('SELECT upc FROM vendorSKUtoPLU WHERE vendorID=? AND sku LIKE ?');
        $clear = $dbc->prepare('DELETE FROM PurchaseOrderItems WHERE orderID=?');

        for ($i=0; $i<$za->numFiles; $i++) {
            $info = $za->statIndex($i);
            if (substr(strtolower($info['name']), -4) != '.csv') {
                // skip non-csv file
                continue;
            }

            echo "Processing invoice {$info['name']}\n";
            $fp = $za->getStream($info['name']);
            $header_info = array();
            $item_info = array();
            while(!feof($fp)) {
                $line = fgetcsv($fp);
                if (strtolower($line[0]) == 'header') {
                    $header_info = self::parseHeader($line);
                } else if (strtolower($line[0]) == 'detail') {
                    $item = self::parseItem($line);
                    $item_info[] = $item;
                }
            }

            if (count($item_info) > 0) {
                $id = false;
                if ($repeat) {
                    // date has been downloaded before; lookup orderID
                    $idR = $dbc->execute($find, array(1, $header_info['vendorInvoiceID']));
                    if ($dbc->num_rows($idR) > 0) {
                        $idW = $dbc->fetch_row($idR);
                        $id = $idW['orderID'];
                        $dbc->execute($clear, array($id));
                    }
                }
                if (!$id) {
                    // date has not been downloaded before OR
                    // date previously did not include this invoice
                    $dbc->execute($create, array(1, $header_info['placedDate'], $header_info['placedDate'],
                                    $header_info['vendorOrderID'], $header_info['vendorInvoiceID']));
                    $id = $dbc->insert_id();
                }

                foreach($item_info as $item) {
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
                    $model->receivedDate($header_info['placedDate']);
                    $model->unitSize($item['unitSize']);
                    $model->brand($item['brand']);
                    $model->description($item['description']);
                    $model->internalUPC($item['upc']);

                    $pluCheck = $dbc->execute($plu, array(1, $item['sku']));
                    if ($dbc->num_rows($pluCheck) > 0) {
                        $pluInfo = $dbc->fetch_row($pluCheck);
                        $model->internalUPC($pluInfo['upc']);
                    }

                    $model->save();
                }
            }
        }

        return true;
    }

    static private function parseHeader($line)
    {
        $INVOICE = 1;
        $ORDER_DATE = 4;
        $PO_NUMBER = 24;

        return array(
                'placedDate' => date('Y-m-d', strtotime($line[$ORDER_DATE])),
                'vendorOrderID' => $line[$PO_NUMBER],
                'vendorInvoiceID' => $line[$INVOICE],
        );
    }

    static private function parseItem($line)
    {
        global $FANNIE_OP_DB;

        $UPC = 3;
        $SKU = 4;
        $RECVQTY = 5;
        $CASESIZE = 6;
        $UNITSIZE = 7;
        $BRAND = 8;
        $DESCRIPTION = 9;
        $TOTALCOST = 13;
        $UNITCOST = 15;
        $ORDERQTY = 18;

        // remove non-digits and check digits
        // then pad to length
        $upc = str_replace('-', '', $line[$UPC]);
        $upc = str_replace(' ', '', $upc);
        $upc = substr($upc, 0, strlen($upc)-1);
        $upc = BarcodeLib::padUPC($upc);

        $caseSize = $line[$CASESIZE];
        // invoice does not include proper case size
        // try to find actual size in vendorItems table
        // via SKU or UPC
        if (strtoupper($caseSize) == 'CS') {
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $vmodel = new VendorItemsModel($dbc);
            $vmodel->sku($line[$SKU]);
            $vmodel->vendorID(1);
            $vmodel->load();
            if ($vmodel->units() != '') {
                $caseSize = $vmodel->units();
            } else {
                $vmodel->reset();
                $vmodel->upc($upc);
                $vmodel->vendorID(1);
                foreach($vmodel->find() as $item) {
                    if ($item->units() != '') {
                        $caseSize = $item->units();
                    }
                }
            }

            if (!is_numeric($caseSize)) {
                $caseSize = 1;
            }
        }

        return array(
            'sku' => $line[$SKU],
            'quantity' => $line[$ORDERQTY],
            'unitCost' => $line[$UNITCOST],
            'caseSize' => $caseSize,
            'receivedQty' => $line[$RECVQTY],
            'receivedTotalCost' => $line[$TOTALCOST],
            'unitSize' => $line[$UNITSIZE],
            'brand' => $line[$BRAND],
            'description' => $line[$DESCRIPTION],
            'upc' => $upc,
        );
    }
}

