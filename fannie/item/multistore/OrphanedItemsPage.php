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

class OrphanedItemsPage extends FannieRESTfulPage
{
    protected $title = 'Orphaned Items';
    protected $header = 'Orphaned Items';
    public $description = '[Orphaned Items] are items that do not
        belong to any existing store.';

    protected $must_authenticate = true;
    protected $auth_classes = array('admin');

    public function preprocess()
    {
        $this->__routes[] = 'post<newID><oldID>';

        return parent::preprocess();
    }

    /**
      Re-assign items one at a time from the old store
      to the new store. Some may fail if the item already
      exists in the new store.
    */
    public function post_newID_oldID_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $query = $dbc->prepare('
            SELECT upc
            FROM products
            WHERE store_id=?');
        $result = $dbc->execute($query, array($this->oldID));
        $remapP = $dbc->prepare('
            UPDATE products
            SET store_id=?
            WHERE upc=?
                AND store_id=?');
        while ($w = $dbc->fetchRow($result)) {
            $dbc->execute($remapP, array($this->newID, $w['upc'], $this->oldID));
        }

        header('Location: ' . $_SERVER['PHP_SELF']);

        return false;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $query = '
            SELECT store_id,
                COUNT(*) AS numItems
            FROM products
            WHERE store_id NOT IN (
                SELECT storeID FROM Stores
            )
            GROUP BY store_id
            ORDER BY store_id';
        $result = $dbc->query($query);
        if ($dbc->numRows($result) == 0) {
            return '<div class="alert alert-success">All items belong to stores</div>';
        }

        $ret = '<table class="table table-bordered">';
        $ret .= '<tr>
            <th>Current store ID</th>
            <th># of items</th>
            <th>Re-assign to store</th>
            </tr>';
        $stores = new StoresModel($dbc);
        $opts = $stores->toOptions();
        while ($w = $dbc->fetchRow($result)) {
            $ret .= sprintf('
                <tr>
                    <td>%d</td>
                    <td>%d</td>
                    <td class="form-inline">
                        <form method="post">
                            <select name="newID" class="form-control">
                                <option value="">Choose store</option>
                                %s
                            </select>
                            <input type="hidden" name="oldID" value="%d" />
                            <button type="submit" class="btn btn-default btn-sm">Re-Assign</button>
                        </form>
                    </td>
                </tr>',
                $w['store_id'],
                $w['numItems'],
                $opts,
                $w['store_id']);
        }
        $ret .= '</table>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Locate items that do not belong to any existing
            store and assign them to a store.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

