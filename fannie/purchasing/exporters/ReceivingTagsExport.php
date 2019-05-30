<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

use COREPOS\Fannie\API\item\signage\Tags4x8P;

if (!class_exists('DefaultCsvPoExport')) {
    include(dirname(__FILE__) . '/DefaultCsvPoExport.php');
}

class ReceivingTagsExport extends DefaultCsvPoExport 
{
    public $nice_name = 'Receiving Tags';
    public $extension = 'pdf';
    public $mime_type = 'application/pdf';

    public function exportString($id)
    {
        return 'Unsupported';
    }

    public function export_order($id)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));

        $infoP = $dbc->prepare('
            SELECT o.sku,
                o.quantity,
                o.caseSize,
                o.brand,
                o.description,
                o.internalUPC,
                o.unitSize,
                n.vendorName,
                o.isSpecialOrder
            FROM PurchaseOrderItems AS o
                LEFT JOIN PurchaseOrder AS p ON o.orderID=p.orderID
                LEFT JOIN vendors AS n ON p.vendorID=n.vendorID
                WHERE o.orderID=?
            ORDER BY o.salesCode, o.brand, o.description');
        $infoR = $dbc->execute($infoP, array($id));
        $items = array();
        while ($infoW = $dbc->fetchRow($infoR)) {
            $item = array(
                'upc' => $infoW['internalUPC'],
                'description' => $infoW['description'],
                'posDescription' => $infoW['description'],
                'brand' => $infoW['brand'],
                'normal_price' => 0,
                'units' => $infoW['caseSize'],
                'size' => $infoW['unitSize'],
                'sku' => $infoW['sku'],
                'vendor' => $infoW['vendorName'],
                'scale' => 0,
                'numflag' => 0,
                'pricePerUnit' => '',
            );
            for ($i=0; $i<$infoW['quantity']; $i++) {
                $item['normal_price'] = ($i+1) . '/' . $infoW['quantity'];
                if ($infoW['isSpecialOrder']) {
                    $item['normal_price'] = 'SO';
                }
                $items[] = $item;
            }
        }

        $pdf = new Tags4x8P($items, 'provided');
        $pdf->drawPDF();
    }
}

