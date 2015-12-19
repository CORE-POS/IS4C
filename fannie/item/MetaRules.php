<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Community Co-op

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

if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}

class MetaRules extends FannieRESTfulPage
{
    public $description = '[Meta Product Rules] controls which item fields can vary across stores.';

    protected $title = 'Meta Product Rules';
    protected $header = 'Meta Product Rules';

    protected function post_id_handler()
    {
        $prod = new ProductsModel($this->connection);
        $meta = new MetaProductRulesModel($this->connection);
        $valid = array_keys($prod->getColumns());
        $checks = FormLib::get('variable', array());
        if (is_array($this->id)) {
            foreach ($this->id as $col) {
                if (in_array($col, $valid)) {
                    $meta->colName($col);
                    $meta->variable(in_array($col, $checks) ? 1 : 0);
                    $meta->save();
                }
            }
        }

        return filter_input(INPUT_SERVER, 'PHP_SELF');
    }

    protected function get_view()
    {
        $ret = '<form method="post">
            <table class="table table-bordered table-striped">
            <tr><th>Field</th><th>Allowed to Vary</th>';
        $prod = new ProductsModel($this->connection);
        $meta = $this->connection->prepare('
            SELECT variable FROM MetaProductRules WHERE colName=?
        ');
        foreach ($prod->getColumns() as $col => $info) {
            if (in_array($col, array('upc', 'store_id', 'id'))) {
                continue;
            }
            $ret .= sprintf('<tr> 
                <td>%s<input type="hidden" name="id[]" value="%s" /></td>
                <td><input type="checkbox" name="variable[]" value="%s" %s /></td>
                </tr>',
                $col, $col,
                $col, 
                $this->connection->getValue($meta, array($col)) == 1 ? 'checked' : '');
        }
        $ret .= '</table>
            <p>
                <button type="submit" class="btn btn-default">Save</button>
            </p>
            </form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            In a multi-store environment, more than one copy of each item
            will exist. The meta rules control which attributes of the item
            must be identical across all stores and which attributes are 
            allowed to vary.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = array('inUse');
        $this->post_id_handler(); // return value unreliable in testing
    }
}

FannieDispatch::conditionalExec();

