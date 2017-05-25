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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class VendorAliasesPage extends FannieRESTfulPage 
{
    protected $title = "Fannie : Vendor Aliases";
    protected $header = "Vendor Aliases";

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    public $description = '[Vendor Aliases] manages items that are sold under one or more UPCs that
        differ from the vendor catalog UPC.';

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

        $resp = array('error'=>($delR === false ? 1 : 0));

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
                <th>Item</th>
                <th>Unit Size</th>
                <th>Multiplier</th>
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
                </tr>',
                ($row['isPrimary'] ? 'class="info"' : ''),
                $row['sku'],
                $row['upc'], $row['upc'],
                $row['description'],
                $row['size'],
                $row['multiplier'],
                $this->id, $row['sku'], $row['upc'], FannieUI::deleteIcon()
            );
        }
        $ret .= '</tbody></table>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

