<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

class DeleteVendorItems {}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    
    include(dirname(__FILE__) . '/../../config.php');
    if (!class_exists('FannieAPI')) {
        include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
    }

    $upc = FormLib::get('upc');
    $sku = FormLib::get('sku');
    $vendorID = FormLib::get('vendorID');

    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $item = new VendorItemsModel($dbc);

    $item->upc($upc);
    $item->sku($sku);
    $item->vendorID($vendorID);
       
    $deleted = $item->delete();
    if (!$deleted) {
        $ret['error'] = 1;
        $ret['error_msg'] = 'Save failed';
    }

    echo json_encode($ret);    
}
