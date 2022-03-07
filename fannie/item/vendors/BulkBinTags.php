<?php
/*******************************************************************************

    Copyright 2014 Foods Co-op

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

use COREPOS\Fannie\API\lib\FannieUI;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

/*
 *  @class BulkBinTags
 *  Create Barcodes for NEW Bulk Bin PDF Labels
 */
class BulkBinTags extends FannieRESTfulPage 
{
    protected $title = "Fannie : UNFI Bulk Bin Barcodes";
    protected $header = "UNFI Bulk Bin Barcodes";

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');
    protected $id = 358;

    public $description = '[] .';

    public function preprocess()
    {
        $this->__routes[] = 'get<id><print>';
        $this->__routes[] = 'post<id><print>';

        return parent::preprocess();
    }

    public function get_id_print_handler()
    {
        //$store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        //$mtLength = $store == 1 ? 3 : 7;

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(10,10,10);
        $pdf->SetFont('Arial', '', 7);
        $dbc = $this->connection;
        $myskus = array();
        //$myskus[] = $row['sku'];

        //$mvmtP = $dbc->prepare("SELECT upc, auto_par FROM products WHERE upc = ? AND store_id = ?");

        $items = FormLib::get('items');
        $items = preg_split('/\r\n|[\r\n]/', $items);
        $myitems = array();
        //$mvmtT = array();
        foreach ($items as $k => $upc) {
            $upc = BarcodeLib::padUPC($upc);
            $items[$k] = $upc;
            $myitems[] = $upc;
            //$mvmtR = $dbc->execute($mvmtP, array($upc, $store));
            //$mvmtRow = $dbc->fetchRow($mvmtR);
            //$mvmtT[$upc] = round($mvmtRow['auto_par'] * $mtLength, 1);
        }

        $prep = $dbc->prepare("SELECT i.sku, p.description, i.size, i.units, v.vendorName, p.brand, p.upc
            FROM products AS p 
                LEFT JOIN vendors AS v ON p.default_vendor_id=v.vendorID
                LEFT JOIN vendorItems AS i ON p.default_vendor_id=i.vendorID AND p.upc=i.upc
            WHERE p.upc = ?
        ");

        $skus = array();
        foreach ($myitems as $upc) {
            $res = $dbc->execute($prep, $upc);
            $row = $dbc->fetchRow($res);
            $myskus[] = $row['sku'];
            $skus[$row['sku']] = array(
                $row['description'],
                $row['size'],
                $row['units'],
                $row['brand'],
                $row['vendorName'],
                $row['upc']
            );
        }

        $posX = 60;
        $posY = 105;

        foreach (array(0,1,2,3) as $k => $v) {

            $img = Image_Barcode2::draw($myskus[$k], 'code128', 'png', false, 15, 1, false);
            $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
            imagepng($img, $file);

            if ($v == 0) {
                //$pdf->SetXY($posX, $posY);
                $pdf->Image($file, $posX-3, $posY-2);
                //$pdf->Cell(0, 5, $myskus[$k]);
            } elseif ($v == 1) {
                //$pdf->SetXY($posX+100, $posY);
                $pdf->Image($file, $posX+94, $posY-2);
            } elseif ($v == 2) {
                //$pdf->SetXY($posX, $posY+100);
                $pdf->Image($file, $posX-3, $posY+100);
            } elseif ($v == 3) {
                //$pdf->SetXY($posX+100, $posY+100);
                $pdf->Image($file, $posX+94, $posY+100);
            }
            unlink($file);
        }

        /*
        foreach ($skus as $sku => $row) {

            if ($row[1] == '#') 
                $row[1] = 'LB';
            $pdf->SetXY($posX+3, $posY);
            $pdf->Cell(0, 5, substr($row[0], 0, 25));
            $pdf->Ln(3);
            $pdf->SetX($posX+3);

            $pdf->Cell(0, 5, $sku.'  '.$row[3], 0, 1);
            $img = Image_Barcode2::draw($sku, 'code128', 'png', false, 20, 1, false);
            $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
            imagepng($img, $file);
            $pdf->Image($file, $posX, $posY+7);
            unlink($file);

            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetXY($posX+35, $posY+15);
            $posX += 52;
            if ($posX > 170) {
                $posX = 5;
                $posY += 31;
                if ($posY > 250) {
                    $posY = 20;
                    $pdf->AddPage();
                }
            }
        }
        */
        $pdf->Output('barcodes'.uniqid().'.pdf', 'I');

        return false;
    }

    protected function post_id_print_handler()
    {
        return $this->get_id_print_handler();
    }

    protected function get_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $ret = '';

        $prep = $dbc->prepare("
            SELECT v.upc,
                v.sku,
                v.isPrimary,
                v.multiplier,
                p.description,
                p.size
            FROM VendorAliases AS v
                " . DTrans::joinProducts('v') . "
            WHERE v.vendorID=?
            ORDER BY v.sku,
                v.isPrimary DESC,
                v.upc");
        $ret .= '<table class="table table-bordered hidden">
            <thead>
                <th>Vendor SKU</th>
                <th>Our UPC</th>
                <th>Item</th>
                <th>Unit Size</th>
                <th>Multiplier</th>
                <th>&nbsp;</th>
                <th><span class="fas fa-print" onclick="$(\'.printUPCs\').prop(\'checked\', true);"></span></th>
            </thead><tbody>';
        $res = $dbc->execute($prep, array($this->id));
        while ($row = $dbc->fetchRow($res)) {
            $ret .= sprintf('<tr %s>
                <td>%s</td>
                <td><a href="../ItemEditorPage.php?searchupc=%s">%s</a></td>
                <td>%s</td>
                <td>%s</td>
                <td>%.2f</td>
                <td><a class="btn btn-default btn-xs btn-danger" href="?_method=delete&id=%d&sku=%s&upc=%s">%s</a></td>
                <td><input type="checkbox" class="printUPCs" name="printUPCs[]" value="%d" /></td>
                </tr>',
                ($row['isPrimary'] ? 'class="info"' : ''),
                $row['sku'],
                $row['upc'], $row['upc'],
                $row['description'],
                $row['size'],
                $row['multiplier'],
                $this->id, $row['sku'], $row['upc'], FannieUI::deleteIcon(),
                $row['upc']
            );
        }
        $ret .= '</tbody></table>';
        $test = FormLib::get('items');
        $items = preg_split('/\r\n|[\r\n]/', $test);
        $ret .= '<form id="tagForm" method="post">
            <input type="hidden" name="print" value="1" />
            <input type="hidden" name="id" value="' . $this->id . '" />
            </form>
            <form id="textareaform" method="post">
            <div class="form-group">
            </div>
            <div>
                <label for="items">Enter List of UPCs to Print Order Tags</label>
                <p>Enter 4 Bulk PLUs to print a sheet of ordering barcodes for
                    new bulk bin labels.</p>
            </div>
            <div id="enterItems">
                <div class="form-group">
                    <div style="padding-bottom: 4px;">
                        <textarea name="items" id="items" class="form-control" rows=15>'.$test.'</textarea>
                        <input name="print" value=1 type="hidden" />
                        <input name="id" value=1 type="hidden" />
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-default">Print Barcodes</button>
                </div>
            </div>
            </form>';


        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Create order tags for UNFI products assigned 
            internal UPCs (products sold in bulk, deli 
            production items, etc.). Enter a list of UPCs to 
            print tags with barcodes expressive of 
            the SKU corresponding to the UPC in POS. 
            </p>';
    }

}

FannieDispatch::conditionalExec();
