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
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use \CoreLocal;

/**
  @class CCReceiptMessage
*/
class CCReceiptMessage extends ReceiptMessage {

    public function select_condition(){
        return "SUM(CASE WHEN trans_subtype IN ('CC','AX','DC') THEN 1 ELSE 0 END)";
    }

    /**
      Generate the message
      @param $val the value returned by the object's select_condition()
      @param $ref a transaction reference (emp-lane-trans)
      @param $reprint boolean
      @return [string] message to print on receipt
    */
    public function message($val, $ref, $reprint=False){
        if ($val == 0) return '';
        else return $this->variable_slip($ref, $reprint, False);
    }

    /**
      The same data can be rendered two different ways. One's a
      signature slip, the other is just a list of date, amount,
      approval code, etc
    */
    protected function variable_slip($ref, $reprint=False, $sigSlip=False)
    {
        $date = ReceiptLib::build_time(time());
        list($emp, $reg, $trans) = ReceiptLib::parseRef($ref);
        $sort = 'asc';

        $slip = '';
        $idclause = '';
        $dbc = Database::tDataConnect();
        if ($reprint)
            $dbc = Database::mDataConnect();
        if ($sigSlip && is_numeric(CoreLocal::get('paycard_id'))) {
            $idclause = ' AND transID='.CoreLocal::get('paycard_id');
        }

        $trans_type = $dbc->concat('p.cardType', "' '", 'p.transType', '');

        $query = "SELECT $trans_type AS tranType,
                    CASE WHEN p.transType = 'Return' THEN -1*p.amount ELSE p.amount END as amount,
                    p.PAN,
                    CASE WHEN p.manual=1 THEN 'Manual' ELSE 'Swiped' END as entryMethod,
                    p.issuer,
                    p.xResultMessage,
                    p.xApprovalNumber,
                    p.xTransactionID,
                    p.name,
                    p.requestDatetime AS datetime,
                    p.transID
                  FROM PaycardTransactions AS p
                  WHERE dateID=" . date('Ymd') . "
                    AND empNo=" . $emp . "
                    AND registerNo=" . $reg . "
                    AND transNo=" . $trans . $idclause . "
                    AND p.validResponse=1
                    AND (p.xResultMessage LIKE '%APPROVE%' OR p.xResultMessage LIKE '%PENDING%')
                    AND p.cardType IN ('Credit', 'Debit', 'EMV', 'R.Credit', 'R.EMV')
                  ORDER BY p.requestDatetime";

        $result = $dbc->query($query);

        $emvP = $dbc->prepare('
            SELECT content
            FROM EmvReceipt
            WHERE dateID=?
                AND empNo=?
                AND registerNo=?
                AND transNo=?
                AND transID=?
        ');
        $recurring = 20;
        $payments_left = 4;
        $r_phone = '218-728-0884';
        $r_email = 'billing@wholefoods.coop';
        
        while ($row = $dbc->fetchRow($result)) {
            $slip .= ReceiptLib::centerString("................................................")."\n";
            // do not look for EmvReceipt server side; use classic receipt
            $emvR = $reprint ? false : $dbc->execute($emvP, array(date('Ymd'), $emp, $reg, $trans, $row['transID']));
            if ($emvR && $dbc->numRows($emvR)) {
                $emvW = $dbc->fetchRow($emvR);
                $lines = explode("\n", $emvW['content']);
                for ($i=0; $i<count($lines); $i++) {
                    if (isset($lines[$i+1]) && (strlen($lines[$i]) + strlen($lines[$i+1])) < 56) {
                        // don't columnize the amount lines
                        if (strstr($lines[$i], 'AMOUNT') || strstr($lines[$i+1], 'AMOUNT')) {
                            $slip .= ReceiptLib::centerString($lines[$i]) . "\n";
                        } elseif (strstr($lines[$i], 'TOTAL') || strstr($lines[$i+1], 'TOTAL')) {
                            $slip .= ReceiptLib::centerString($lines[$i]) . "\n";
                        }  else {
                            $spacer = 56 - strlen($lines[$i]) - strlen($lines[$i+1]);
                            $slip .= $lines[$i] . str_repeat(' ', $spacer) . $lines[$i+1] . "\n";
                            $i++;
                        }
                    } else {
                        if (strstr($lines[$i], 'x___')) {
                            if ($sigSlip) {
                                $slip .= "\n\n\n";
                            } else {
                                $i++;
                                continue;
                            }
                        }
                        $slip .= ReceiptLib::centerString($lines[$i]) . "\n";
                    }
                }
                if ($sigSlip) {
                    $slip .= "\n" . ReceiptLib::centerString($emp . '-' . $reg . '-' . $trans) . "\n";
                    $slip .= ReceiptLib::centerString(_('(Merchant Copy)')) . "\n";
                } else {
                    $slip .= "\n" . ReceiptLib::centerString(_('(Customer Copy)')) . "\n";
                }
            } else {
                if ($row['amount'] < 0) {
                    $amt = "-$".number_format(-1*$row['amount'],2);
                } else {
                    $amt = "$".number_format($row['amount'],2);
                }

                if ($sigSlip) {
                    for ($i=1; $i<= CoreLocal::get('chargeSlipCount'); $i++) {
                        $slip .= ReceiptLib::centerString(CoreLocal::get("chargeSlip" . $i))."\n";
                    }
                    if (strpos($row['tranType'], ' R.')) {
                        $para1 = 'Whole Foods Co-op (WFC) will charge four (4) additional $20 payments to your card. Payments will occur monthly starting one month from today. Each payment will purchase four (4) shares of class B equity in WFC. Entries on your bank statement may be labeled recurring.';
                        $para2 = 'To cancel this arrangement at any point, contact WFC by phone at 218-728-0884 or by email at equity@wholefoods.coop.';
                        $para3 = 'If a monthly payment fails or is declined, no future monthly charges will be made. You will retain ownership of all equity purchased up to that point and may pay the remaining balance any time before the due date, one year from today.';
                        $slip .= wordwrap($para1, 55) . "\n\n";
                        $slip .= wordwrap($para2, 55) . "\n\n";
                        $slip .= wordwrap($para3, 55) . "\n\n";
                    }
                    $slip .= $row['tranType']."\n" // trans type:  purchase, canceled purchase, refund or canceled refund
                        ."Card: ".$row['issuer']."  ".$row['PAN']."\n"
                        ."Reference:  ".$ref."\n"
                        ."Date & Time:  ".$date."\n"
                        ."Entry Method:  ".$row['entryMethod']."\n" // swiped or manual entry
                        ."Sequence Number:  ".$row['xTransactionID']."\n" 
                        ."Authorization:  ".$row['xResultMessage']."\n" 
                        .ReceiptLib::boldFont()  // change to bold font for the total
                        ."Amount: ".$amt."\n"        
                        .ReceiptLib::normalFont();
                    $slip .= ReceiptLib::centerString("I agree to pay above total amount")."\n"
                        .ReceiptLib::centerString("according to card issuer agreement.")."\n\n"
                    
                        .ReceiptLib::centerString("X____________________________________________")."\n"
                        .ReceiptLib::centerString($row['name'])."\n";
                } else {
                    // use columns instead
                    $col1 = array();
                    $col2 = array();
                    $col1[] = $row['tranType'];
                    $col1[] = "Entry Method:  ".$row['entryMethod'];
                    $col1[] = "Sequence Number:  ".$row['xTransactionID'];
                    $col2[] = $row['issuer']."  ".$row['PAN'];
                    $col2[] = "Authorization:  ".$row['xResultMessage'];
                    $col2[] = ReceiptLib::boldFont()."Amount: ".$amt.ReceiptLib::normalFont();
                    $slip .= ReceiptLib::twoColumns($col1,$col2);
                    if (strpos($row['tranType'], ' R.')) {
                        $slip .= ReceiptLib::boldFont() . 'This is a recurring payment' . ReceiptLib::normalizeFont() . "\n"
                            . wordwrap(
                                sprintf('You will be billed monthly %d additional times for $%.2f. ', $payments_left, $recurring)
                                . 'The charges on your bank statement will be labeled "recurring". '
                                . sprintf('Call %s or email %s to cancel. ', $r_phone, $r_email)
                                . 'Please do not include your credit card number in an email. ',
                                55) . "\n";
                    }
                }
            }
            $slip .= ReceiptLib::centerString(".................................................")."\n";
        }

        return $slip;
    }

    /**
      Message can be printed independently from a regular    
      receipt. Pass this string to AjaxEnd.php as URL
      parameter receiptType to print the standalone receipt.
    */
    public $standalone_receipt_type = 'ccSlip';

    /**
      Print message as its own receipt
      @param $ref a transaction reference (emp-lane-trans)
      @param $reprint boolean
      @return [string] message to print 
    */
    public function standalone_receipt($ref, $reprint=False){
        return $this->variable_slip($ref, $reprint, True);
    }
}

