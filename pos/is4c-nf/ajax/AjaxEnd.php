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
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\Drawers;
use COREPOS\pos\lib\Kickers\Kicker;
use COREPOS\pos\lib\PrintHandlers\PrintHandler;
use COREPOS\pos\lib\AjaxCallback;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\UdpComm;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

/**
  @class AjaxEnd
*/
class AjaxEnd extends AjaxCallback
{
    protected $encoding = 'json';

    public function ajax()
    {
        $receiptType = $this->form->tryGet('receiptType');
        if ($receiptType === '') {
            return array();
        }
        $receiptNum = $this->form->tryGet('ref');

        $transFinished = $this->transFinished($receiptType);
        if (!preg_match('/^\d+-\d+-\d+$/', $receiptNum)) {
            $receiptNum = ReceiptLib::mostRecentReceipt();
        }

        $dokick = $this->doKick($receiptNum, $transFinished);
        list($doEmail, $customerEmail) = $this->sendEmail();

        $receiptContent = array();
        if (!$this->isDisabled($receiptType) && $receiptType !== 'none') {
            $receiptContent[] = ReceiptLib::printReceipt($receiptType, $receiptNum, false, $doEmail);
            if ($this->session->get('autoReprint') == 1 && $receiptType !== "ccSlip" && $receiptType !== 'gcSlip') {
                $this->session->set("autoReprint",0);
                $receiptContent[] = ReceiptLib::printReceipt($receiptType, $receiptNum, true);
            }
        }

        if ($transFinished) {
            UdpComm::udpSend("termReset");
            $this->session->set('ccTermState','swipe');
            $this->uploadAndReset();
            $this->session->set("End",0);
        }

        // close session so if printer hangs
        // this script won't lock the session file
        if (session_id() != '') {
            session_write_close();
        }

        if ($receiptType == "full" && $dokick) {
            $drawer = new Drawers($this->session, null);
            $drawer->kick();
        }

        $this->outputReceipt($receiptContent, $customerEmail);

        return array();
    }

    /**
      Output the receipt to printer and/or email
      @param $receiptContent [mixed string OR array]
        A string will always be printed
        An array will print the 'print' part and email the 'any' part
      @param $customerEmail [string] customer email address
    */
    private function outputReceipt($receiptContent, $customerEmail)
    {
        $printObj = PrintHandler::factory($this->session->get('ReceiptDriver'));
        $emailObj = $this->emailObj();
        foreach ($receiptContent as $receipt) {
            if (is_array($receipt)) {
                if (!empty($receipt['print'])) {
                    $printObj->writeLine($receipt['print']);
                }
                if (!empty($receipt['any'])) {
                    $emailObj->writeLine($receipt['any'],$customerEmail);
                }
            } elseif(!empty($receipt)) {
                $printObj->writeLine($receipt);
            }
        }
    }

    /**
      Disable receipt for cancelled and/or suspended
      transactions if configured to do so
    */
    private function isDisabled($receiptType)
    {
        if ($receiptType == 'cancelled' && $this->session->get('CancelReceipt') == 0 && $this->session->get('CancelReceipt') !== '') {
            return true;
        } elseif ($receiptType == 'suspended' && $this->session->get('SuspendReceipt') == 0 && $this->session->get('SuspendReceipt') !== '') {
            return true;
        } elseif ($receiptType == 'ddd' && $this->session->get('ShrinkReceipt') == 0 && $this->session->get('ShrinkReceipt') !== '') {
            return true;
        }

        return false;
    }

    private function emailObj()
    {
        // use same email class for sending the receipt
        // as was used to generate the receipt
        $emailClass = ReceiptLib::emailReceiptMod();
        return new $emailClass();
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
            $kickerObject = Kicker::factory($this->session->get('kickerModule'));
            $dokick = $kickerObject->doKick($receiptNum);
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
        }

        return false;
    }

    private function uploadAndReset() 
    {
        if ($this->session->get("testremote")==0) {
            Database::testremote(); 
        }

        if ($this->session->get("TaxExempt") != 0) {
            $this->session->set("TaxExempt",0);
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

