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

class UnitBreakdownPage extends FannieRESTfulPage 
{
    protected $title = "Fannie : Vendor Unit Breakdowns";
    protected $header = "Vendor Unit Breakdowns";

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    public $description = '[Vendor Unit Breakdowns] manages items where the splits a package
        and sells items individually';

    public function preprocess()
    {
        $this->__routes[] = 'get<id><sku><plu>';
        $this->__routes[] = 'get<id><break>';

        return parent::preprocess();
    }

    public function get_id_break_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $model = new VendorBreakdownsModel($dbc);
        $model->vendorID($this->id);
        $original = new VendorItemsModel($dbc);
        $product = new ProductsModel($dbc);
        foreach ($model->find() as $obj) {
            $original->vendorID($this->id);
            $original->sku($obj->sku());
            if (!$original->load()) {
                $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Vendor SKU #" . $obj->sku() . " not found');\n");
                continue;
            }
            $split_factor = false;
            list($split_factor, $unit_size) = $obj->getSplit($original->size());
            if ($split_factor) {
                $obj->units($split_factor);
                $obj->save();
            }
            if (!$split_factor) {
                $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Vendor SKU #" . $obj->sku() . ' ' . $original->size() . " cannot be broken down');\n");
                continue;
            }

            // add an entry using the store UPC/PLU in place of the vendor SKU
            // since two records from the same vendor with same SKU are not 
            // permitted in the table
            $original->sku($obj->upc());
            $original->upc($obj->upc());
            $original->units(1);
            if ($unit_size != '') {
                $original->size($unit_size);
            }
            $original->cost($original->cost() / $split_factor);
            $original->saleCost($original->saleCost() / $split_factor);
            if ($original->save()) {
                // update cost in products table, too
                $product->reset();
                $product->upc($obj->upc());
                foreach ($product->find('store_id') as $p) {
                    if ($p->load() && $p->default_vendor_id() == $this->id) {
                        $p->cost($original->cost());
                        $p->save();
                        $original->description($p->description());
                        $original->save();
                    }
                }
            } else {
                $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Error saving vendor SKU #" . $obj->sku() . "');\n");
            }
        }

