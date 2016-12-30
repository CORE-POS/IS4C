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
        list($emp, $reg, $trans) = ReceiptLib::parseRef($ref);
        $slip = '';

        // query database for receipt info 
        $dbc = Database::tDataConnect();
        if ($reprint) {
            $dbc = Database::mDataConnect();
        }

        $transType = $dbc->concat('p.cardType', "' '", 'p.transType', '');

        $query = "SELECT p.amount,
                    p.name,
                    p.PAN,
                    p.refNum,
                    $transType AS ebtMode,
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

        $result = $dbc->query($query);
        $prevRefNum = false;
        while ($row = $dbc->fetchRow($result)) {

            // failover to mercury's backup server can
            // result in duplicate refnums. this is
            // by design (theirs, not CORE's)
            if ($row['refNum'] == $prevRefNum) {
                continue;
            }

            $slip .= ReceiptLib::centerString("................................................")."\n";
            // store header
            for ($i=1; $i<= CoreLocal::get('chargeSlipCount'); $i++) {
                $slip .= ReceiptLib::centerString(CoreLocal::get("chargeSlip" . $i))."\n";
            }
            $slip .= "\n";
            $col1 = array();
            $col2 = array();
            $col1[] = $row['ebtMode'];
            $col2[] = "Entry Method: swiped\n";
            $col1[] = "Sequence: " . $row['xTransactionID'];
            $col2[] = "Card: ".$row['PAN'];
            $col1[] = "Authorization: " . $row['xResultMessage'];
            $col2[] = ReceiptLib::boldFont() . "Amount: " . $row['amount'] . ReceiptLib::normalFont();
            $balance = 'unknown';
            $ebtType = substr(strtoupper($row['ebtMode']), 0, 5);
            if ($ebtType == 'EBT F' || $ebtType == 'EBTFO') {
                if (is_numeric(CoreLocal::get('EbtFsBalance'))) {
                    $balance = sprintf('%.2f', CoreLocal::get('EbtFsBalance'));
                }
            } elseif ($ebtType == 'EBT C' || $ebtType == 'EBTCA') {
                if (is_numeric(CoreLocal::get('EbtCaBalance'))) {
                    $balance = sprintf('%.2f', CoreLocal::get('EbtCaBalance'));
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

