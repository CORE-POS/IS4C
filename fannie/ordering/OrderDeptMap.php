<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}

class OrderDeptMap extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    protected $header = 'Map Special Orders Departments';
    protected $title = 'Map Special Orders Departments';
    public $description = '[Special Order Departments] maps items\' normal department setting
    to specialized secondary departments (if necessary).';
    public $page_set = 'Special Orders';

    protected function post_id_handler()
    {
        $this->connection->selectDB($this->config->get('TRANS_DB'));
        $model = new SpecialOrderDeptMapModel($this->connection);
        $model->dept_ID($this->id);
        try {
            $model->map_to($this->form->mapID);
        } catch (Exception $ex) {
            $model->map_to($this->id);
        }
        try {
            $model->minQty($this->form->minQty);
        } catch (Exception $ex) {
            $model->minQty(0);
        }
        $model->save();

        return filter_input(INPUT_SERVER, 'PHP_SELF');
    }

    protected function updateForm()
    {
        $this->connection->selectDB($this->config->get('OP_DB'));
        $depts = new DepartmentsModel($this->connection);
        $opts = $depts->toOptions();
        $ret = '<form method="post">
            <div class="form-group form-inline">
            <label>Department #</label>
            <select name="id" class="form-control input-sm">
            ' . $opts . '
            </select>
            <label>Maps to</label>
            <select name="mapID" class="form-control input-sm">
            ' . $opts . '
            </select>
            <label>Min. Qty</labe>
            <input type="text" name="minQty" class="form-control input-sm price-field" />
            <button type="submit" class="btn btn-default">Add/Update</button>
            </div>
            </form>';

        return $ret;
    }

    protected function get_view()
    {
        $this->connection->selectDB($this->config->get('TRANS_DB'));
        $dbc = $this->connection;
        $model = new SpecialOrderDeptMapModel($this->connection);
        $prep = $dbc->prepare('
            SELECT dept_name
            FROM ' . $this->config->get('OP_DB') . $dbc->sep() . 'departments
            WHERE dept_no=?
        ');

        $ret = '<table class="table table-bordered">
            <thead><tr>
                <th>Department</th>
                <th>Maps To</th>
                <th>Min Order Qty</th>
            </tr></thead>
            <tbody>';
        foreach ($model->find('dept_ID') as $obj) {
            $ret .= sprintf('<tr>
                <td>%d %s</td>
                <td>%d %s</td>
                <td>%d</td>
                </tr>',
                $obj->dept_ID(),
                $dbc->getValue($prep, array($obj->dept_ID())),
                $obj->map_to(),
                $dbc->getValue($prep, array($obj->map_to())),
                $obj->minQty()
            );
        }
        $ret .= '</tbody></table>';

        return $this->updateForm() . $ret;
    }

    public function unitTest($phpunit)
    {
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->id = 1;
        $form->mapID = 2;
        $form->minQty = 3;
        $this->id = 1;
        $this->setForm($form);
        $this->post_id_handler();
        $model = new SpecialOrderDeptMapModel($this->connection);
        $model->dept_ID(1);
        $phpunit->assertEquals(true, $model->load());
        $phpunit->assertEquals(2, $model->map_to());
        $phpunit->assertEquals(3, $model->minQty());
        $this->connection->query('DELETE FROM SpecialOrderDeptMap WHERE dept_ID <> 1');
        $body = $this->get_view();
        $phpunit->assertNotEquals(0, strlen($body));
    }
}

FannieDispatch::conditionalExec();

