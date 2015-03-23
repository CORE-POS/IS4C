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
    protected $title = "Fannie : Vendor Case Breakdowns";
    protected $header = "Vendor Case Breakdowns";

    public $description = '[Vendor Case Breakdowns] manages items where the splits a package
        and sells items individually';
    public $themed = true;

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
            $unit_size = '';
            if (preg_match('/^\d+$/', $original->size())) {
                $split_factor = $original->size();
            } elseif (preg_match('/(\d+)\s*\\/\s*(.+)/', $original->size(), $matches)) {
                $split_factor = $matches[1];
                $unit_size = $matches[2];
            }
            if (!$split_factor) {
                $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Vendor SKU #" . $original->size() . " cannot be broken down');\n");
                continue;
            }

            // add an entry using the store UPC/PLU in place of the vendor SKU
            // since two records from the same vendor with same SKU are not 
            // permitted in the table
            $original->sku($obj->upc());
            $original->upc($obj->upc());
            $original->units(1);
            $original->size($unit_size);
            $original->cost($original->cost() / $split_factor);
            $original->saleCost($original->saleCost() / $split_factor);
            if ($original->save()) {
                $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Vendor SKU #" . $obj->sku() . " broken down');\n");
                // update cost in products table, too
                $product->reset();
                $product->upc($obj->upc());
                if ($product->load() && $product->default_vendor_id() == $this->id) {
                    $product->cost($original->cost());
                    $product->save();
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
                m.upc,
                v.description AS vendorDescript,
                p.description as storeDescript
            FROM VendorBreakdowns AS m
                LEFT JOIN products AS p ON p.upc=m.upc
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
            if (empty($row['vendorDescript'])) {
                $row['vendorDescript'] = '<span class="alert-danger">Discontinued by vendor?</span>';
            }
            if (empty($row['storeDescript'])) {
                $row['storeDescript'] = '<span class="alert-danger">Discontinued by us?</span>';
            }
            $ret .= sprintf('
                <tr>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>
                        <a href="?_method=delete&id=%d&sku=%s&plu=%s" 
                        onclick="return confirm(\'Delete entry for PLU #%s?\');">%s</a>
                    </td>
                </tr>',
                $row['sku'],
                $row['upc'],
                $row['vendorDescript'],
                $row['storeDescript'],
                $this->id, $row['sku'], $row['upc'],
                $row['upc'], FannieUI::deleteIcon()
            );

        }

        $ret .= '</tbody></table>';
        $this->addScript('../../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addOnloadCommand("\$('.table').tablesorter([[1,0]]);\n");

        return $ret;
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
}

FannieDispatch::conditionalExec();