        return true;
    }

    public function delete_id_handler()
    {
        $sku = FormLib::get('sku');
        $plu = BarcodeLib::padUPC(FormLib::get('plu'));
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $delP = $dbc->prepare('
            DELETE FROM VendorBreakdowns
            WHERE vendorID=?
                AND sku=?
                AND upc=?');
        $delR = $dbc->execute($delP, array($this->id, $sku, $plu));
        $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Deleted entry for PLU #{$plu}')\n");

        return true;
    }

    public function get_id_sku_plu_handler()
    {
        if (empty($this->sku) || empty($this->plu)) {
            return true;
        }

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $this->plu = BarcodeLib::padUPC($this->plu);
        $this->sku = trim($this->sku);

        $skuP = $dbc->prepare('
            SELECT upc
            FROM VendorBreakdowns
            WHERE vendorID=?
                AND sku=?');
        $skuR = $dbc->execute($skuP, array($this->id, $this->sku));
        if ($skuR && $dbc->numRows($skuR) > 0) {
            $upP = $dbc->prepare('
                UPDATE VendorBreakdowns
                SET upc=?
                WHERE vendorID=?
                    AND sku=?');
            $upR = $dbc->execute($upP, array($this->plu, $this->id, $this->sku));
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Updated entry for SKU #{$this->sku}')\n");

            return true;
        }

        $pluP = $dbc->prepare('
            SELECT sku
            FROM VendorBreakdowns
            WHERE vendorID=?
                AND upc=?');
        $pluR = $dbc->execute($pluP, array($this->id, $this->plu));
        if ($pluR && $dbc->numRows($pluR) > 0) {
            $upP = $dbc->prepare('
                UPDATE VendorBreakdowns
                SET sku=?
                WHERE vendorID=?
                    AND upc=?');
            $upR = $dbc->execute($upP, array($this->sku, $this->id, $this->plu));
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Updated entry for PLU #{$this->plu}')\n");

            return true;
        }

        $insP = $dbc->prepare('
            INSERT INTO VendorBreakdowns
            (vendorID, sku, upc)
            VALUES
            (?, ?, ?)');
        $insR = $dbc->execute($insP, array($this->id, $this->sku, $this->plu));
        $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Added new entry for PLU #{$this->plu}')\n");

        return true;
    }

    public function delete_id_view()
    {
        return '<div id="alert-area"></div>' . $this->get_id_view();
    }

    public function get_id_sku_plu_view()
    {
        return '<div id="alert-area"></div>' . $this->get_id_view();
    }

    public function get_id_break_view()
    {
        return '<div id="alert-area"></div>' . $this->get_id_view();
    }

    public function get_id_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $prep = $dbc->prepare("
            SELECT m.sku,
                v.upc AS parentUPC,
                m.upc,
                v.description AS vendorDescript,
                p.description as storeDescript,
                m.units
            FROM VendorBreakdowns AS m
                " . DTrans::joinProducts('m') . "
                LEFT JOIN vendorItems AS v ON v.sku=m.sku AND v.vendorID=m.vendorID
            WHERE m.vendorID = ?
            ORDER BY m.upc
        ");

        $ret = '';

        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
        $ret .= '<div class="form-group form-inline">
            <label>SKU</label>
            <input type="text" class="form-control" name="sku" placeholder="Vendor SKU" />
            <label>PLU/UPC</label>
            <input type="text" class="form-control" name="plu" placeholder="Our PLU" />
            <button type="submit" class="btn btn-default">Add Entry</button>
            <input type="hidden" name="id" value="' . $this->id . '" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="?id=' . $this->id . '&break=1" class="btn btn-default">Run Breakdowns</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="VendorIndexPage.php?vid=' . $this->id . '" class="btn btn-default">Home</a>
            </div>
            </form>';

        $ret .= '<table class="table table-bordered">';
        $ret .= '<thead><tr>
            <th>Parent SKU</th>
            <th>Child UPC</th>
            <th>Parent Description</th>
            <th>Child Description</th>
            <th>Units</th>
            <th>&nbsp;</th>
            </tr></thead><tbody>';
        $res = $dbc->execute($prep, array($this->id));
        while ($row = $dbc->fetchRow($res)) {
            if (empty($row['vendorDescript'])) {
                $row['vendorDescript'] = '<span class="alert-danger">Discontinued by vendor?</span>';
            }
            if (empty($row['storeDescript'])) {
                $row['storeDescript'] = '<span class="alert-danger">Discontinued by us?</span>';
            }
            $ret .= sprintf('
                <tr>
                    <td><a href="../ItemEditorPage.php?searchupc=%s">%s</a></td>
                    <td><a href="../ItemEditorPage.php?searchupc=%s">%s</a></td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>
                        <a href="?_method=delete&id=%d&sku=%s&plu=%s" 
                        onclick="return confirm(\'Delete entry for PLU #%s?\');">%s</a>
                    </td>
                </tr>',
                $row['parentUPC'], $row['sku'],
                $row['upc'], $row['upc'],
                $row['vendorDescript'],
                $row['storeDescript'],
                $row['units'],
                $this->id, $row['sku'], $row['upc'],
                $row['upc'], COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
            );

        }

        $ret .= '</tbody></table>';
        $this->addScript('../../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addOnloadCommand("\$('.table').tablesorter([[1,0]]);\n");

        return $ret;
    }

    // redirect since ID is required
    public function get_handler()
    {
        return 'VendorIndexPage.php';
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
            Breakdowns are individual item entries in
            a vendor catalog that turn into multiple products
            that the store sells. The cannonical example is a
            soda purchased in a 6-pack from the vendor but sold
            as both 6-packs as well as singles. Running breakdowns
            will generate additional products from vendor items
            and calculate their costs based on the original
            items\' unit size.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->delete_id_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_id_sku_plu_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_id_break_view()));
        $phpunit->assertNotEquals(0, strlen($this->css_content()));
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_handler()));
    }
}

FannieDispatch::conditionalExec();

