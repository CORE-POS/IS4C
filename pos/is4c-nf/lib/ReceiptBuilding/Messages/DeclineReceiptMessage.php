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
  @class DeclineReceiptMessage
*/
class DeclineReceiptMessage extends ReceiptMessage 
{

    public function select_condition()
    {
        return '0';
    }

    /**
      Print message as its own receipt
      @param $ref a transaction reference (emp-lane-trans)
      @param $reprint boolean
      @return [string] message to print 
    */
    public function standalone_receipt($ref, $reprint=false)
    {
        list($emp, $reg, $trans) = ReceiptLib::parseRef($ref);

        $dbc = Database::tDataConnect();

        $emvP = $dbc->prepare('
            SELECT content
            FROM EmvReceipt
            WHERE dateID=?
                AND empNo=?
                AND registerNo=?
                AND transNo=?
                AND transID=?
            ORDER BY tdate DESC
        ');
        $emvR = $dbc->execute($emvP, array(date('Ymd'), $emp, $reg, $trans, CoreLocal::get('paycard_id')));
        
        $slip = '';
        while ($emvW = $dbc->fetchRow($emvR)) {
            $slip .= ReceiptLib::centerString("................................................")."\n";
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
                            $i++;
                            continue;
                    }
                    $slip .= ReceiptLib::centerString($lines[$i]) . "\n";
                }
            }
            $slip .= "\n" . ReceiptLib::centerString(_('(Customer Copy)')) . "\n";

            break;
        }

        return $slip;
    }

    /**
      Message can be printed independently from a regular    
      receipt. Pass this string to AjaxEnd.php as URL
      parameter receiptType to print the standalone receipt.
    */
    public $standalone_receipt_type = 'ccDecline';

}

