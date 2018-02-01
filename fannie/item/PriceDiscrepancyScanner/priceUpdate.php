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

include(dirname(__FILE__).'/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class priceUpdate
{
    public $discoverable = false;

    protected function post_handler()
    {
        $store_id = FormLib::get('store_id');
        $upc = FormLib::get('upc');
        $price = FormLib::get('price');
    
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $item = new ProductsModel($dbc);    
        $ret = array('error'=>1);
        if (strlen($upc) == 13) {
            $item->upc($upc);
            $item->store_id($store_id);
            $item->normal_price($price);        
            $saved = $item->save();
            $ret['error'] = 0;
            if (!$saved) {
                $ret['error'] = 1;
                $ret['error_msg'] = 'Save failed';
            }
        }
    
        echo json_encode($ret);

        return false;
    }
}

FannieDispatch::conditionalExec();

