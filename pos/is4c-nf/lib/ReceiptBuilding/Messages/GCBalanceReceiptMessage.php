<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\lib\ReceiptBuilding\Messages;
use COREPOS\pos\lib\ReceiptLib;
use \CoreLocal;

/**
  @class GCBalanceReceiptMessage
*/
class GCBalanceReceiptMessage extends ReceiptMessage {

    public function select_condition(){
        return '0';
    }

    public function message($val, $ref, $reprint=False){
        return '';
    }

    /**
      Message can be printed independently from a regular    
      receipt. Pass this string to AjaxEnd.php as URL
      parameter receiptType to print the standalone receipt.
    */
    public $standalone_receipt_type = 'gcBalSlip';

    /**
      Print message as its own receipt
      @param $ref a transaction reference (emp-lane-trans)
      @param $reprint boolean
      @return [string] message to print 
    */
    public function standalone_receipt($ref, $reprint=False)
    {
        // balance inquiries are not logged and have no meaning in a reprint,
        // so we can assume that it just happened now and all data is still in session vars
        $tempArr = CoreLocal::get("paycard_response");
        if (!is_array($tempArr) || !isset($tempArr['Balance'])) {
            return '';
        }
        $bal = "$".number_format($tempArr["Balance"],2);
        $pan = CoreLocal::get("paycard_PAN"); // no need to mask gift card numbers
        $slip = ReceiptLib::normalFont()
                .ReceiptLib::centerString(".................................................")."\n";
        for ($i=1; $i<= CoreLocal::get('chargeSlipCount'); $i++) {
            $slip .= ReceiptLib::centerString(CoreLocal::get("chargeSlip" . $i))."\n";
        }
        $slip .= "\n"
                ."Gift Card Balance\n"
                ."Card: ".$pan."\n"
                ."Date: ".date('m/d/y h:i a')."\n"
                .ReceiptLib::boldFont()  // change to bold font for the total
                ."Balance: ".$bal."\n"
                .ReceiptLib::normalFont()
                .ReceiptLib::centerString(".................................................")."\n"
                ."\n";
        return $slip;
    }
}

