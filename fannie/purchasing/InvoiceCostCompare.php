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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class InvoiceCostCompare extends FannieRESTfulPage 
{

    protected $header = 'Compare Costs';
    protected $title = 'Compare Costs';

    public $description = '[Invoice Cost Comparison] reconciles invoice costs against
    vendor catalog costs.';

    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<date1><date2>';
        $this->__routes[] = 'get<vendors><date1><date2>';

        return parent::preprocess();
    }

    public function post_view()
    {
        $dbc = $this->connection;
        $dbc->setDefaultDB($this->config->get('OP_DB'));

        $vendorItem = new VendorItemsModel($dbc);
        $product = new ProductsModel($dbc);

        $updates = FormLib::get('update');
        $ret = '';
        foreach ($updates as $update) {
            $update = json_decode($update, true);
            $vendorItem->reset();
            $vendorItem->sku($update['sku']);
            $vendorItem->vendorID($update['vendorID']);
            if (!$vendorItem->load()) {
                $ret .= '<div class="alert alert-danger">' . $update['sku'] . ' missing from catalog</div>'; 
                continue;
            }

            $currentCost = $vendorItem->cost();
            $vendorItem->cost($update['cost']);
            $ret .= '<div class="alert alert-info">Changing ' . $update['sku'] . ' from ' . $currentCost . ' to ' . $update['cost'] . '</div>';
            $vendorItem->save();

            $product->reset();
            $product->upc($vendorItem->upc());
            if ($product->load() && $product->default_vendor_id() == $vendorItem->vendorID()) {
                $ret .= '<div class="alert alert-info">Updating product cost as well</div>';
                $product->cost($vendorItem->cost());
                $product->save();
            }
        }

        return $ret;
    }

    public function get_vendors_date1_date2_view()
    {
        $dbc = $this->connection;
        $dbc->setDefaultDB($this->config->get('OP_DB'));

        $catalogP = $dbc->prepare('
            SELECT cost
            FROM vendorItems
            WHERE vendorID=?
                AND sku=?');
        $invoiceQ = '
            SELECT o.vendorID,
                sku,
                MAX(i.description) AS description,
                MAX(v.vendorName) as vendorName,
                AVG(unitCost) AS cost
            FROM PurchaseOrder AS o
                INNER JOIN PurchaseOrderItems AS i ON o.orderID=i.orderID
                INNER JOIN vendors AS v ON o.vendorID=v.vendorID
            WHERE o.placedDate BETWEEN ? AND ?
                AND o.vendorID IN (';
        $args = array($this->date1, $this->date2);
        $ids = '';
        foreach ($this->vendors as $v) {
            $args[] = $v;
            $ids .= '?,';
        }
        $ids = substr($ids, 0, strlen($ids)-1);
        $invoiceQ .= $ids . ')
            GROUP BY o.vendorID,
                i.sku';
        $invoiceP = $dbc->prepare($invoiceQ);
        $invoiceR = $dbc->execute($invoiceP, $args);
        $ret = '<form method="post">
            <table class="table table-bordered">
            <thead>
            <tr>
                <th>Vendor</th>
                <th>SKU</th>
                <th>Item</th>
                <th>Invoice Cost</th>
                <th>Catalog Cost</th>
                <th>Update Catalog</th>
            </tr>
            </thead>
            <tbody>';
        while ($w = $dbc->fetchRow($invoiceR)) {
            $tr = sprintf('<tr __class__>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%.2f</td>',
                $w['vendorName'],
                $w['sku'],
                $w['description'],
                $w['cost']);
            $catalogCost = 0;
            $catalogR = $dbc->execute($catalogP, array($w['vendorID'], $w['sku']));
            if ($catalogR && $dbc->numRows($catalogR)) {
                $catalogW = $dbc->fetchRow($catalogR);
                $catalogCost = $catalogW['cost'];
            }
            if ($w['cost'] == $catalogCost) {
                // nothing to update
                continue;
            } elseif ($w['cost'] == 0) {
                // invoice missing cost info
                continue;
            }
            $updateJSON = json_encode(array(
                'vendorID' => $w['vendorID'],
                'sku' => $w['sku'],
                'cost' => $w['cost'],
            ));
            $checked = $catalogCost == 0 ? true : false;
            $tr .= sprintf('<td>%.2f</td>
                    <td><input type="checkbox" name="update[]" value=\'%s\' %s /></td>
                    </tr>',
                    $catalogCost,
                    $updateJSON,
                    ($checked ? 'checked' : '') 
                    );
            if ($catalogCost == 0) {
                $tr = str_replace('__class__', 'class="success"', $tr);
            } elseif ($w['cost'] > $catalogCost) {
                $tr = str_replace('__class__', 'class="info"', $tr);
            } else {
                $tr = str_replace('__class__', 'class="warning"', $tr);
            }

            $ret .= $tr;
        }
        $ret .= '</tbody></table>
            <p>
                <button type="submit" class="btn btn-default">Update Selected</button>
            </p>
            </form>';

        return $ret;
    }

    public function get_date1_date2_view()
    {
        $dbc = $this->connection;
        $dbc->setDefaultDB($this->config->get('OP_DB'));

        $prep = $dbc->prepare("
            SELECT p.vendorID,
                v.vendorName
            FROM PurchaseOrder AS p
                INNER JOIN vendors AS v ON p.vendorID=v.vendorID
            WHERE p.placedDate BETWEEN ? AND ?
            GROUP BY p.vendorID, 
                v.vendorName");
        $vendors = $dbc->execute($prep, array($this->date1, $this->date2));

        $ret = '<form method="get">
            <input type="hidden" name="date1" value="' . $this->date1 . '" />
            <input type="hidden" name="date2" value="' . $this->date2 . '" />
            <select name="vendors[]" multiple size="15" class="form-control">';
        while ($w = $dbc->fetchRow($vendors)) {
            $ret .= sprintf('<option value="%d">%s</option>', $w['vendorID'], $w['vendorName']);
        }
        $ret .= '</select>
            <p>
                <button type="submit" class="btn btn-default">Submit</button>
            </p>
            </form>';

        return $ret;
    }

    public function get_view()
    {
        return '<form method="get">
            <div class="form-group">
                <label>Start Date</label>
                <input type="text" name="date1" class="form-control date-field" />
            </div>
            <div class="form-group">
                <label>End Date</label>
                <input type="text" name="date2" class="form-control date-field" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Submit</button>
            </div>
            </form>';
    }
}

FannieDispatch::conditionalExec();

