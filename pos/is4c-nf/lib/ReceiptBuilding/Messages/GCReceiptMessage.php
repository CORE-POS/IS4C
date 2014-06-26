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

/**
  @class GCReceiptMessage
*/
class GCReceiptMessage extends ReceiptMessage 
{

    public function select_condition()
    {
        return "SUM(CASE WHEN trans_subtype='GD' OR department=902 THEN 1 ELSE 0 END)";
    }

    public function message($val, $ref, $reprint=false)
    {
        return $this->varied_message($ref, $reprint, false);
    }

    protected function varied_message($ref, $reprint=false, $sigSlip=false)
    {
        global $CORE_LOCAL;
        if ($CORE_LOCAL->get('autoReprint') == 1) {
            $sigSlip = true;
        }
        $date = ReceiptLib::build_time(time());
        list($emp, $reg, $trans) = explode('-',$ref);
        $slip = '';

        // query database for gc receipt info 
        $db = Database::tDataConnect();
        if ($reprint) {
            $db = Database::mDataConnect();
        }

        $order = ($sigSlip) ? 'DESC' : 'ASC';
        $trans_type = $db->concat('p.cardType', "' '", 'p.transType', '');

        $sql = "SELECT $trans_type AS tranType,
                    CASE WHEN p.transType = 'Return' THEN -1*p.amount ELSE p.amount END as amount,
                    p.registerNo as terminalID,
                    p.PAN,
                    CASE WHEN p.manual=1 THEN 'Manual' ELSE 'Swiped' END as entryMethod,
                    CASE WHEN transType='VOID' THEN '' ELSE p.xApprovalNumber END AS xAuthorizationCode,
                    p.xBalance,
                    CASE WHEN transType='VOID' THEN p.xApprovalNumber ELSE '' END AS xVoidCode,
                    p.transID,
                    p.requestDatetime AS datetime
                FROM PaycardTransactions AS p
                WHERE dateID=" . date('Ymd') . "
                    AND empNo=" . $emp . "
                    AND registerNo=" . $reg . "
                    AND transNo=" . $trans . "
                    AND p.validResponse=1
                    AND p.xResultMessage LIKE '%Appro%'
                    AND p.cardType = 'Gift'
                ORDER BY p.requestDatetime " . $order;

        $result = $db->query($sql);
        $num = $db->num_rows($result);
        while($row = $db->fetch_row($result)){
            $slip .= ReceiptLib::centerString("................................................")."\n";
            // store header
            $slip .= ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip2"))."\n"  // "wedge copy"
                    . ReceiptLib::centerString("................................................")."\n"
                    . ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip1"))."\n"  // store name 
                    . ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip3").", ".$CORE_LOCAL->get("chargeSlip4"))."\n"  // address
                    . ReceiptLib::centerString($CORE_LOCAL->get("receiptHeader2"))."\n"  // phone
                    . "\n";
            $col1 = array();
            $col2 = array();
            $col1[] = $row['tranType'];
            $col2[] = "Date: ".date('m/d/y h:i a', strtotime($row['datetime']));
            $col1[] = "Terminal ID: ".$row['terminalID'];
            $col2[] = "Reference: ".$ref."-".$row['transID'];
            $col1[] = "Card: ".$row['PAN'];
            $col2[] = "Entry Method: ".$row['entryMethod'];
            if ( ((int)$row['xVoidCode']) > 0) {
                $col1[] = "Void Auth: ".$row['xVoidCode'];
                $col2[] = "Orig Auth: ".$row['xAuthorizationCode'];
            } else {
                $col1[] = "Authorization: ".$row['xAuthorizationCode'];
                $col2[] = "";
            }
            $col1[] = ReceiptLib::boldFont()."Amount: ".PaycardLib::paycard_moneyFormat($row['amount']).ReceiptLib::normalFont(); // bold ttls apbw 11/3/07
            $col2[] = "New Balance: ".PaycardLib::paycard_moneyFormat($row['xBalance']);
            $slip .= ReceiptLib::twoColumns($col1, $col2);

            // name/phone on activation only
            if (($row['tranType'] == 'Gift Card Activation' || $row['tranType'] == 'Gift Card Issue') && $sigSlip) {
                $slip .= "\n".ReceiptLib::centerString("Name:  ___________________________________")."\n"
                        ."\n".ReceiptLib::centerString("Phone: ___________________________________")."\n";
            }
            $slip .= ReceiptLib::centerString("................................................")."\n";
        }

        return $slip;
    }

    public $standalone_receipt_type = 'gcSlip';

    /**
      Print message as its own receipt
      @param $ref a transaction reference (emp-lane-trans)
      @param $reprint boolean
      @return [string] message to print 
    */
    public function standalone_receipt($ref, $reprint=false)
    {
        return $this->varied_message($ref, $reprint, true);
    }
}

