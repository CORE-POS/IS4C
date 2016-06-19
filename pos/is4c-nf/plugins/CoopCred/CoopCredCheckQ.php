<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto, Canada

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

use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\parser\Parser;

/** List the Coop Cred Programs the member belongs to
 *   with stutus and balance for each.
 */
class CoopCredCheckQ extends Parser {

    function check($str){
        if ($str == "QQ")
            return True;
        return False;
    }

    function parse($str){
        global $CORE_LOCAL;
        $ret = $this->default_json();
        $pStyle = "<p style='font-weight:bold; text-align:center; margin: 0em 0em 0em -1.0em;'>";
        if (!in_array('CoopCred', $CORE_LOCAL->get('PluginList'))) {
            $message = "{$pStyle}" .
            _("Coop Cred is not currently in use at this co-op.") .
            "</p>";
            $ret['output'] = DisplayLib::boxMsg("$message","",True);
            return $ret;
        }

        $conn = CoopCredLib::ccDataConnect();
        if ($conn === False) {
            return "Error: ccDataConnect() failed.";
        }

        $ccQ = "SELECT p.programID AS ppID, p.programName, p.tenderType,
            p.active, p.startDate, p.endDate,
            p.bankID, p.creditOK, p.inputOK, p.transferOK,
            m.creditBalance, m.creditLimit, m.creditOK, m.inputOK,
                m.transferOK, m.isBank
                ,(m.creditLimit - m.creditBalance) as availCreditBalance
                ,c.FirstNAme, c.LastName
            FROM CCredMemberships m
            JOIN CCredPrograms p
                ON p.programID = m.programID
            JOIN {$CORE_LOCAL->get('pDatabase')}.custdata c
                ON m.cardNo = c.CardNo
            WHERE m.cardNo =" . $CORE_LOCAL->get("memberID") .
            " AND c.personNum=1 " .
            "ORDER BY ppID";
        $ccS = $conn->prepare("$ccQ");
        if ($ccS === False) {
            $ret['output'] = DisplayLib::boxMsg("<p>Prep Failed: {$ccQ}</p>","",True);
            return $ret;
        }
        $args = array();
        $ccR = $conn->execute($ccS, $args);
        if ($ccR === False) {
            $ret['output'] = DisplayLib::boxMsg("<p>Query Failed: {$ccQ}</p>","",True);
            return $ret;
        }
        if ($conn->num_rows($ccR) == 0) {
            $message = "{$pStyle}Member " . $CORE_LOCAL->get("memberID") .
                _(" is not registered for any Coop Cred Programs.") .
                "</p>";
            $ret['output'] = DisplayLib::boxMsg("$message","",True);
            return $ret;
        }

        $pStyle2 = "style='" .
            "font-weight:bold; " .
            "text-align:center; " .
            "margin: 0em 0em 0em -1.0em; " .
            "'";
        $message = "<p {$pStyle2}>";
        $message .= 
            _("Member")." #". $CORE_LOCAL->get("memberID");
        $message .= "<br />" . _("Can spend these amounts of Coop Cred:");
        $message .= "</p>";

        $bStyle3 = "style='" .
            "margin: 0.25em 0em 0em -3.0em; " .
            "'";
        $message .= "<div {$bStyle3}>";
        $tableStyle1 = "style='" .
            "width: 16em; " .
            "'";
        $rowStyle = "style='vertical-align:top;' ";
        $keyStyle = "style='font-size:0.8em;' ";
        $nameStyle = "style='font-size:0.8em;' ";
        $narrowAmountStyle = "style='text-align:right; font-size:1.2em;' ";
        $wideAmountStyle = "style='text-align:right; font-size:1.1em;' ";
        $notOkStyle = "style='font-size:0.8em;' ";
        $noteStyle = "style='font-size:0.7em;' ";
        $message .= "<table {$tableStyle1} cellpadding=2 cellspacing=0 border=0>";
        while ($row = $conn->fetchRow($ccR)) {
            $programOK = CoopCredLib::programOK($row['tenderType'], $conn);
            if ($programOK === True) {
                $programCode = 'CCred' . $CORE_LOCAL->get("CCredProgramID");
                $subtotals = CoopCredLib::getCCredSubtotals($row['tenderType'], $conn);
                $tenderKeyCap = ($CORE_LOCAL->get("{$programCode}tenderKeyCap") != "")
                    ?  $CORE_LOCAL->get("{$programCode}tenderKeyCap")
                    : 'CCr' . $CORE_LOCAL->get("CCredProgramID");
                $programBalance =
                    ($CORE_LOCAL->get("{$programCode}availBal")) ?
                    $CORE_LOCAL->get("{$programCode}availBal") :
                    $CORE_LOCAL->get("{$programCode}availCreditBalance");
                $programBalanceString = sprintf("\$%s",
                    number_format($programBalance,2));
                $amountStyle = strlen($programBalanceString)<=9 ?
                    $narrowAmountStyle : $wideAmountStyle;

                $message .= 
                sprintf("%s%s%s%s%s%s%s",
                    "<tr {$rowStyle}><td {$keyStyle}>",
                    "[{$tenderKeyCap}]",
                    "</td><td {$nameStyle}>",
                    $CORE_LOCAL->get("{$programCode}programName"),
                    "</td><td {$amountStyle}>",
                    $programBalanceString,
                    "</td></tr>"
                );
            } else {
                $message .= "<tr><td colspan=99 {$notOkStyle}>" . $programOK . "</td></tr>";
            }
        }

        $note = _("The amounts reflect any tenders of, or inputs to, " .
                   "Coop Cred in the current transaction.");
        $message .= "<tr><td colspan=99 {$noteStyle}>" . $note . "</td></tr>";

        /* Want: self, mode=Print
         */
        $printButton = "<button type='submit' value='" .
            $CORE_LOCAL->get('memberID') . 
            "' onClick='' " .
            " title='Not implemented yet' " .
            ">" .
            "Print" .
            "</button>";
        $message .= "<tr><td colspan=99 >" . $printButton . "</td></tr>";

        $message .= "</table>";
        $message .= "</div>";
        $ret['output'] = DisplayLib::boxMsg("$message","",True);
        return $ret;
    }


    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>QQ</td>
                <td>Display All Coop Cred
                Program statuses
                and
                balances
                for currently entered member</td>
            </tr>
            </table>";
    }

}

