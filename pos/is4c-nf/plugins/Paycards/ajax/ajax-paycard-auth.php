<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IT CORE.

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

if (basename($_SERVER['PHP_SELF']) != basename(__FILE__)){
    return;
}

ini_set('display_errors','Off');
include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

// send the request
$result = 0; // 0 is never returned, so we use it to make sure it changes
$myObj = 0;
$json = array();
$plugin_info = new Paycards();
$json['main_frame'] = $plugin_info->pluginUrl().'/gui/paycardSuccess.php';
$json['receipt'] = false;
foreach(CoreLocal::get("RegisteredPaycardClasses") as $rpc){
    $myObj = new $rpc();
    if ($myObj->handlesType(CoreLocal::get("paycard_type"))){
        break;
    }
}

$result = $myObj->doSend(CoreLocal::get("paycard_mode"));
if ($result === PaycardLib::PAYCARD_ERR_OK){
    PaycardLib::paycard_wipe_pan();
    $json = $myObj->cleanup($json);
    CoreLocal::set("strRemembered","");
    CoreLocal::set("msgrepeat",0);
} else if ($result === PaycardLib::PAYCARD_ERR_NSF_RETRY) {
    // card shows balance < requested amount
    // try again with lesser amount
    $json['main_frame'] = $plugin_info->pluginUrl().'/gui/paycardboxMsgAuth.php';
} else if ($result === PaycardLib::PAYCARD_ERR_TRY_VERIFY) {
    // communication error. query processor about
    // transaction status.
    $json['main_frame'] = $plugin_info->pluginUrl().'/gui/PaycardTransLookupPage.php?mode=verify&id=_l'.$myObj->last_ref_num;
} else {
    PaycardLib::paycard_reset();
    CoreLocal::set("msgrepeat",0);
    $json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';
}

echo json_encode($json);
