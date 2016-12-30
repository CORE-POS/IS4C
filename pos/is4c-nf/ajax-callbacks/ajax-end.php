<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

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

ini_set('display_errors','Off');
include_once(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));

$receiptType = isset($_REQUEST['receiptType'])?$_REQUEST['receiptType']:'';
$receiptNum = isset($_REQUEST['ref']) ? $_REQUEST['ref'] : '';
header('Location: ../ajax/AjaxEnd.php?receiptType=' . $receiptType . '&ref=' . $receiptNum);

/**
  Use requested receipt type to determine whether transaction
  should be completed and flushed from localtemptrans rather
  than relying on session variables.
  - full => normal transaction receipt
  - cancelled => transaction cancelled
  - suspended => transaction suspended
  - ddd  => shrink items
  - none => don't print a receipt, just flush localtemptrans

  Note: none is currently only used by the RRR parser which
  could probably be refactored into a plugin providing its
  own receipt type implementation via a ReceiptMessage
$transFinished = false;
if ($receiptType == 'full' || $receiptType == 'cancelled' ||
    $receiptType == 'suspended' || $receiptType == 'none' ||
    $receiptType == 'ddd') {
    
    $transFinished = true;
}

if (!preg_match('/^\d+-\d+-\d+$/', $receiptNum)) {
    $receiptNum = ReceiptLib::mostRecentReceipt();
}

$yesSync = JsonLib::array_to_json(array('sync'=>true));
$noSync = JsonLib::array_to_json(array('sync'=>false));
$output = $noSync;

ob_start();

if (strlen($receiptType) > 0) {

    $receiptContent = array();

    if ($transFinished) {
        $kicker_class = (CoreLocal::get("kickerModule")=="") ? 'Kicker' : CoreLocal::get('kickerModule');
        $kicker_object = new $kicker_class();
        if (!is_object($kicker_object)) {
            $kicker_object = new Kicker();
        }
        $dokick = $kicker_object->doKick($receiptNum);
    }

    $print_class = CoreLocal::get('ReceiptDriver');
    if ($print_class === '' || !class_exists($print_class)) {
        $print_class = 'ESCPOSPrintHandler';
    }
    $PRINT_OBJ = new $print_class();

    $email = trim(CoreState::getCustomerPref('email_receipt'));
    $customerEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
    $doEmail = ($customerEmail !== false) ? true : false;
    
    if ($receiptType != "none") {
        $receiptContent[] = ReceiptLib::printReceipt($receiptType, $receiptNum, false, $doEmail);
    }

    if ($receiptType == "ccSlip" || $receiptType == 'gcSlip') {
        // don't mess with reprints
    } elseif (CoreLocal::get("autoReprint") == 1) {
        CoreLocal::set("autoReprint",0);
        $receiptContent[] = ReceiptLib::printReceipt($receiptType, $receiptNum, true);
    }
    // use same email class for sending the receipt
    // as was used to generate the receipt
    $email_class = ReceiptLib::emailReceiptMod();

    if ($transFinished) {
        $output = $yesSync;
        UdpComm::udpSend("termReset");
        $sd = MiscLib::scaleObject();
        if (is_object($sd)) {
            //$sd->readReset();
        }
        CoreLocal::set('ccTermState','swipe');
        uploadAndReset($receiptType);
        CoreLocal::set("End",0);
    }

    // close session so if printer hangs
    // this script won't lock the session file
    if (session_id() != '') {
        session_write_close();
    }

    if ($receiptType == "full" && $dokick) {
        ReceiptLib::drawerKick();
    }

    // Disable receipt for cancelled and/or suspended
    // transactions if configured to do so
    if ($receiptType == 'cancelled' && CoreLocal::get('CancelReceipt') == 0 && CoreLocal::get('CancelReceipt') !== '') {
        $receiptContent = array();
    } elseif ($receiptType == 'suspended' && CoreLocal::get('SuspendReceipt') == 0 && CoreLocal::get('SuspendReceipt') !== '') {
        $receiptContent = array();
    } elseif ($receiptType == 'ddd' && CoreLocal::get('ShrinkReceipt') == 0 && CoreLocal::get('ShrinkReceipt') !== '') {
        $receiptContent = array();
    }

    $EMAIL_OBJ = new $email_class();
    foreach($receiptContent as $receipt) {
        if(is_array($receipt)) {
            if (!empty($receipt['print'])) {
                $PRINT_OBJ->writeLine($receipt['print']);
            }
            if (!empty($receipt['any'])) {
                $EMAIL_OBJ->writeLine($receipt['any'],$customerEmail);
            }
        } elseif(!empty($receipt)) {
            $PRINT_OBJ->writeLine($receipt);
        }
    }
}

echo $output;
ob_end_flush();

function uploadAndReset($type) 
{
    if (CoreLocal::get("testremote")==0) {
        Database::testremote(); 
    }

    if (CoreLocal::get("TaxExempt") != 0) {
        CoreLocal::set("TaxExempt",0);
        Database::setglobalvalue("TaxExempt", 0);
    }

    CoreState::memberReset();
    CoreState::transReset();
    CoreState::printReset();

    Database::getsubtotals();

    return 1;
}

*/

