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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DeleteItemPage extends FannieRESTfulPage
{
    protected $header = 'Delete Item';
    protected $title = 'Delete Item';

    public $description = '[Delete item] removes an item from the system.';

    protected $must_authenticate = true;
    protected $auth_classes = array('delete_items');

    public function preprocess()
    {
        $this->__routes[] = 'get<id><confirm>';
        return parent::preprocess();
    }

    public function get_id_confirm_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $upc = BarcodeLib::padUPC($this->id);

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $stores = new StoresModel($dbc);
        foreach ($stores->find() as $s) {
            $model->store_id($s->storeID());
            $model->delete();
        }

        if (substr($upc, 0, 3) == '002') {
            $scaleQ = $dbc->prepare_statement("DELETE FROM scaleItems WHERE plu=?");
            $dbc->exec_statement($scaleQ,array($upc));
            $plu = substr($upc, 3, 4);
            \COREPOS\Fannie\API\item\HobartDgwLib::deleteItemsFromScales($plu);
            \COREPOS\Fannie\API\item\EpScaleLib::deleteItemsFromScales($plu);
        }

        $userP = $dbc->prepare("DELETE FROM productUser WHERE upc=?");
        $dbc->execute($userP,array($upc));

        if ($dbc->tableExists('prodExtra')) {
            $extraP = $dbc->prepare("DELETE FROM prodExtra WHERE upc=?");
            $dbc->execute($extraP,array($upc));
        }

        return '<div class="alert alert-success">Item deleted</div>';
    }

    public function get_id_view()
    {
        $upc = BarcodeLib::padUPC($this->id);
        $dbc = $this->connection;
        $prep = $dbc->prepare('SELECT * FROM products WHERE upc=?');
        $res = $dbc->execute($prep, array($upc));
        if (!$res || $dbc->numRows($res) == 0) {
            return '<div class="alert alert-danger">Item not found</div>'
                . $this->get_view();
        }
        $row = $dbc->fetchRow($res);

        return '<div class="alert alert-warning">Delete this item?</div>
            <p>
            <a href="ItemEditorPage.php?searchupc=' . $upc . '">' . $upc . '</a>
            - ' . $row['description'] . ' $' . sprintf('%.2f', $row['normal_price']) . '
            </p>
            <p>
            <a href="?confirm=1&id=' . $upc . '" class="btn btn-default">Yes, delete this item</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="DeleteItemPage.php" class="btn btn-default">No, keep this item</a>
            </p>';
    }

    public function get_view()
    {
        $this->addOnloadCommand('$(\'input:first\').focus();');
        return <<<HTML
<form method="get">
<div class="form-group">
    <label>UPC</label>
    <input type="text" name="id" class="form-control" />
</div>
<div class="form-group">
    <button type="submit" class="btn btn-default">Lookup Item</button>
</div>
</form>
HTML;
    }
}

FannieDispatch::conditionalExec();

