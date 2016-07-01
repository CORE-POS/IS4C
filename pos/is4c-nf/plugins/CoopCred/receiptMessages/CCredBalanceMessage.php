<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto

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

use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\ReceiptBuilding\CustMessages\CustomerReceiptMessage;

/**
  @class CCredBalanceMessage
  Return a message containing Available Balance
  after the just-completed transaction
  for each Program the member belongs to.
  Needs records in table custReceiptMessage.
*/
/* 9Oct2014 This works but I don't think I'll use it.
 * Needs an opdata.custReceiptMessage record for each program for each member,
 *  naming this mod.
 */

class CCredBalanceMessage extends CustomerReceiptMessage {

    /**
      Return a message containing Available Balance
      after the just-completed transaction
      for each Program the member belongs to.
      @param $str [string] from the custReceiptMessage table record.
        format: "AAA BBB \d" where AAA will sort to the right sequence
                and \d is the CCredPrograms.programID
        I don't remember why three fields. AAA \d may be enough.
      @return [string] message about Available Balance for each Program
      the member belongs to.
    */
    public function message($str){
        global $CORE_LOCAL;

        $lineStart = "";
        $lineEnd = "\n";

        $msg = explode(' ',$str);
        // 9Oct2014 Why [2]? Wouldn't [1] be enough?
        if (!isset($msg[2]) || !preg_match('/^\d+$/', $msg[2])) {
            return "Error: No program id in $str{$lineEnd}";
        }
        $programID = $msg[2];

        if (!in_array('CoopCred', $CORE_LOCAL->get('PluginList'))) {
            $ret = "{$lineStart}" .
            _("Coop Cred is not currently in use at this co-op.") .
            "{$lineEnd}";
            return $ret;
        }

        $conn = CoopCredLib::ccDataConnect();
        if ($conn === False) {
            return "Error: ccDataConnect() failed." . $lineEnd;
        }

        $ccQ = "SELECT p.programID, p.programName, p.tenderType,
            p.active, p.startDate, p.endDate,
            p.bankID, p.creditOK, p.inputOK, p.transferOK,
            m.creditBalance, m.creditLimit, m.creditOK, m.inputOK,
                m.transferOK, m.isBank
                ,(m.creditLimit - m.creditBalance) as availCreditBalance
                ,c.FirstNAme, c.LastName
            FROM CCredMemberships m
            JOIN CCredPrograms p ON p.programID = m.programID
            JOIN {$CORE_LOCAL->get('pDatabase')}.custdata c
                ON m.cardNo = c.CardNo
                WHERE m.cardNo =" . $CORE_LOCAL->get("memberID") .
                " AND m.programID={$programID}
                 AND c.personNum=1";
        $ccS = $conn->prepare("$ccQ");
        if ($ccS === False) {
            return "Statement prep for Program {$programID} failed.{$lineEnd}";
        }
        $args = array();
        $ccR = $conn->execute($ccS, $args);
        if ($ccR === False) {
            return "Query for Program {$programID} failed.{$lineEnd}";
        }
        if ($conn->num_rows($ccR) == 0) {
            $ret = "Member " . $CORE_LOCAL->get("memberID") .
                _(" is not registered for Coop Cred Program {$programID}.") .
                "$lineEnd";
            return $ret;
        }

        /* For each Coop Cred Program the member is in.
         */
        while ($row = $conn->fetchRow($ccR)) {
            $programOK = CoopCredLib::programOK($row['tenderType'], $conn);
            if ($programOK === True) {
                $subs = CoopCredLib::getCCredSubtotals($row['tenderType'], $conn);
                if ($subs !== True) {
                    return ("Error: Coop Cred Program " . $programID . " subtotals.");
                }
                $projectName = 'Coop Cred';
                $pcode = 'CCred' . $programID;
                $pname = $CORE_LOCAL->get("{$pcode}ProgramName");
                $acb = ($CORE_LOCAL->get("{$pcode}availBal") != '') ?
                        $CORE_LOCAL->get("{$pcode}availBal") :
                        _("Balance not available.");
                $ret = sprintf("%s: %s %s: \$ %s%s",
                    $projectName,
                    $pname,
                    _("Available"),
                    $acb,
                    $lineEnd
                );

            } else {
                $ret = $programOK . $lineEnd;
            }
        }

        return $ret;

    }

}

