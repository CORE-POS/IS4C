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
  @class EbtReceiptMessage
*/
class EbtReceiptMessage extends ReceiptMessage 
{

    public function select_condition()
    {
        return "SUM(CASE WHEN trans_subtype IN ('EC', 'EF') THEN 1 ELSE 0 END)";
    }

    public function message($val, $ref, $reprint=false)
    {
        global $CORE_LOCAL;

        $date = ReceiptLib::build_time(time());
        list($emp, $reg, $trans) = explode('-',$ref);
        $slip = '';

        // query database for receipt info 
        $db = Database::tDataConnect();
        if ($reprint) {
            $db = Database::mDataConnect();
        }

        $query = "SELECT q.amount, q.name, q.PAN, q.refNum,
                    CASE 
                        WHEN q.mode = 'EBTFOOD_Sale' THEN 'Ebt FS Sale'
                        WHEN q.mode = 'EBTFOOD_Return' THEN 'Ebt FS Refund'
                        WHEN q.mode = 'EBTCASH_Sale' THEN 'Ebt Cash Sale'
                        WHEN q.mode = 'EBTCASH_Return' THEN 'Ebt Cash Refund'
                        ELSE q.mode
                    END as ebtMode,
                    r.xResultMessage, r.xTransactionID
                  FROM efsnetRequest AS q
                    LEFT JOIN efsnetResponse AS r ON
                        q.date = r.date AND
                        q.laneNo = r.laneNo AND
                        q.transNo = r.transNo AND
                        q.transID = r.transID AND
                        q.cashierNo = r.cashierNo
                  WHERE r.xResultMessage LIKE '%Approve%'
                        AND q.mode LIKE 'EBT%'
                        AND r.validResponse=1
                        AND q.date=" . date('Ymd') . "
                        AND q.transNo=" . ((int)$trans) . "
                  ORDER BY q.refNum, q.datetime";;

        if ($db->table_exists('PaycardTransactions')) {
            $trans_type = $db->concat('p.cardType', "' '", 'p.transType', '');

            $query = "SELECT p.amount,
                        p.name,
                        p.PAN,
                        p.refNum,
                        $trans_type AS ebtMode,
                        p.xResultMessage,
                        p.xTransactionID,
                        p.xBalance,
                        p.requestDatetime AS datetime
                      FROM PaycardTransactions AS p
                      WHERE dateID=" . date('Ymd') . "
                        AND empNo=" . $emp . "
                        AND registerNo=" . $reg . "
                        AND transNo=" . $trans . "
                        AND p.validResponse=1
                        AND p.xResultMessage LIKE '%APPROVE%'
                        AND p.cardType LIKE 'EBT%'
                      ORDER BY p.requestDatetime";
        }

        $result = $db->query($query);
        $prevRefNum = false;
        while ($row = $db->fetch_row($result)) {

            // failover to mercury's backup server can
            // result in duplicate refnums. this is
            // by design (theirs, not CORE's)
            if ($row['refNum'] == $prevRefNum) {
                continue;
            }

            $slip .= ReceiptLib::centerString("................................................")."\n";
            // store header
            $slip .= ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip2"))."\n"  // "wedge copy"
                    . ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip1"))."\n"  // store name 
                    . ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip3").", ".$CORE_LOCAL->get("chargeSlip4"))."\n"  // address
                    . ReceiptLib::centerString($CORE_LOCAL->get("receiptHeader2"))."\n"  // phone
                    . "\n";
            $col1 = array();
            $col2 = array();
            $col1[] = $row['ebtMode'];
            $col2[] = "Entry Method: swiped\n";
            $col1[] = "Sequence: " . $row['xTransactionID'];
            $col2[] = "Card: ".$row['PAN'];
            $col1[] = "Authorization: " . $row['xResultMessage'];
            $col2[] = ReceiptLib::boldFont() . "Amount: " . $row['amount'] . ReceiptLib::normalFont();
            $balance = 'unknown';
            $ebt_type = substr(strtoupper($row['ebtMode']), 0, 5);
            if ($ebt_type == 'EBT F' || $ebt_type == 'EBTFO') {
                if (is_numeric($CORE_LOCAL->get('EbtFsBalance'))) {
                    $balance = sprintf('%.2f', $CORE_LOCAL->get('EbtFsBalance'));
                }
            } else if ($ebt_type == 'EBT C' || $ebt_type == 'EBTCA') {
                if (is_numeric($CORE_LOCAL->get('EbtCaBalance'))) {
                    $balance = sprintf('%.2f', $CORE_LOCAL->get('EbtCaBalance'));
                }
            }
            $col1[] = "New Balance: " . $balance;
            $col2[] = '';
            $slip .= ReceiptLib::twoColumns($col1, $col2);

            $slip .= ReceiptLib::centerString("................................................")."\n";

            $prevRefNum = $row['refNum'];
        }

        return $slip;
    }
}

