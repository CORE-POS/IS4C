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
use COREPOS\pos\lib\MemberLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\ReceiptLib;
use \CoreLocal;

/**
  @class StoreChargeMessage

  This replaces a pair of receipt methods that were/are
  hardcoded into ReceiptLib. Every receipt that has charge
  activity includes the current balance as a footer. That's
  the primary message provided by this class. A transaction
  that includes a charge may also trigger a signature slip
  if paper signature slips are being used. The signature
  slip is provided by standalone receipt.
*/
class StoreChargeMessage extends ReceiptMessage 
{
    /**
      This message has to be printed on paper
    */
    public $paper_only = false;

    public function select_condition()
    {
        $arDepts = MiscLib::getNumbers(CoreLocal::get('ArDepartments'));
        if (count($arDepts) == 0) {
            return ' CASE WHEN trans_subtype=\'MI\' THEN 1 ELSE 0 END ';
        }

        return " CASE WHEN trans_subtype='MI' OR department IN (" . implode(',', $arDepts) . ") THEN 1 ELSE 0 END ";
    }

    /**
      Generate the message
      @param $val the value returned by the object's select_condition()
      @param $ref a transaction reference (emp-lane-trans)
      @param $reprint boolean
      @return [string] message to print on receipt
    */
    public function message($val, $ref, $reprint=false)
    {
        MemberLib::chargeOk();

        $labels = array();
        $labels['charge'] = array(_("Current IOU Balance:") , 1);
        $labels['debit'] = array(_("Debit available:"), -1);
        if (CoreLocal::get('InvertAR')) {
            $labels['charge'][1] = -1;
        }

        $currActivity = CoreLocal::get("memChargeTotal");
        $currBalance = CoreLocal::get("balance") - $currActivity;

        if (($numRows > 0 || $currBalance != 0) && CoreLocal::get("memberID") != CoreLocal::get('defaultNonMem')) {
            $chargeString = $labels["$program"][0] .
                " $".sprintf("%.2f",($labels["$program"][1] * $currBalance));
            $receipt = "\n\n"
                . $this->printHandler->textStyle(true, false, true)
                . $this->printHandler->centerString($chargeString)
                . $this->printHandler->textStyle(true) . "\n";

            return $receipt;
        }

        return '';
    }

    public function standalone_receipt($ref, $reprint=false)
    {
        $chgName = MemberLib::getChgName();
        $dateTimeStamp = time();
        $date = ReceiptLib::build_time($dateTimeStamp);
        $program = 'charge';

        /* Where should the label values come from, be entered?
           20Mar15 Eric Lee. Andy's comment was about Coop Cred which
             is now implemented as he describes.
           24Apr14 Andy
           Implementing these as ReceiptMessage subclasses might work
           better. Plugins could provide their own ReceiptMessage subclass
           with the proper labels (or config settings for the labels)
        */
        $labels = array();
        $labels['charge'] = array(
                _("CUSTOMER CHARGE ACCOUNT\n"),
                _("Charge Amount:"),
                _("I AGREE TO PAY THE ABOVE AMOUNT\n"),
                _("TO MY CHARGE ACCOUNT\n"),
        );
        $labels['debit'] = array(
                _("CUSTOMER DEBIT ACCOUNT\n"),
                _("Debit Amount:"),
                _("I ACKNOWLEDGE THE ABOVE DEBIT\n"),
                _("TO MY DEBIT ACCOUNT\n"),
        );

        /* Could append labels from other modules
        foreach (CoreLocal::get('plugins') as $plugin)
            if (isset($plugin['printChargeFooterCustLabels'])) {
                $labels[]=$plugin['printChargeFooterCustLabels']
            }
        */

        $receipt = "\n\n\n\n\n\n\n"
               .chr(27).chr(105)
               .chr(27).chr(33).chr(5)        // apbw 3/18/05 
               ."\n".$this->printHandler->centerString(CoreLocal::get("chargeSlip2"))."\n"
               .ReceiptLib::centerString("................................................")."\n"
               .ReceiptLib::centerString(CoreLocal::get("chargeSlip1"))."\n\n"
               . $labels["$program"][0]
               ._("Name: ").trim($chgName)."\n"        // changed by apbw 2/14/05 SCR
               ._("Member Number: ").trim(CoreLocal::get("memberID"))."\n"
               ._("Date: ").$date."\n"
               ._("REFERENCE #: ").$ref."\n"
               .$labels["$program"][1] . " $".number_format(-1 * CoreLocal::get("chargeTotal"), 2)."\n"
               . $labels["$program"][2]
               . $labels["$program"][3]
               ._("Purchaser Sign Below\n\n\n")
               ."X____________________________________________\n"
               .CoreLocal::get("fname")." ".CoreLocal::get("lname")."\n\n"
               .ReceiptLib::centerString(".................................................")."\n\n";

        return $receipt . $this->message(1, $ref, $reprint);
    }
}

