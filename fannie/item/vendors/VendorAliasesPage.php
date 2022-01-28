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

class VendorAliasesPage extends FannieRESTfulPage 
{
    protected $title = "Fannie : Vendor Aliases";
    protected $header = "Vendor Aliases";

    protected $must_authenticate = true;
    protected $auth_classes = array('admin');

    public $description = '[Vendor Aliases] manages items that are sold under one or more UPCs that
        differ from the vendor catalog UPC.';

    public function preprocess()
    {
        $this->__routes[] = 'get<id><print>';
        $this->__routes[] = 'post<id><print>';

        return parent::preprocess();
    }

    protected function post_id_print_handler()
    {
        return $this->get_id_print_handler();
    }

    protected function get_id_print_handler()
    {
        $store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $mtLength = $store == 1 ? 3 : 7;

        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(10,10,10);
        $pdf->SetFont('Arial', '', 7);
        $dbc = $this->connection;
        $upcs = FormLib::get('printUPCs', array());
        $upcs = array_map(function ($i) { return BarcodeLib::padUPC($i); }, $upcs);
        $args = array($this->id, $store);
        list($inStr, $args) = $dbc->safeInClause($upcs, $args);
        $prep = $dbc->prepare('
            SELECT p.description, v.sku, n.vendorName, p.brand, MAX(p.auto_par) AS auto_par
            FROM products AS p
                INNER JOIN VendorAliases AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                INNER JOIN vendors AS n ON p.default_vendor_id=n.vendorID
            WHERE v.vendorID=? 
                AND p.store_id=?
                AND p.upc IN (' . $inStr . ')
            GROUP BY p.description, v.sku, n.vendorName'); 
        $res = $dbc->execute($prep, $args);
        $posX = 5;
        $posY = 20;
        while ($row = $dbc->fetchRow($res)) {
            //$prepB = $dbc->prepare('SELECT units, size FROM vendorItems WHERE sku = ?');
            $prepB = $dbc->prepare('SELECT max(receivedDate), caseSize, unitSize, brand FROM PurchaseOrderItems WHERE sku = ?');
            $resB = $dbc->execute($prepB, $row['sku']);
            $tagSize = array();
            $tagSize = $dbc->fetch_row($resB);
            $pdf->SetXY($posX+3, $posY);
            $pdf->Cell(0, 5, substr($row['description'], 0, 25));
            $pdf->Ln(3);
            $pdf->SetX($posX+3);
            $pdf->Cell(0, 5, $row['vendorName'] . ' - ' . $row['sku']);
            $img = Image_Barcode2::draw($row['sku'], 'code128', 'png', false, 20, 1, false);
            $file = tempnam(sys_get_temp_dir(), 'img') . '.png';
            imagepng($img, $file);
            $pdf->Image($file, $posX, $posY+7);
            unlink($file);
            $pdf->SetXY($posX+3, $posY+16);
            $pdf->Cell(0, 5, $tagSize['unitSize'] . ' / ' . $tagSize['caseSize'] . ' - ' . $tagSize['brand']);
            $pdf->SetXY($posX+35, $posY+17.5);
            $border = $mtLength == 7 ? 'TBR' : 'TBL';
            $pdf->Cell(8, 4, sprintf('%.1f', $mtLength * $row['auto_par']), $border, 0, 'C');
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
        $pdf->Output('skus.pdf', 'I');

        return false;
    }

    protected function delete_id_handler()
    {
        $sku = FormLib::get('sku');
        $upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $delP = $dbc->prepare('
            DELETE FROM VendorAliases
            WHERE vendorID=?
                AND sku=?
                AND upc=?');
        $delR = $dbc->execute($delP, array($this->id, $sku, $upc));

        return 'VendorAliasesPage.php?id=' . $this->id;
    }

    protected function post_id_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $upc = FormLib::get('upc', '');
        $sku = FormLib::get('sku', '');
        if (trim($upc) === '' || trim($sku) === '') {
            return true;
        }
        $upc = BarcodeLib::padUPC($upc);
        $isPrimary = FormLib::get('isPrimary', 0);
        $multiplier = FormLib::get('multiplier');

        $alias = new VendorAliasesModel($dbc);
        $alias->vendorID($this->id);
        $alias->upc($upc);
        $alias->sku($sku);
        $alias->isPrimary($isPrimary);
        $alias->multiplier($multiplier);
        $saved = $alias->save();

        if ($isPrimary) {
            $alias->reset();
            $alias->vendorID($this->id);
            $alias->upc($upc);
            $alias->isPrimary(1);
            foreach ($alias->find() as $obj) {
                if ($obj->upc() != $upc) {
                    $obj->isPrimary(0);
                    $obj->save();
                }
            }
        }

        return true;
    }

    protected function post_id_view()
    {
        return '<div id="alert-area"></div>' . $this->get_id_view();
    }

    protected function get_id_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $ret = '<form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />
            <p><div class="form-inline container-fluid">
                <label>UPC</label>
                <input class="form-control input-sm" type="text" name="upc" placeholder="UPC" />
                <label>SKU</label>
                <input type="text" class="form-control input-sm" name="sku" placeholder="SKU" />
                <label>Primary Alias <input type="checkbox" name="isPrimary" value="1" /></label>
                <label>Multiplier</label>
                <input type="number" min="0" max="100" step="0.01" name="multiplier" value="1.0" 
                    class="form-control input-sm" />
                <button type="submit" class="btn btn-submit btn-core">Add/Update</button>
            </div></p>
            </form>';

        $prep = $dbc->prepare("
            SELECT v.upc,
                v.sku,
                v.isPrimary,
                v.multiplier,
                p.brand,
                p.description,
                p.size
            FROM VendorAliases AS v
                " . DTrans::joinProducts('v') . "
            WHERE v.vendorID=?
            ORDER BY v.sku,
                v.isPrimary DESC,
                v.upc");
        $ret .= '<table class="table table-bordered">
            <thead>
                <th>Vendor SKU</th>
                <th>Our UPC</th>
                <th>Brand</th>
                <th>Description</th>
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
                <td>%s</td>
                <td>%.2f</td>
                <td><a class="btn btn-default btn-xs btn-danger" href="?_method=delete&id=%d&sku=%s&upc=%s">%s</a></td>
                <td><input type="checkbox" class="printUPCs" name="printUPCs[]" value="%d" /></td>
                </tr>',
                ($row['isPrimary'] ? 'class="info"' : ''),
                $row['sku'],
                $row['upc'], $row['upc'],
                $row['brand'],
                $row['description'],
                $row['size'],
                $row['multiplier'],
                $this->id, $row['sku'], $row['upc'], FannieUI::deleteIcon(),
                $row['upc']
            );
        }
        $ret .= '</tbody></table>';
        $ret .= '<form id="tagForm" method="post">
            <input type="hidden" name="print" value="1" />
            <input type="hidden" name="id" value="' . $this->id . '" />
            <button type="button" class="btn btn-default"
                onclick="$(\'.printUPCs:checked\').each(function (i) {
                    console.log($(this).val());
                    $(\'#tagForm\').append(\'<input type=hidden name=printUPCs[] value=\' + $(this).val() + \' />\');
                }); $(\'#tagForm\').submit();"
            >Print Scan Tags</button>
            </form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Aliases are items that are sold under alternate UPCs or PLUs. Aliases supplant
            both SKU maps and Breakdown items. A given vendor SKU may have multiple aliases
            but only one primary alias. The primary alias is shown with the actual vendor SKU
            associated and is the unit that is counted for inventory purposes. The multiplier
            is a conversion factor between a given alias and the primary alias. If the primary
            alias is a single item and there\'s a secondary alias that\'s a four-pack the multiplier
            on the latter alias will be four.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertInternalType('string', $this->get_id_view());
        $phpunit->assertInternalType('string', $this->post_id_view());
        $phpunit->assertInternalType('string', $this->delete_id_handler());
        $phpunit->assertEquals(true, $this->post_id_handler());
    }
}

FannieDispatch::conditionalExec();

