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
		$query = "select  tranType, amount, PAN, entryMethod, issuer, xResultMessage, xApprovalNumber, xTransactionID, name, "
			." datetime from ccReceiptView where date=".date('Ymd',time())
			." and cashierNo = ".$emp." and laneNo = ".$reg
			." and transNo = ".$trans ." ".$idclause
			." order by datetime $sort, transID DESC";

        if ($db->table_exists('PaycardTransactions')) {
            $trans_type = $db->concat('p.cardType', "' '", 'p.transType', '');

            $query = "SELECT $trans_type AS tranType,
                        CASE WHEN p.transType = 'Return' THEN -1*p.amount ELSE p.amount END as amount,
                        p.PAN,
                        CASE WHEN p.manual=1 THEN 'Manual' ELSE 'Swiped' END as entryMethod,
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
                        AND (p.xResultMessage LIKE '%APPROVE%' OR p.xResultMessage LIKE '%PENDING%')
                        AND p.cardType IN ('Credit', 'Debit')
                      ORDER BY p.requestDatetime";
        }

		$result = $db->query($query);
		
		while($row = $db->fetch_array($result)){
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
				$slip .= ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip1"))."\n"		// store name 
					.ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip3").", ".$CORE_LOCAL->get("chargeSlip4"))."\n"  // address
					.ReceiptLib::centerString($CORE_LOCAL->get("chargeSlip5"))."\n"		// merchant code 
					.ReceiptLib::centerString($CORE_LOCAL->get("receiptHeader2"))."\n\n";	// phone
				$slip .= $trantype."\n"			// trans type:  purchase, canceled purchase, refund or canceled refund
					."Card: ".$cardBrand."  ".$pan."\n"
					."Reference:  ".$ref."\n"
					."Date & Time:  ".$date."\n"
					."Entry Method:  ".$entryMethod."\n"  		// swiped or manual entry
					."Sequence Number:  ".$sequenceNum."\n"	// their sequence #		
					//."Authorization:  ".$approvalPhrase." ".$authCode."\n"		// result + auth number
					."Authorization:  ".$approvalPhrase."\n"		// result + auth number
					.ReceiptLib::boldFont()  // change to bold font for the total
					."Amount: ".$amt."\n"		
					.ReceiptLib::normalFont();
				$slip .= ReceiptLib::centerString("I agree to pay above total amount")."\n"
					.ReceiptLib::centerString("according to card issuer agreement.")."\n\n"
				
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
				// Cut is added automatically by printing process
				//$slip .= "\n\n\n\n".chr(27).chr(105);
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

?>
