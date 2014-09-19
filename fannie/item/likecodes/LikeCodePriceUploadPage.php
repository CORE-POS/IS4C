<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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

class LikeCodePriceUploadPage extends FannieUploadPage 
{
    protected $title = "Fannie :: Upload Likecode Prices";
    protected $header = "Upload Likecode Prices";

    public $description = '[Like Code Prices] uploads a spreadsheet of like codes and prices
    and immediately updates the prices for those like coded items.';

    protected $preview_opts = array(
        'likecode' => array(
            'name' => 'likecode',
            'display_name' => 'Like Code #',
            'default' => 0,
            'required' => true
        ),
        'price' => array(
            'name' => 'price',
            'display_name' => 'Price',
            'default' => 1,
            'required' => true
        ),
        'cost' => array(
            'name' => 'cost',
            'display_name' => 'Cost (Unit)',
            'default' => 2,
            'required' => false
        ),
    );

    function process_file($linedata)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $lc_index = $this->get_column_index('likecode');
        $price_index = $this->get_column_index('price');
        $cost_index = $this->get_column_index('cost');

        $ret = true;
        $update = $dbc->prepare('UPDATE products AS p
                            SET p.normal_price = ?,
                                p.modified = ' . $dbc->now() . '
                            WHERE p.upc IN
                                ( SELECT u.upc 
                                  FROM upcLike AS u
                                  WHERE u.likeCode=? )');
        $updateWithCost = $dbc->prepare('UPDATE products AS p
                            SET p.normal_price = ?,
                                p.cost = ?,
                                p.modified = ' . $dbc->now() . '
                            WHERE p.upc IN
                                ( SELECT u.upc 
                                  FROM upcLike AS u
                                  WHERE u.likeCode=? )');
        foreach($linedata as $line) {
            $lc = trim($line[$lc_index]);
            $price =  trim($line[$price_index], ' $');  
            $cost = 0;
            if ($cost_index !== false && isset($line[$cost_index])) {
                $cost = trim($line[$cost_index], ' $');
            }
            
            if (!is_numeric($lc)) continue; // skip header(s) or blank rows

            $try = false;
            if ($cost == 0) {
                $try = $dbc->execute($update, array($price, $lc));
            } else {
                $try = $dbc->execute($updateWithCost, array($price, $cost, $lc));
            }
            if ($try === false) {
                $ret = false;
                $this->error_details .= ' Problem updating LC# ' . $lc . ';';
            }
        }

        return $ret;
    }

    function form_content()
    {
        return '<fieldset><legend>Instructions</legend>
        Upload a CSV or XLS file containing likecode #s and prices. Cost 
        may also optionally be included.
        <br />A preview helps you to choose and map columns to the database.
        <br />The uploaded file will be deleted after the load.
        </fieldset><br />';
    }

    function results_content()
    {
        SyncLanes::pushTable('products');
        return 'Import completed successfully';
    }
}

FannieDispatch::conditionalExec(false);

