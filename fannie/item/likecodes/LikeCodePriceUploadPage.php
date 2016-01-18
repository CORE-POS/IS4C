<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
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

class LikeCodePriceUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    protected $title = "Fannie :: Upload Likecode Prices";
    protected $header = "Upload Likecode Prices";

    public $description = '[Like Code Prices] uploads a spreadsheet of like codes and prices
    and immediately updates the prices for those like coded items.';

    protected $preview_opts = array(
        'likecode' => array(
            'display_name' => 'Like Code #',
            'default' => 0,
            'required' => true
        ),
        'price' => array(
            'display_name' => 'Price',
            'default' => 1,
            'required' => true
        ),
        'cost' => array(
            'display_name' => 'Cost (Unit)',
            'default' => 2,
        ),
    );

    private function prepareStatements($dbc)
    {
        $update = $dbc->prepare('
            UPDATE products AS p
                INNER JOIN upcLike AS u ON p.upc=u.upc
            SET p.normal_price = ?,
                p.modified = ' . $dbc->now() . '
            WHERE u.likeCode=?');
        $updateWithCost = $dbc->prepare('
            UPDATE products AS p
                INNER JOIN upcLike AS u ON p.upc=u.upc
            SET p.normal_price=?,
                p.cost = ?,
                p.modified = ' . $dbc->now() . '
            WHERE u.likeCode=?');
        if ($dbc->dbmsName() == 'mssql') {
            $update = $dbc->prepare('
                UPDATE products
                SET normal_price = ?,
                    modified = ' . $dbc->now() . '
                FROM products AS p
                    INNER JOIN upcLike AS u ON p.upc=u.upc
                WHERE u.likeCode=?');
            $updateWithCost = $dbc->prepare('
                UPDATE products
                SET normal_price=?,
                    cost = ?,
                    modified = ' . $dbc->now() . '
                FROM products AS p
                    INNER JOIN upcLike AS u ON p.upc=u.upc
                WHERE u.likeCode=?');
        }

        return array($update, $updateWithCost);
    }

    public function process_file($linedata, $indexes)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = true;
        list($update, $updateWithCost) = $this->prepareStatements($dbc);
        $this->stats = array('done' => 0, 'error' => array());
        foreach ($linedata as $line) {
            $likecode = trim($line[$indexes['likecode']]);
            $price =  trim($line[$indexes['price']], ' $');  
            $cost = 0;
            if ($indexes['cost'] !== false && isset($line[$indexes['cost']])) {
                $cost = trim($line[$indexes['cost']], ' $');
            }
            
            if (!is_numeric($likecode)) continue; // skip header(s) or blank rows

            $try = false;
            if ($cost == 0) {
                $try = $dbc->execute($update, array($price, $likecode));
            } else {
                $try = $dbc->execute($updateWithCost, array($price, $cost, $likecode));
            }
            if ($try === false) {
                $ret = false;
                $this->stats['error'][] = ' Problem updating LC# ' . $likecode . ';';
            } else {
                $this->stats['done']++;
            }
        }

        return $ret;
    }

    function form_content()
    {
        return '<div class="well"><legend>Instructions</legend>
        Upload a CSV or XLS file containing likecode #s and prices. Cost 
        may also optionally be included.
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </div><br />';
    }

    function results_content()
    {
        \COREPOS\Fannie\API\data\SyncLanes::pushTable('products');
        $ret = '<p>Import Complete</p>';
        $ret .= '<div class="alert alert-success">Updated ' . $this->stats['done'] . ' likecodes</div>';
        if (count($this->stats['error']) > 0) {
            $ret .= '<div class="alert alert-danger"><ul>';
            foreach ($this->stats['error'] as $error) {
                $ret .= '<li>' . $error . '</li>';
            }
            $ret .= '</ul></div>';
        }

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $data = array(1, 1.99, 0.99);
        $indexes = array('likecode'=>0, 'price'=>1, 'cost'=>2);
        $this->process_file(array($data), $indexes);
    }
}

FannieDispatch::conditionalExec();

