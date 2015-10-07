<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ConsistencyRulesPage extends FannieRESTfulPage
{
    protected $header = 'Product Consistency Rules';
    protected $title = 'Product Consistency Rules';
    public $description = '[Product Conssistency Rules] define which item settings are allowed to vary
    on a per-store basis';

    public function preprocess()
    {
        $this->initRules();
        return parent::preprocess();
    }

    /**
      Add a rule for all non-ID columns in products
    */
    private function initRules()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $products = $this->connection->tableDefinition('products');
        $skip = array('upc', 'store_id', 'id');

        $hasRule = $this->connection->prepare('
            SELECT variable
            FROM ConsistentProductRules
            WHERE ' . $this->connection->identifierEscape('column') . '=?
        ');
        $addRule = $this->connection->prepare('
            INSERT INTO ConsistentProductRules
                (' . $this->connection->identifierEscape('column') . ')
            VALUES
                (?)
        ');

        foreach ($products as $column => $info) {
            if (in_array($column, $skip)) {
                continue;
            }
            if ($this->connection->getValue($hasRule, array($column)) === false) {
                $this->connection->execute($addRule, array($column));
            }
        }
    }

    protected function post_id_handler()
    {
        $model = new ConsistentProductRulesModel($this->connection);
        for ($i=0; $i<count($this->id); $i++) {
            $model->column($this->id[$i]);
            try {
                if (in_array($this->id[$i], $this->form->varies)) {
                    $model->variable(1);
                } else {
                    $model->variable(0);
                }
            } catch (Exception $ex) {
                $model->variable(0);
            }
            $model->save();
        }

        return filter_input(INPUT_SERVER, 'PHP_SELF');
    }

    protected function get_view()
    {
        $ret = '<form method="post">
            <table class="table table-bordered">
            <tr>
                <th>Column Name</th>
                <th>May Vary</th>
            </tr>';
        $model = new ConsistentProductRulesModel($this->connection);
        foreach ($model->find() as $obj) {
            $ret .= sprintf('<tr>
                <td>%s<input type="hidden" name="id[]" value="%s" /></td>
                <td><input type="checkbox" name="varies[]" value="%s" %s /></td>
                </tr>',
                $obj->column(), $obj->column(),
                $obj->column(), ($obj->variable() ? 'checked' : '')
            );
        }
        $ret .= '</table>';
        $ret .= '<p><button type="submit" class="btn btn-default btn-core">Save</button></p>
            </form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            This is a fairly low-level setting. In a multistore environment, some
            item settings will be expected to be consistent across all stores while
            other settings may vary at different stores. This simply defines whether
            or not each item setting can vary. Additional tools for auditing and 
            correcting unexpected inconsistencies build on top of this.
            </p>';
    }
}

FannieDispatch::conditionalExec();

