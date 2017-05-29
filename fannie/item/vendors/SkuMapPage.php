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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SkuMapPage extends FannieRESTfulPage 
{
    protected $title = "Fannie : Vendor SKU Map";
    protected $header = "Vendor SKU Map";

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    public $description = '[Vendor SKU Map] uses SKUs to map items that have a different
        vendor UPC than store UPC. Typically the "store" UPC is a PLU.';

    public function preprocess()
    {
        $this->__routes[] = 'get<id><sku><plu>';
        $this->__routes[] = 'get<id><apply>';
        $this->__routes[] = 'get<id><print>';

        return parent::preprocess();
    }

    protected function get_id_print_handler()
    {
        $pdf = new FPDF('P', 'mm', 'Letter');
        $pdf->AddPage();
        $pdf->SetMargins(10,10,10);
        $pdf->SetFont('Arial', '', 7);
        $dbc = $this->connection;
        $prep = $dbc->prepare('
            SELECT p.description, v.sku, n.vendorName, p.brand
            FROM products AS p
                INNER JOIN vendorSKUtoPLU AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                INNER JOIN vendors AS n ON p.default_vendor_id=n.vendorID
            WHERE v.vendorID=? 
            GROUP BY p.description, v.sku, n.vendorName'); 
        $res = $dbc->execute($prep, $this->id);
        $posX = 5;
        $posY = 20;
        while ($row = $dbc->fetchRow($res)) {
            //$prepB = $dbc->prepare('SELECT units, size FROM vendorItems WHERE sku = ?');
            $prepB = $dbc->prepare('SELECT max(receivedDate), caseSize, unitSize, brand FROM PurchaseOrderItems WHERE sku = ?');
            $resB = $dbc->execute($prepB, $row['sku']);
            $tagSize = array();
            $tagSize = $dbc->fetch_row($resB);
            $pdf->SetXY($posX+3, $posY);
            $pdf->Cell(0, 5, substr($row['description'], 1, 25));
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
        $plu = BarcodeLib::padUPC(FormLib::get('plu'));
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $delP = $dbc->prepare('
            DELETE FROM vendorSKUtoPLU
            WHERE vendorID=?
                AND sku=?
                AND upc=?');
        $delR = $dbc->execute($delP, array($this->id, $sku, $plu));

        $resp = array('error'=>($delR === false ? 1 : 0));
        echo json_encode($resp);

        return false;
    }

    protected function get_id_apply_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $map = new VendorSKUtoPLUModel($dbc); 
        $map->vendorID($this->id);
        $upP = $dbc->prepare('
            UPDATE vendorItems
            SET upc=?
            WHERE sku=?
                AND vendorID=?');
        $costP = $dbc->prepare('
            UPDATE products
            SET cost=(SELECT cost FROM vendorItems WHERE vendorID=? AND sku=?)
            WHERE upc=?');
        foreach ($map->find() as $obj) {
            $res = $dbc->execute($upP, array($obj->upc(), $obj->sku(), $this->id));
            $res = $dbc->execute($costP, array($this->id, $obj->sku(), $obj->upc()));
        }
        $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Updated vendor catalog')\n");

        return true;
    }

    protected function get_id_sku_plu_handler()
    {
        if (empty($this->sku) || empty($this->plu)) {
            return true;
        }

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $this->plu = BarcodeLib::padUPC($this->plu);

        if ($this->updateSKU($dbc, $this->id, $this->sku, $this->plu)) {
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Updated entry for SKU #{$this->sku}')\n");
        } elseif ($this->updatePLU($dbc, $this->id, $this->sku, $this->plu)) {
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Updated entry for PLU #{$this->plu}')\n");
        } else {
            $this->addMapping($dbc, $this->id, $this->sku, $this->plu);
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Added new entry for PLU #{$this->plu}')\n");
        }

        return true;
    }

    private function updateSKU($dbc, $id, $sku, $plu)
    {
        $skuP = $dbc->prepare('
            SELECT upc
            FROM vendorSKUtoPLU
            WHERE vendorID=?
                AND sku=?');
        $skuR = $dbc->execute($skuP, array($id, $sku));
        if ($skuR && $dbc->numRows($skuR) > 0) {
            $upP = $dbc->prepare('
                UPDATE vendorSKUtoPLU
                SET upc=?
                WHERE vendorID=?
                    AND sku=?');
            $upR = $dbc->execute($upP, array($plu, $id, $sku));

            return true;
        } else {
            return false;
        }
    }

    private function updatePLU($dbc, $id, $sku, $plu)
    {
        $pluP = $dbc->prepare('
            SELECT sku
            FROM vendorSKUtoPLU
            WHERE vendorID=?
                AND upc=?');
        $pluR = $dbc->execute($pluP, array($id, $plu));
        if ($pluR && $dbc->numRows($pluR) > 0) {
            $upP = $dbc->prepare('
                UPDATE vendorSKUtoPLU
                SET sku=?
                WHERE vendorID=?
                    AND upc=?');
            $upR = $dbc->execute($upP, array($sku, $id, $plu));
            return true;
        } else {
            return false;
        }
    }

    private function addMapping($dbc, $id, $sku, $plu)
    {
        $insP = $dbc->prepare('
            INSERT INTO vendorSKUtoPLU
            (vendorID, sku, upc)
            VALUES
            (?, ?, ?)');
        $insR = $dbc->execute($insP, array($id, $sku, $plu));
    }

    protected function get_id_apply_view()
    {
        return '<div id="alert-area"></div>' . $this->get_id_view();
    }

    protected function get_id_sku_plu_view()
    {
        return '<div id="alert-area"></div>' . $this->get_id_view();
    }

    protected function get_id_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $this->addScript('skuMap.js');

        $prep = $dbc->prepare("
            SELECT m.sku,
                m.upc,
                v.description AS vendorDescript,
                p.description as storeDescript
            FROM vendorSKUtoPLU AS m
                " . DTrans::joinProducts('m') . "
                LEFT JOIN vendorItems AS v ON v.sku=m.sku AND v.vendorID=m.vendorID
            WHERE m.vendorID = ?
            ORDER BY m.upc
        ");

        $ret = '';

        $ret .= '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="get">';
        $ret .= '<div class="form-group form-inline">
            <label>SKU</label>
            <input type="text" class="form-control" id="sku" name="sku" placeholder="Vendor SKU" />
            <label>PLU</label>
            <input type="text" class="form-control" name="plu" placeholder="Our PLU" />
            <button type="submit" class="btn btn-default">Add Entry</button>
            <a href="?id=' . $this->id . '&apply=1" class="btn btn-default">Update Catalog</a>
            <input type="hidden" name="id" value="' . $this->id . '" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="VendorIndexPage.php?vid=' . $this->id . '" class="btn btn-default">Home</a>
            </div>
            </form>';

        $ret .= '<table class="table table-bordered">';
        $ret .= '<thead><tr>
            <th>Vendor SKU</th>
            <th>Our PLU</th>
            <th>Vendor Description</th>
            <th>Our Description</th>
            <th>&nbsp;</th>
            </tr></thead><tbody>';
        $res = $dbc->execute($prep, array($this->id));
        while ($row = $dbc->fetchRow($res)) {
            $ret .= $this->rowToTable($row);
        }

        $ret .= '</tbody></table>
            <p><a href="?print=1&id=' . $this->id . '" class="btn btn-default">Print Order Tags</a></p>';
        $this->addScript('../../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addOnloadCommand("\$('.table').tablesorter([[1,0]]);\n");
        $this->addOnloadCommand("\$('#sku').focus();\n");

        return $ret;
    }

    private function rowToTable($row) 
    {
        if (empty($row['vendorDescript'])) {
            $row['vendorDescript'] = '<span class="alert-danger">Discontinued by vendor?</span>';
        }
        if (empty($row['storeDescript'])) {
            $row['storeDescript'] = '<span class="alert-danger">Discontinued by us?</span>';
        }
        return sprintf('
            <tr>
                <td>%s</td>
                <td><a href="../ItemEditorPage.php?searchupc=%s">%s</a></td>
                <td>%s</td>
                <td>%s</td>
                <td>
                    <a href=""
                    onclick="return skuMap.deleteRow(%d, \'%s\', \'%s\', this);">%s</a>
                </td>
            </tr>',
            $row['sku'],
            $row['upc'], $row['upc'],
            $row['vendorDescript'],
            $row['storeDescript'],
            $this->id, $row['sku'], $row['upc'], COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
        );
    }

    public function css_content()
    {
        return '
                .table thead th {
                    cursor: hand;
                    cursor: pointer;
                }
            ';
    }

    public function helpContent()
    {
        return '<p>
            Create a permanent mapping between certain vendor item SKUs
            and store product UPCs. This is used when importing entire
            catalogs from spreadsheet type sources. The process usually
            rebuilds the catalog from scratch. This separate mapping
            preserves SKU => UPC relationships through that rebuild.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_apply_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_id_sku_plu_view()));
        $phpunit->assertNotEquals(0, strlen($this->css_content()));
        $row = array('vendorDescript'=>'', 'storeDescript'=>'', 'sku'=>'111',
            'upc'=>'4011');
        $phpunit->assertNotEquals(0, strlen($this->rowToTable($row)));
    }
}

FannieDispatch::conditionalExec();

