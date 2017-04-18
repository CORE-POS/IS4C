<?php
/*******************************************************************************

    Copyright 2009,2010 Whole Foods Co-op

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

class AdvancedBatchEditor extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('batches','batches_audited');
    protected $title = 'Sales Batches Tool';
    protected $header = 'Sales Batches Tool';

    public $description = '[Advanced Batches] is an editor for complex sales.';

    /**
      Save batch and redirect to display
    */
    public function post_id_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $list = new BatchListModel($dbc);
        $upcs = FormLib::get('upc', array());
        $prices = FormLib::get('price', array());
        $groups = FormLib::get('groupPrice', array());
        $methods = FormLib::get('priceMethod', array());
        $qtys = FormLib::get('quantity', array());
        for ($i=0; $i<count($upcs); $i++) {
            $list->reset();
            $list->batchID($this->id);
            $list->upc($upcs[$i]);
            $changed = false;
            if (isset($prices[$i])) {
                $list->salePrice($prices[$i]);
                $changed = true;
            }
            if (isset($groups[$i])) {
                $list->groupSalePrice($groups[$i]);
                $changed = true;
            }
            if (isset($methods[$i])) {
                $list->pricemethod($methods[$i]);
                $changed = true;
            }
            if (isset($qtys[$i])) {
                $list->quantity($qtys[$i]);
                $changed = true;
            }
            if ($changed) {
                $list->save();
            }
        }

        header('Location: ?id=' . $this->id);

        return false;
    }

    /**
      Display batch as form
    */
    public function get_id_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $list = new BatchListModel($dbc);
        $list->batchID($this->id);
        $ret = '<form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />
            <table class="table table-bordered table-striped tablesorter tablesorter-core">
            <thead>
            <tr>
                <th>UPC</th>
                <th>Brand</th>
                <th>Description</th>
                <th>Sale Price</th>
                <th>Group Price</th>
                <th>Price Method</th>
                <th>Quantity</th>
            </tr>
            </thead>
            <tbody>';
        $t_def = $dbc->tableDefinition('batchList');
        $prodP = $dbc->prepare('
            SELECT brand,
                description
            FROM products
            WHERE upc=?');
        foreach ($list->find('listID', true) as $item) {
            if (!isset($t_def['groupSalePrice']) || $item->groupSalePrice() == null) {
                $item->groupSalePrice($item->salePrice());
            }
            $prod = $dbc->getRow($prodP, array($item->upc()));
            $ret .= $this->printRow($item, $prod);
        }
        $ret .= '</tbody></table>
            <p>
                <button type="submit" class="btn btn-default">Save</button>
            </p>
            </form>';
        $this->addScript('../../src/javascript/tablesorter/jquery.tablesorter.min.js');
        $this->addOnloadCommand("\$('.tablesorter').tablesorter();");
        $this->addOnloadCommand("\$('.table input.form-control:first').focus();\n");

        return $ret;
    }

    private function printRow($item, $prod)
    {
        return sprintf('
            <tr>
                <td>%s<input type="hidden" name="upc[]" value="%s" /></td>
                <td>%s</td>
                <td>%s</td>
                <td><input type="text" class="form-control price-field input-sm"
                    value="%.2f" name="price[]" /></td>
                <td><input type="text" class="form-control price-field input-sm"
                    value="%.2f" name="groupPrice[]" /></td>
                <td><input type="text" class="form-control price-field input-sm"
                    value="%d" name="priceMethod[]" /></td>
                <td><input type="text" class="form-control price-field input-sm"
                    value="%d" name="quantity[]" /></td>
            </tr>',
            $item->upc(), $item->upc(),
            $prod['brand'],
            $prod['description'],
            $item->salePrice(),
            $item->groupSalePrice(),
            $item->pricemethod(),
            $item->quantity()
        );
    }

    /**
      Input a batch ID
    */
    public function get_view()
    {
        $this->addOnloadCommand("\$('.form-group input.form-control:first').focus();\n");

        return <<<HTML
<form method="get">
    <div class="form-group">
        <label>Batch ID</label>
        <input type="text" name="id" class="form-control" />
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default">Edit Batch</button>
    </div>
</form>
HTML;

    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }

    public function helpContent()
    {
        return '<p>The advanced batch editor provides rawer access to the underlying database.
            This tool is only necessary when fine-tuning the price, method, and quantity fields
            for use in more complex types of sales</p>';
    }
}

FannieDispatch::conditionalExec();

