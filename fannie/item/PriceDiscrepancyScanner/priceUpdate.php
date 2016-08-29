<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class priceUpdate {} // compat

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {

    include(dirname(__FILE__).'/../../config.php');
    if (!class_exists('FannieAPI')) {
        include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
    }
    if (!class_exists('PriceDiscrepancyPage')) {
        include_once($FANNIE_ROOT.'item/PriceDiscrepancyPage.php');
    }

    $store_id = $_POST['store_id'];
    $upc = $_POST['upc'];
    $price = $_POST['price'];
    
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $item = new ProductsModel($dbc);    
    $ret = array('error'=>0);
    if (strlen($upc) == 13) {
        $item->upc($upc);
    } else {
        $ret['error'] = 1;
    }
    
    $item->store_id($store_id);
    $item->normal_price($price);        
    $ret .= '
        <div class="alert alert-info">
            <form method="get">
                <input type="text" name="test' . $i . '">
                <input type="submit">
            </form>
            This form was added to the page through ajax. 
        </div>
    ';
    
    if ($ret['error'] == 0) {
        $saved = $item->save();
        if (!$saved) {
            $ret['error'] = 1;
            $ret['error_msg'] = 'Save failed';
        }
    }

    echo json_encode($ret);

}
