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

class registryUpdate {} // compat

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {

    include(dirname(__FILE__).'/../../../config.php');
    if (!class_exists('FannieAPI')) {
        include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
    }
    if (!class_exists('wfcuRegistryModel')) {
        include_once($FANNIE_ROOT.'modules/plugins2.0/WfcClassRegistry/wfcuRegistryModel.php');
    }

    $timeStamp = date('Y-m-d h:i:s');
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $item = new wfcuRegistryModel($dbc);    
    $ret = array('error'=>0);
    if (strlen($_POST['upc']) == 8) {
        $item->upc($_POST['upc']);
        $item->id($_POST['seat']);
    } else {
        $ret['error'] = 1;
    }

    if ($_POST['field'] === 'editFirst') {
        $item->first_name($_POST['value']);
        $item->modified($timeStamp);
        
        $prep = $dbc->prepare('SELECT first_name FROM wfcuRegistry WHERE upc=? AND first_name IS NOT NULL;');
        $result = $dbc->execute($prep, array($_POST['upc']));
        $countRows = 0;
        while($row = $dbc->fetch_row($result)) {
            $countRows++;
        }
    } elseif ($_POST['field'] === 'editLast') {
        $item->last_name($_POST['value']);
        $item->modified($timeStamp);
    } elseif ($_POST['field'] === 'editPhone') {
        $item->phone($_POST['value']);
        $item->modified($timeStamp);
    } elseif ($_POST['field'] === 'editCard_no') {
        $item->card_no($_POST['value']);
        $item->modified($timeStamp);
    } elseif ($_POST['field'] === 'editPayment') {
        $item->payment($_POST['value']);
        $item->modified($timeStamp);
    } elseif ($_POST['field'] === 'editNotes') {
        $item->details($_POST['value']);
        $item->modified($timeStamp);
    } elseif ($_POST['field'] === 'editRefund') {
        $item->refund($_POST['value']);
        $item->modified($timeStamp);
    } elseif ($_POST['field'] === 'editAmount') {
        $item->amount($_POST['value']);
        $item->modified($timeStamp);
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

}
