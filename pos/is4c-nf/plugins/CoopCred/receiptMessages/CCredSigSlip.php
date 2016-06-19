<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2014 West End Food Co-op, Toronto, Ontario

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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\ReceiptBuilding\Messages\ReceiptMessage;

/**
    @class CCredSigSlip
    Make a signature slip for each Coop Cred tender used in the transaction.
*/
class CCredSigSlip extends ReceiptMessage {

    public $paper_only = true;

    public function select_condition(){

        global $CORE_LOCAL;

        $tenderList = "('dummy')";
        if ($CORE_LOCAL->get('CCredTendersUsed') != '') {
            $tu = $CORE_LOCAL->get('CCredTendersUsed');
            $tenderList = implode("','", array_keys($tu));
            $tenderList = "('" . $tenderList ."')";
        }

        $departmentList = "(-999)";
        /* Not for payments.
        if ($CORE_LOCAL->get('CCredDepartmentsUsed') != '') {
            $du = $CORE_LOCAL->get('CCredDepartmentsUsed');
            $departmentList = implode(",", array_keys($du));
            $departmentList = "(" . $departmentList .")";
        }
         */

        return "SUM(CASE
                    WHEN trans_subtype IN {$tenderList} 
                    OR department IN {$departmentList} THEN 1
                    ELSE 0
                END)";
    }

    /**
      Generate the message
      @param $val the value returned by the object's select_condition()
      @param $ref a transaction reference (emp-lane-trans)
      @param $reprint boolean
      @return [string] message to print on receipt
    */
    public function message($val, $ref, $reprint=False){

        global $CORE_LOCAL;

        if ($val == 0) {
            return '';
        }

        $lineStart = "";
        $lineEnd = "\n";

        $conn = CoopCredLib::ccDataConnect();
        if ($conn === False) {
            return "Error: ccDataConnect() failed." . $lineEnd;
        }
        $chgName = COREPOS\pos\lib\MemberLib::getChgName();
        
        $dateTimeStamp = time();
        $date = ReceiptLib::build_time($dateTimeStamp);
        $itemSeparator = '';
        for ($i=1 ; $i <= 7 ; $i++) {
            $itemSeparator .= $lineEnd;
        }
        $cutPaper = chr(27).chr(105)
                    .chr(27).chr(33).chr(5);
        $projectName = _('Coop Cred');
        $projectUC = _('COOP CRED');
        $slip = '';
        $tu = $CORE_LOCAL->get('CCredTendersUsed');
        foreach ($tu as $tTender => $tProgramID) {

            $programCode = "CCred$tProgramID";
            $programName = $CORE_LOCAL->get("{$programCode}programName");
            $subs = CoopCredLib::getCCredSubtotals($tTender, $conn,
                $programCode, 'localtranstoday', $ref);
            if ($subs !== True) {
                return ("Error: Coop Cred Program " . $tProgramID . " subtotals.");
            }
            $labels["$programCode"] = array(
                    "$projectUC '{$programName}' ACCOUNT\n"
                    , "Tender:{$tTender} Debit Amount:"
                    , "I ACKNOWLEDGE THE ABOVE DEBIT\n"
                    , "TO MY $projectUC '{$programName}' ACCOUNT\n"
            );

            $slip .= $itemSeparator
                   . $cutPaper
                   ."\n".ReceiptLib::centerString("S T O R E   C O P Y")."\n"
                   .ReceiptLib::centerString("................................................")
                   ."\n\n"
                   . $labels["$programCode"][0]
                   ."Name: ".trim($chgName)."\n"
                   ."Member Number: ".trim($CORE_LOCAL->get("memberID"))."\n"
                   ."Date: ".$date."\n"
                   ."REFERENCE #: ".$ref."\n"
                   .$labels["$programCode"][1] . " $"
                       .number_format(-1 * $CORE_LOCAL->get("{$programCode}chargeTotal"), 2)
                       ."\n"
                   . $labels["$programCode"][2]
                   . $labels["$programCode"][3]
                   ."Member Sign Below\n\n\n"
                   ."X____________________________________________\n"
                   .$CORE_LOCAL->get("fname")." ".$CORE_LOCAL->get("lname")."\n\n"
                   .ReceiptLib::centerString(".................................................")
                   ."\n\n";

        // each tender
        }

        return $slip;

    }

    /**
      The same data can be rendered two different ways. One's a
      signature slip, the other is just a list of date, amount,
      approval code, etc
      N.B. This isn't finished for CoopCred, just a placeholder.
    */
    protected function variable_slip($ref, $reprint=False, $sigSlip=False){
        global $CORE_LOCAL;
        $date = ReceiptLib::build_time(time());
        list($emp,$reg,$trans) = explode('-', $ref);
        $sort = 'asc';

        $slip = '';
        $idclause = '';
        $db = Database::tDataConnect();
        if ($reprint)
            $db = Database::mDataConnect();
        if ($sigSlip && is_numeric($CORE_LOCAL->get('paycard_id'))) {
            $idclause = ' AND transID='.$CORE_LOCAL->get('paycard_id');
        }

        // query database for cc receipt info 
        $query = "SELECT  tranType, amount, PAN, entryMethod, issuer, "
            ." xResultMessage, xApprovalNumber, xTransactionID, name, datetime "
            ." FROM ccReceiptView where date=".date('Ymd',time())
            ." and cashierNo = ".$emp." and laneNo = ".$reg
            ." and transNo = ".$trans ." ".$idclause
            ." ORDER BY datetime $sort, transID DESC";

        if ($db->table_exists('PaycardTransactions')) {
            $trans_type = $db->concat('p.cardType', "' '", 'p.transType', '');

            $query = "SELECT $trans_type AS tranType,
                CASE WHEN p.transType = 'Return' THEN -1*p.amount
                    ELSE p.amount END as amount,
                p.PAN,
                CASE WHEN p.manual=1 THEN 'Manual'
                    ELSE 'Swiped' END as entryMethod,
                p.issuer,
                p.xResultMessage,
                p.xApprovalNumber,
                p.xTransactionID,
                p.name,
                p.requestDatetime AS datetime
              FROM PaycardTransactions AS p
              WHERE dateID=" . date('Ymd') . "
                AND empNo=" . $emp . "
                AND registerNo=" . $reg . "
                AND transNo=" . $trans . $idclause . "
                AND p.validResponse=1
                AND (p.xResultMessage LIKE '%APPROVE%' OR
                    p.xResultMessage LIKE '%PENDING%')
                AND p.cardType IN ('Credit', 'Debit')
              ORDER BY p.requestDatetime";
        }

        $result = $db->query($query);
        
        while($row = $db->fetchRow($result)){
            $trantype = $row['tranType'];  
            if ($row['amount'] < 0) {
                $amt = "-$".number_format(-1*$row['amount'],2);
            } else {
                $amt = "$".number_format($row['amount'],2);
            }
            $pan = $row['PAN']; // already masked in the database
            $entryMethod = $row['entryMethod'];
            $cardBrand = $row['issuer'];
            $approvalPhrase = $row['xResultMessage'];
            $authCode = "#".$row['xApprovalNumber'];
            $sequenceNum = $row['xTransactionID'];  
            $name = $row["name"];

            $slip .= ReceiptLib::centerString("................................................")."\n";
            if ($sigSlip){
                $slip .= ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip1"))
                     ."\n"        // store name 
                    .ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip3")
                        .", ".$CORE_LOCAL->get("chargeSlip4"))."\n"  // address
                    .ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip5"))."\n"        // merchant code 
                    .ReceiptLib::centerString($CORE_LOCAL->get("receiptHeader2"))."\n\n";    // phone
                // trans type:  purchase, canceled purchase, refund or canceled refund
                $slip .= $trantype."\n"
                    ."Card: ".$cardBrand."  ".$pan."\n"
                    ."Reference:  ".$ref."\n"
                    ."Date & Time:  ".$date."\n"
                    ."Entry Method:  ".$entryMethod."\n"          // swiped or manual entry
                    ."Sequence Number:  ".$sequenceNum."\n"        // their sequence #        
                    ."Authorization:  ".$approvalPhrase."\n"    // result + auth number
                    // total    
                    .ReceiptLib::boldFont()."Amount: ".$amt."\n"
                    .ReceiptLib::normalFont();
                $slip .= ReceiptLib::centerString("I agree to pay above total amount")."\n"
                    .ReceiptLib::centerString("according to the card issuer agreement.")."\n\n"
                
                    .ReceiptLib::centerString("X____________________________________________")."\n"
                    .ReceiptLib::centerString($name)."\n";
            }
            else {
                // use columns instead
                $c1 = array();
                $c2 = array();
                $c1[] = $trantype;
                $c1[] = "Entry Method:  ".$entryMethod;
                $c1[] = "Sequence Number:  ".$sequenceNum;
                $c2[] = $cardBrand."  ".$pan;
                $c2[] = "Authorization:  ".$approvalPhrase;
                $c2[] = ReceiptLib::boldFont()."Amount: ".$amt.ReceiptLib::normalFont();
                $slip .= ReceiptLib::twoColumns($c1,$c2);
            }
            $slip .= ReceiptLib::centerString(".................................................")."\n";

            if ($sigSlip){
                // Cut is added automatically by the printing process
                break;
            }
        }

        return $slip;
    }

    /**
      Message can be printed independently from a regular    
      receipt. Pass this string to ajax-end.php as URL
      parameter receiptType to print the standalone receipt.
    */
    // 15Feb2015 EL This doesn't look finished.
    //public $standalone_receipt_type = 'cCredSlip';
    public $standalone_receipt_type = 'ccSlip';

    /**
      Print message as its own receipt
      @param $ref a transaction reference (emp-lane-trans)
      @param $reprint boolean
      @return [string] message to print 
    */
    // 15Feb2015 EL This doesn't look finished.
    public function standalone_receipt($ref, $reprint=False){
        return "Coop Cred standalone receipt.\n";
        //return $this->variable_slip($ref, $reprint, True);
    }
}

