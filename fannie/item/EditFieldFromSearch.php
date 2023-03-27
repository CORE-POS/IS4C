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
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class EditFieldFromSearch extends FannieRESTfulPage
{
    protected $header = 'Edit Search Results';
    protected $title = 'Edit Search Results';

    public $description = '[Edit Field for Search Results] takes a set of advanced search items and allows
    setting any field to a new value for all items. Must be accessed via Advanced Search.';
    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    private $upcs = array();

    function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       $this->__routes[] = 'post<upc><field><value>';
       $this->__routes[] = 'post<upc><signField><signValue>';
       return parent::preprocess();
    }

    protected function post_upc_signField_signValue_handler()
    {
        $model = new SignPropertiesModel($this->connection);
        $columns = $model->getColumns();
        if (!isset($columns[$this->signField])) {
            echo 'Invalid field';
            return false;
        }

        $args = array($this->signValue);
        list($inStr, $args) = $this->connection->safeInClause($this->upc, $args);
        $args[] = COREPOS\Fannie\API\lib\Store::getIdByIp();
        $query = "UPDATE SignProperties SET " 
            . $this->connection->identifierEscape($this->signField) . "=? 
            WHERE upc IN ({$inStr}) AND storeID = ?";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);

        return true;
    }

    protected function post_upc_field_value_handler()
    {
        $model = new ProductsModel($this->connection);
        $columns = $model->getColumns();
        if (!isset($columns[$this->field])) {
            echo 'Invalid field';
            return false;
        }

        $args = array($this->value);
        list($inStr, $args) = $this->connection->safeInClause($this->upc, $args);
        $query = "UPDATE products SET " 
            . $this->connection->identifierEscape($this->field) . "=?,
                modified=" . $this->connection->now() . "
            WHERE upc IN ({$inStr})";
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        if ($res) {
            $update = new ProdUpdateModel($this->connection);
            $update->logManyUpdates($this->upc, 'EDIT');
        }

        $queue = new COREPOS\Fannie\API\jobs\QueueManager();
        $queue->add(array(
            'class' => 'COREPOS\\Fannie\\API\\jobs\\SyncItem',
            'data' => array(
                'upc' => $this->upc,
            ),
        ));

        return true;
    }

    protected function post_upc_field_value_view()
    {
        $this->u = $this->upc;
        return '<div class="alert alert-success">Set ' . $this->field . ' to ' . $this->value . '</div>'
            . $this->post_u_view();
    }

    protected function post_upc_signField_signValue_view()
    {
        $this->u = $this->upc;
        return '<div class="alert alert-success">Set ' . $this->signField . ' to ' . $this->signValue . '</div>'
            . $this->post_u_view();
    }

    protected function post_u_view()
    {
        $model = new ProductsModel($this->connection);
        $columns = $model->getColumns();
        $opts = '<option value="">Select field...</option>';
        $ignoreColumn = array('upc', 'store_id', 'id', 'modified', 'last_sold', 'created');
        foreach ($columns as $name => $info) {
            if (!in_array($name, $ignoreColumn)) {
                $opts .= '<option>' . $name . '</option>';
            }
        }

        $s_def = $this->connection->tableExists('SignProperties');
        if ($s_def !== false) {
            $signModel = new SignPropertiesModel($this->connection);
            $signColumns = $signModel->getColumns();
            $signOpts = '<option value="">Select field...</option>';
            $signIgnoreColumn = array('upc', 'storeID');
            foreach ($signColumns as $name => $info) {
                if (!in_array($name, $signIgnoreColumn)) {
                    $signOpts .= '<option>' . $name . '</option>';
                }
            }
        }

        $items = '<table class="table table-bordered small">';
        $items2 = '';
        list($inStr, $args) = $this->connection->safeInClause($this->u);
        $prep = $this->connection->prepare("SELECT upc, brand, description FROM products WHERE upc IN ({$inStr}) GROUP BY upc, brand, description");
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $items .= sprintf('<tr><td>%s<input type="hidden" name="upc[]" value="%s" /></td><td>%s</td><td>%s</td></tr>',
                $row['upc'], $row['upc'], $row['brand'], $row['description']);
            $items2 .= "<input type=\"hidden\" name=\"upc[]\" value=\"{$row['upc']}\" />";
        }
        $items .= '</table>';

        return <<<HTML
<div class="row">
    <div class="col-lg-4">
        <h3>Product Data</h3>
        <p>NOT Store Specific</p>
        <form method="post" action="EditFieldFromSearch.php">
            <div class="form-group">
                <label>Field</label>
                <select name="field" required class="form-control">{$opts}</select>
            </div>
            <div class="form-group">
                <label>New Value</label>
                <input type="text" class="form-control" name="value" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Update Items</button>
            </div>
            {$items2}
        </form>
    </div>
    <div class="col-lg-4">
        <h3>Sign Data</h3>
        <p>Store Specific</p>
        <form method="post" action="EditFieldFromSearch.php">
            <div class="form-group">
                <label>Field</label>
                <select name="signField" required class="form-control">{$signOpts}</select>
            </div>
            <div class="form-group">
                <label>New Value</label>
                <input type="text" class="form-control" name="signValue" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default btn-core">Update Items</button>
            </div>
            {$items2}
        </form>
    </div>
    <div class="col-lg-4"></div>
</div>
<hr />
{$items}
HTML;
    }
}

FannieDispatch::conditionalExec();

