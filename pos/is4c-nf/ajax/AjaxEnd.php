<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

namespace COREPOS\pos\ajax;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\Drawers;
use COREPOS\pos\lib\Kickers\Kicker;
use COREPOS\pos\lib\PrintHandlers\PrintHandler;
use COREPOS\pos\lib\AjaxCallback;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\UdpComm;
use \CoreLocal;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

/**
  @class AjaxEnd
*/
class AjaxEnd extends AjaxCallback
{
    protected $encoding = 'json';

    public function ajax(array $input=array())
    {
        $receiptType = isset($input['receiptType']) ? $input['receiptType'] : FormLib::get('receiptType');
        if ($receiptType === '') {
            return array();
        }
        $receiptNum = isset($input['ref']) ? $input['ref'] : FormLib::get('ref');

        $transFinished = $this->transFinished($receiptType);
        if (!preg_match('/^\d+-\d+-\d+$/', $receiptNum)) {
            $receiptNum = ReceiptLib::mostRecentReceipt();
        }

        $dokick = $this->doKick($receiptNum, $transFinished);
        list($doEmail, $customerEmail) = $this->sendEmail();

        $receiptContent = array();
        if ($this->isDisabled($receiptType) || $receiptType === 'none') {
            $receiptContent = array();
        } else {
            if ($receiptType != "none") {
                $receiptContent[] = ReceiptLib::printReceipt($receiptType, $receiptNum, false, $doEmail);
            }
            if (CoreLocal::get('autoReprint') == 1 && $receiptType !== "ccSlip" && $receiptType !== 'gcSlip') {
                CoreLocal::set("autoReprint",0);
                $receiptContent[] = ReceiptLib::printReceipt($receiptType, $receiptNum, true);
            }
        }

        if ($transFinished) {
            UdpComm::udpSend("termReset");
            CoreLocal::set('ccTermState','swipe');
            $this->uploadAndReset($receiptType);
            CoreLocal::set("End",0);
        }

        // close session so if printer hangs
        // this script won't lock the session file
        if (session_id() != '') {
            session_write_close();
        }

        if ($receiptType == "full" && $dokick) {
            Drawers::kick();
        }

        $PRINT_OBJ = PrintHandler::factory(CoreLocal::get('ReceiptDriver'));
        $EMAIL_OBJ = $this->emailObj();
        foreach ($receiptContent as $receipt) {
            if (is_array($receipt)) {
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

        return array();
    }

    /**
      Disable receipt for cancelled and/or suspended
      transactions if configured to do so
    */
    private function isDisabled($receiptType)
    {
        if ($receiptType == 'cancelled' && CoreLocal::get('CancelReceipt') == 0 && CoreLocal::get('CancelReceipt') !== '') {
            return true;
        } elseif ($receiptType == 'suspended' && CoreLocal::get('SuspendReceipt') == 0 && CoreLocal::get('SuspendReceipt') !== '') {
            return true;
        } elseif ($receiptType == 'ddd' && CoreLocal::get('ShrinkReceipt') == 0 && CoreLocal::get('ShrinkReceipt') !== '') {
            return true;
        } else {
            return false;
        }
    }

    private function emailObj()
    {
        // use same email class for sending the receipt
        // as was used to generate the receipt
        $email_class = ReceiptLib::emailReceiptMod();
        return new $email_class();
    }

    private function sendEmail()
    {
        $email = trim(CoreState::getCustomerPref('email_receipt'));
        $customerEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
        $doEmail = ($customerEmail !== false) ? true : false;

        return array($doEmail, $customerEmail);
    }

    private function doKick($receiptNum, $transFinished)
    {
        $dokick = false;
        if ($transFinished) {
            $kicker_object = Kicker::factory(CoreLocal::get('kickerModule'));
            $dokick = $kicker_object->doKick($receiptNum);
        }

        return $dokick;
    }

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
    */
    private function transFinished($receiptType)
    {
        if ($receiptType == 'full' || $receiptType == 'cancelled' ||
            $receiptType == 'suspended' || $receiptType == 'none' ||
            $receiptType == 'ddd') {
            return true;
        } else {
            return false;
        }
    }

    private function uploadAndReset($type) 
    {
        if (CoreLocal::get("testremote")==0) {
            Database::testremote(); 
        }

        if (CoreLocal::get("TaxExempt") != 0) {
            CoreLocal::set("TaxExempt",0);
            Database::setglobalvalue("TaxExempt", 0);
        }

        CoreState::memberReset();
        CoreState::printReset();
        CoreState::transReset();

        Database::getsubtotals();

        return 1;
    }
}

AjaxEnd::run();

