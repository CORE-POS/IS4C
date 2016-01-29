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



$dbc = FannieDB::get($FANNIE_OP_DB);
$item = new wfcuRegistryModel($dbc);    
$ret = array('error'=>0);
if (strlen($_POST['upc'] == 8) {
    $item->upc($_POST['upc']);
    $item->seat($_POST['seat'];
} else {
    $ret['error'] = 1;
}

if ($_POST['field'] === 'editFirst') {
    $item->first_name($_POST['value']);
} elseif ($_POST['field'] === 'editLast') {
    $item->last_name($_POST['value']);
} elseif ($_POST['field'] === 'editPhone') {
    $item->phone($_POST['value']);
} elseif ($_POST['field'] === 'Card_no') {
    $item->card_no($_POST['value']);
} elseif ($_POST['field'] === 'editPayment') {
    $item->payment($_POST['value']);
} elseif ($_POST['field'] === 'editOptFirst') {
    $item->first_opt_name($_POST['value']);
} elseif ($_POST['field'] === 'editOptLast') {
    $item->last_opt_name($_POST['value']);
} else {
    $ret['error'] = 1;
    $ret['error_msg'] = 'Unknown field';
}

if ($ret['error'] == 0) {
    $saved = $item->save();
    if (!$saved) {
        $ret['error'] = 1;
        $ret['error_msg'] = 'Save failed';
    }
}

echo json_encode($ret);