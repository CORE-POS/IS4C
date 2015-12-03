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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI.php')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class IncompleteItemsPage extends FannieRESTfulPage
{
    protected $title = 'Incomplete Items';
    protected $header = 'Incomplete Items';
    public $description = '[Incomplete Items] are items that exist
        at some but not all stores.';

    protected $must_authenticate = true;
    protected $auth_classes = array('admin');

    public function get_id_handler()
    {
        $upc = BarcodeLib::padUPC($this->id);
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $product = new ProductsModel($dbc);
        $product->upc($upc);
        $matches = $product->find('store_id');
        if (count($matches) > 0) {
            $product = $matches[0];
            $stores = new StoresModel($dbc);
            foreach ($stores->find('storeID') as $store) {
                $product->store_id($store->storeID());
                $product->save();
            }
        }

        return '../ItemEditorPage.php?searchupc=' . $upc;
    }

    /**
      For each store, examine all items.
      For all items in a given store, check
      whether it exists in all other stores.
      Copy the item to store(s) where it
      does not exist if needed.

      This may be better suited to a cron/CLI
      approach. Time required to check every
      item scales rapidly with both number of
      items and number of stores.
    */
    public function post_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $model = new StoresModel();
        $stores = array();
        foreach ($model->find() as $s) {
            $stores[] = $s->storeID();
        }

        $product = new ProductsModel();
        $chkP = $dbc->prepare('
            SELECT upc
            FROM products
            WHERE upc=?
                AND store_id=?');
        for ($i=0; $i<count($stores); $i++) {
            $store_id = $stores[$i];
            $product->store_id($store_id);
            foreach ($product->find() as $p) {
                for ($j=0; $j<count($stores); $j++) {
                    if ($i == $j) {
                        continue;
                    }
                    $chkR = $dbc->execute($chkP, array($p->upc(), $stores[$j]));
                    if ($dbc->numRows($chkR) == 0) {
                        $p->store_id($stores[$j]);
                        $p->save();
                    }
                }
            }
        }

        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        
        $stores = new StoresModel($dbc);
        $prod = $dbc->prepare('
            SELECT COUNT(*)
            FROM products
            WHERE store_id=?');

        $mismatch = false;
        $current_count = null;
        $ret = '<ul>';
        foreach ($stores->find() as $s) {
            $ret .= '<li>' . $s->description();
            $count = $dbc->getValue($prod, array($s->storeID()));
            $ret .= ' ' . number_format((int)$count) . ' items</li>';
            if ($current_count !== null && $count != $current_count) {
                $mismatch = true;
            }
            $current_count = $count;
        }
        $ret .= '</ul>';

        if ($mismatch) {
            $ret .= '<div class="alert alert-danger">Incomplete items detected</div>';
            /* do not enable until multi-store products table is ready to go
            $ret .= '<form method="post">
                <button type="submit" name="submit" class="btn btn-default">
                Fix Discrepancies
                </button>
                </form>';
            */
        }

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Locate items that do not exist at all stores.
            Items are not required to be active at all 
            stores but there should be one entry per
            item per store.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

