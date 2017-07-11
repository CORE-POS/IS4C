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

class CloneItemPage extends FannieRESTfulPage
{
    public $description = '[Clone Item] creates a duplicate of an item with a new UPC.';
    public $themed = true;
    protected $enable_linea = true;

    protected $title = 'Clone Item';
    protected $header = 'Clone Item';

    public function post_id_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $new = FormLib::get('new-upc');
        
        if ($new == '') {
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'New UPC cannot be blank');\n");

            return true;
        }

        $new = BarcodeLib::padUPC($new);
        $model = new ProductsModel($dbc);
        $model->upc($new);
        if ($model->load()) {
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'New item {$new} already exists');\n");

            return true;
        }

        $model->reset();
        $model->upc(BarcodeLib::padUPC($this->id));
        if (!$model->load()) {
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Source item " . $model->upc() . " not found');\n");

            return true;
        }

        $model->upc($new);
        $description = substr('CLONE ' . $model->description(), 0, 30);
        $model->description($description);
        $model->store_id(1);
        $model->created(date('Y-m-d H:i:s'));
        $model->save();

        if ($dbc->tableExists('prodExtra')) {
            $extra = new ProdExtraModel($dbc);
            $extra->upc(BarcodeLib::padUPC($this->id));
            $extra->load();
            $extra->upc($new);
            $extra->save();
        }

        if ($dbc->tableExists('productUser')) {
            $user = new ProductUserModel($dbc);
            $user->upc(BarcodeLib::padUPC($this->id));
            $user->load();
            $user->upc($new);
            $user->save();
        }

        header('Location: ItemEditorPage.php?searchupc=' . $new);

        return false;
    }

    public function post_id_view()
    {
        return '<div id="alert-area"></div>' . $this->get_id_view();
    }

    public function get_id_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $model = new ProductsModel($dbc);
        $model->upc(BarcodeLib::padUPC($this->id));
        if (!$model->load()) {
            return '<div class="alert alert-danger">Item ' . $this->id . ' does not exist</dv>';
        }

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">
            <input type="hidden" name="id" value="' . $model->upc() . '" />
            <p>
                Create a copy of ' . $model->upc() . ' (' . $model->description() . ')
            </p>
            <div class="form-group">
                <label>New Item UPC</label>
                <input type="text" name="new-upc" class="form-control" id="new-upc" required />
            </div>
            <p>
                <button type="submit" class="btn btn-default">Clone Item</button>
            </p>
            </form>';
        $this->addOnloadCommand("enableLinea('#new-upc');\n");
        $this->addOnloadCommand("\$('#new-upc').focus();\n");

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Cloning creates a new product that is 
            identical to an exisiting product but has a different
            UPC. The new item will have "CLONE" added to its
            description for distinctive search results. Editing
            the cloned item description is recommended.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->id = '4011';
        $phpunit->assertNotEquals(0, strlen($this->post_id_view()));
        $this->id = 'not-an-item';
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $phpunit->assertEquals(true, $this->post_id_handler());
    }
}

FannieDispatch::conditionalExec();

