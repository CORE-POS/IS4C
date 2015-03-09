<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

if (!class_exists("BasicCCModule")) include_once(realpath(dirname(__FILE__)."/BasicCCModule.php"));
if (!class_exists("xmlData")) include_once(realpath(dirname(__FILE__)."/lib/xmlData.php"));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

define('FD_STORE_ID','');
define('FD_PASSWD','');
define('FD_CERT_PATH',realpath(dirname(__FILE__).'/lib').'/fd');
define('FD_CERT_PASSWD','');
define('FD_KEY_PASSWD','');

/* test credentials  */
/*

*/

class FirstData extends BasicCCModule {

	function handlesType($type){
		if ($type == PaycardLib::PAYCARD_TYPE_CREDIT) return True;
		else return False;
	}

	function handleResponse($authResult)
    {
		switch(CoreLocal::get("paycard_mode")){
		case PaycardLib::PAYCARD_MODE_AUTH:
			return $this->handleResponseAuth($authResult);
		case PaycardLib::PAYCARD_MODE_VOID:
			return $this->handleResponseVoid($authResult);
		}
	}

	function entered($validate,$json)
    {
		// error checks based on card type
		if( CoreLocal::get("CCintegrate") != 1) { // credit card integration must be enabled
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
				"Card Integration Disabled",
				"Please process credit cards in standalone",
				"[clear] to cancel");
			return $json;
		}
		$this->trans_pan['pan'] = CoreLocal::get("paycard_PAN");

		// error checks based on processing mode
		switch( CoreLocal::get("paycard_mode")) {
		case PaycardLib::PAYCARD_MODE_VOID:
			// use the card number to find the trans_id
			$dbTrans = PaycardLib::paycard_db();
			$today = date('Ymd');
			$pan4 = substr($this->trans_pan['pan'],-4);
			$cashier = CoreLocal::get("CashierNo");
			$lane = CoreLocal::get("laneno");
			$trans = CoreLocal::get("transno");
			$sql = "SELECT transID,cashierNo,laneNo,transNo FROM efsnetRequest WHERE "
				.$dbTrans->identifier_escape('date')."='".$today."' AND (PAN LIKE '%".$pan4."')"; 
			$search = PaycardLib::paycard_db_query($sql, $dbTrans);
			$num = PaycardLib::paycard_db_num_rows($search);
			if( $num < 1) {
				PaycardLib::paycard_reset();
				$json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Card Not Used",
					"That card number was not used in this transaction","[clear] to cancel");
				return $json;
			} else if( $num > 1) {
				PaycardLib::paycard_reset();
				$json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Multiple Uses",
					"That card number was used more than once in this transaction; select the payment and press VOID","[clear] to cancel");
				return $json;
			}
			$payment = PaycardLib::paycard_db_fetch_row($search);
			return $this->paycard_void($payment['transID'],$lane,$trans,$json);
			break;

		case PaycardLib::PAYCARD_MODE_AUTH:
			if( $validate) {
				if( PaycardLib::paycard_validNumber($this->trans_pan['pan']) != 1) {
					PaycardLib::paycard_reset();
					$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
						"Invalid Card Number",
						"Swipe again or type in manually",
						"[clear] to cancel");
					return $json;
				} else if( !PaycardLib::paycard_accepted($this->trans_pan['pan'])) {
					PaycardLib::paycard_reset();
					$json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,
						"Unsupported Card Type",
						"We cannot process " . CoreLocal::get("paycard_issuer") . " cards",
						"[clear] to cancel");
					return $json;
				} else if( PaycardLib::paycard_validExpiration(CoreLocal::get("paycard_exp")) != 1) {
					PaycardLib::paycard_reset();
					$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
						"Invalid Expiration Date",
						"The expiration date has passed or was not recognized",
						"[clear] to cancel");
					return $json;
				}
			}
			// set initial variables
			//Database::getsubtotals();
			if (CoreLocal::get("paycard_amount") == 0)
				CoreLocal::set("paycard_amount",CoreLocal::get("amtdue"));
			CoreLocal::set("paycard_id",CoreLocal::get("LastID")+1); // kind of a hack to anticipate it this way..
			$plugin_info = new Paycards();
			$json['main_frame'] = $plugin_info->plugin_url().'/gui/paycardboxMsgAuth.php';
			$json['output'] = '';
			return $json;
			break;
		} // switch mode
	
		// if we're still here, it's an error
		PaycardLib::paycard_reset();
		$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Invalid Mode",
			"This card type does not support that processing mode","[clear] to cancel");
		return $json;

	}

	function paycard_void($transID,$laneNo=-1,$transNo=-1,$json=array()) 
    {
		$this->voidTrans = "";
		$this->voidRef = "";
		// situation checking
		if( CoreLocal::get("CCintegrate") != 1) { // credit card integration must be enabled
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,
				"Card Integration Disabled",
				"Please process credit cards in standalone",
				"[clear] to cancel");
			return $json;
		}
	
		// initialize
		$dbTrans = PaycardLib::paycard_db();
		$today = date('Ymd');
		$cashier = CoreLocal::get("CashierNo");
		$lane = CoreLocal::get("laneno");
		$trans = CoreLocal::get("transno");
		if ($laneNo != -1) $lane = $laneNo;
		if ($transNo != -1) $trans = $transNo;
	
		// look up the request using transID (within this transaction)
		$sql = "SELECT live,PAN,mode,amount,name FROM efsnetRequest 
			WHERE ".$dbTrans->identifier_escape('date')."='".$today."' AND cashierNo=".$cashier." AND 
			laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
		$search = PaycardLib::paycard_db_query($sql, $dbTrans);
		$num = PaycardLib::paycard_db_num_rows($search);
		if( $num < 1) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
				"Card request not found, unable to void","[clear] to cancel");
			return $json;
		} else if( $num > 1) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
				"Card request not distinct, unable to void","[clear] to cancel");
			return $json;
		}
		$request = PaycardLib::paycard_db_fetch_row($search);

		// look up the response
		$sql = "SELECT commErr,httpCode,validResponse,xResponseCode,
			xTransactionID FROM efsnetResponse WHERE ".$dbTrans->identifier_escape('date')."='".$today."' 
			AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
		$search = PaycardLib::paycard_db_query($sql, $dbTrans);
		$num = PaycardLib::paycard_db_num_rows($search);
		if( $num < 1) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
				"Card response not found, unable to void","[clear] to cancel");
			return $json;
		} else if( $num > 1) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
				"Card response not distinct, unable to void","[clear] to cancel");
			return $json;
		}
		$response = PaycardLib::paycard_db_fetch_row($search);

		// look up any previous successful voids
		$sql = "SELECT transID FROM efsnetRequestMod WHERE "
				.$dbTrans->identifier_escape('date')."=".$today
				." AND cashierNo=".$cashier." AND laneNo=".$lane
				." AND transNo=".$trans." AND transID=".$transID
				." AND mode='void' AND xResponseCode=0";
		$search = PaycardLib::paycard_db_query($sql, $dbTrans);
		$voided = PaycardLib::paycard_db_num_rows($search);
		if( $voided > 0) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Unable to Void",
				"Card transaction already voided","[clear] to cancel");
			return $json;
		}

		// look up the transaction tender line-item
		$sql = "SELECT trans_type,trans_subtype,trans_status,voided
		       	FROM localtemptrans WHERE trans_id=" . $transID;
		$search = PaycardLib::paycard_db_query($sql, $dbTrans);
		$num = PaycardLib::paycard_db_num_rows($search);
		if( $num < 1) {
			$sql = "SELECT * FROM localtranstoday WHERE trans_id=".$transID." and emp_no=".$cashier
				." and register_no=".$lane." and trans_no=".$trans
                ." AND datetime >= " . $dbTrans->curdate();
			$search = PaycardLib::paycard_db_query($sql, $dbTrans);
			$num = PaycardLib::paycard_db_num_rows($search);
			if ($num != 1){
				PaycardLib::paycard_reset();
				$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
					"Transaction item not found, unable to void","[clear] to cancel");
				return $json;
			}
		} else if( $num > 1) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
				"Transaction item not distinct, unable to void","[clear] to cancel");
			return $json;
		}
		$lineitem = PaycardLib::paycard_db_fetch_row($search);

		// make sure the payment is applicable to void
		if( $response['commErr'] != 0 || $response['httpCode'] != 200 || $response['validResponse'] != 1) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Unable to Void",
				"Card transaction not successful","[clear] to cancel");
			return $json;
		} else if( $request['live'] != PaycardLib::paycard_live(PaycardLib::PAYCARD_TYPE_CREDIT)) {
			// this means the transaction was submitted to the test platform, but we now think we're in live mode, or vice-versa
			// I can't imagine how this could happen (short of serious $_SESSION corruption), but worth a check anyway.. --atf 7/26/07
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Unable to Void",
				"Processor platform mismatch","[clear] to cancel");
			return $json;
		} else if( $response['xResponseCode'] != 1) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Unable to Void",
				"Credit card transaction not approved<br>The result code was " . $response['xResponseCode'],"[clear] to cancel");
			return $json;
		} else if( $response['xTransactionID'] < 1) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
				"Invalid reference number","[clear] to cancel");
			return $json;
		}

		// make sure the tender line-item is applicable to void
		if( $lineitem['trans_type'] != "T" || $lineitem['trans_subtype'] != "CC" ){
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
				"Authorization and tender records do not match $transID","[clear] to cancel");
			return $json;
		} else if( $lineitem['trans_status'] == "V" || $lineitem['voided'] != 0) {
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Internal Error",
				"Void records do not match","[clear] to cancel");
			return $json;
		}
	
		// save the details
		CoreLocal::set("paycard_amount",(($request['mode']=='retail_alone_credit') ? -1 : 1) * $request['amount']);
		CoreLocal::set("paycard_id",$transID);
		CoreLocal::set("paycard_trans",$cashier."-".$lane."-".$trans);
		CoreLocal::set("paycard_type",PaycardLib::PAYCARD_TYPE_CREDIT);
		CoreLocal::set("paycard_mode",PaycardLib::PAYCARD_MODE_VOID);
		CoreLocal::set("paycard_name",$request['name']);
	
		// display FEC code box
		$plugin_info = new Paycards();
		$json['main_frame'] = $plugin_info->plugin_url().'/gui/paycardboxMsgVoid.php';
		return $json;
	}

	function handleResponseAuth($authResult)
    {
		$inner_xml = $this->desoapify("SOAP-ENV:Body",$authResult['response']);
		$xml = new xmlData($inner_xml);
		$dbTrans = PaycardLib::paycard_db();

		// prepare some fields to store the parsed response; we'll add more as we verify it
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = CoreLocal::get("CashierNo");
		$laneNo = CoreLocal::get("laneno");
		$transNo = CoreLocal::get("transno");
		$transID = CoreLocal::get("paycard_id");
		$cvv2 = CoreLocal::get("paycard_cvv2");

		$sqlColumns =
			"date,cashierNo,laneNo,transNo,transID," .
			"datetime," .
			"seconds,commErr,httpCode";
		$sqlValues =
			sprintf("%d,%d,%d,%d,%d,",  $today, $cashierNo, $laneNo, $transNo, $transID) .
			sprintf("'%s',",            $now ) .
			sprintf("%f,%d,%d",         $authResult['curlTime'], $authResult['curlErr'], $authResult['curlHTTP']);
		$validResponse = ($xml->isValid()) ? 1 : 0;

		$refNum = $xml->get("fdggwsapi:OrderId");
		if ($refNum){
			$sqlColumns .= ",refNum";
			$sqlValues .= sprintf(",%s",$dbTrans->escape($refNum));
		}
		$statusMsg = $xml->get("fdggwsapi:TransactionResult");
		$responseCode = 4;
		switch(strtoupper($statusMsg)){
		case 'APPROVED':
			$responseCode=1; break;
		case 'DECLINED':
		case 'FRAUD':
			$responseCode=2; break;
		case 'FAILED':
		case 'DUPLICATE':
			$responseCode=0; break;
		}
		if ($responseCode){
			$sqlColumns .= ",xResponseCode";
			$sqlValues .= sprintf(",%d",$responseCode);
		}
		// aren't two separate codes from goemerchant
		$resultCode = $responseCode;
		if ($resultCode){
			$sqlColumns .= ",xResultCode";
			$sqlValues .= sprintf(",%d",$resultCode);
		}
		$resultMsg = $statusMsg; // already gathered above
		if ($resultMsg){
			$sqlColumns .= ",xResultMessage";
			$rMsg = $resultMsg;
			if (strlen($rMsg) > 100){
				$rMsg = substr($rMsg,0,100);
			}
			$sqlValues .= sprintf(",%s",$dbTrans->escape($rMsg));
		}
		$xTransID = $xml->get("fdggwsapi:ProcessorReferenceNumber");
		if ($xTransID){
			$sqlColumns .= ",xTransactionID";
			$sqlValues .= sprintf(",%s",$dbTrans->escape($xTransID));
		}
		$apprNumber = $xml->get("fdggwsapi:ApprovalCode");
		if ($apprNumber){
			$sqlColumns .= ",xApprovalNumber";
			$sqlValues .= sprintf(",%s",$dbTrans->escape($apprNumber));
		}
		// valid credit transactions don't have an approval number
		$sqlColumns .= ",validResponse";
		$sqlValues .= sprintf(",%d",0);

        $table_def = $dbTrans->table_definition('efsnetResponse');
        if (isset($table_def['efsnetRequestID'])) {
            $sqlColumns .= ', efsnetRequestID';
            $sqlValues .= sprintf(', %d', $this->last_req_id);
        }

		$sql = "INSERT INTO efsnetResponse (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
		PaycardLib::paycard_db_query($sql, $dbTrans);

		if( $authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
			TransRecord::addcomment("");	
			if ($authResult['curlHTTP'] == '0'){
				CoreLocal::set("boxMsg","No response from processor<br />
							The transaction did not go through");
				return PaycardLib::PAYCARD_ERR_PROC;
			}	
			return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
		}

		switch ($responseCode){
			case 1: // APPROVED
				return PaycardLib::PAYCARD_ERR_OK;
			case 2: // DECLINED
				CoreLocal::set("boxMsg",'Card Declined');
				break;
			case 0: // ERROR
				$texts = $xml->get_first("fdggwsapi:ProcessorResponseMessage");
				CoreLocal::set("boxMsg","Error: $texts");
				break;
			default:
				CoreLocal::set("boxMsg","An unknown error occurred<br />at the gateway");
		}
		return PaycardLib::PAYCARD_ERR_PROC;
	}

	function handleResponseVoid($authResult){
		/*
		global $USER_INFO;
		$xml = new xmlData($authResult['response']);
		$dbTrans = b2c_db();

		// prepare some fields to store the parsed response; we'll add more as we verify it
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = (int)$USER_INFO['UID'];
		$laneNo = 1;
		$transNo = 1;
		$transID = $this->TRANS_ID;
		$amount = 0; // fix this
		$amountText = number_format(abs($amount), 2, '.', '');
		$refNum = $this->refnum($transID);

		// prepare some fields to store the request and the parsed response; we'll add more as we verify it
		$sqlColumns =
			"date,cashierNo,laneNo,transNo,transID,[datetime]," .
			"origAmount,mode,altRoute," .
			"seconds,commErr,httpCode";
		$sqlValues =
			sprintf("%d,%d,%d,%d,%d,%s,",  $today, $cashierNo, $laneNo, $transNo, $transID, $dbTrans->escape($now)) .
			sprintf("%f,'%s',%d,", $amountText, "VOID", 0) .
			sprintf("%f,%d,%d", $authResult['curlTime'], $authResult['curlErr'], $authResult['curlHTTP']);

		$validResponse = ($xml->isValid()) ? 1 : 0;

		$responseCode = $xml->get("STATUS1");
		if ($responseCode){
			$sqlColumns .= ",xResponseCode";
			$sqlValues .= sprintf(",%d",$responseCode);
		}
		else $validResponse = -3;
		$resultCode = $xml->get_first("STATUS1");
		if ($resultCode){
			$sqlColumns .= ",xResultCode";
			$sqlValues .= sprintf(",%d",$resultCode);
		}
		$resultMsg = $xml->get_first("RESPONSE1");
		if ($resultMsg){
			$sqlColumns .= ",xResultMessage";
			$rMsg = $resultMsg;
			if (strlen($rMsg) > 100){
				$rMsg = substr($rMsg,0,100);
			}
			$sqlValues .= sprintf(",%s",$dbTrans->escape($rMsg));
		}
		$sqlColumns .= ",origTransactionID";
		$sqlValues .= sprintf(",%s",$dbcTrans->escape($this->void_trans));
		$sqlColumns .= ",origRefNum";
		$sqlValues .= sprintf(",%s",$dbTrans->escape($this->void_ref));

		$sqlColumns .= ",validResponse";
		$sqlValues .= sprintf(",%d",$validResponse);

		$sql = "INSERT INTO efsnetRequestMod (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
		$dbTrans->query($sql);

		if( $authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
			if ($authResult['curlHTTP'] == '0'){
				return PAYCARD_ERR_PROC;
			}	
			return PAYCARD_ERR_COMM;
		}

		switch ($xml->get("STATUS1")){
			case 1: // APPROVED
				return PAYCARD_ERR_OK;
			case 2: // DECLINED
				$this->ERRORS[] = "Void transaction declined";
				break;
			case 0: // ERROR
				$texts = $xml->get_first("ERROR1");
				$this->ERRORS[] = $texts;
				break;
			default:
				$this->ERRORS[] = "An unknown error occurred<br />at the gateway";
		}
		return PAYCARD_ERR_PROC;
		*/
	}

	function cleanup($json=array())
    {
		switch(CoreLocal::get("paycard_mode")){
		case PaycardLib::PAYCARD_MODE_AUTH:
			// cast to string. tender function expects string input
			// numeric input screws up parsing on negative values > $0.99
			$amt = "".(-1*(CoreLocal::get("paycard_amount")));
			$t_type = 'CC';
			if (CoreLocal::get('paycard_issuer') == 'American Express')
				$t_type = 'AX';
            // if the transaction has a non-zero efsnetRequestID,
            // include it in the tender line
            $record_id = $this->last_req_id;
            $charflag = ($record_id != 0) ? 'RQ' : '';
			TransRecord::addFlaggedTender("Credit Card", $t_type, $amt, $record_id, $charflag);
			CoreLocal::set("boxMsg","<b>Approved</b><font size=-1><p>Please verify cardholder signature<p>[enter] to continue<br>\"rp\" to reprint slip<br>[void] to cancel and void</font>");
			if (CoreLocal::get("paycard_amount") <= CoreLocal::get("CCSigLimit") && CoreLocal::get("paycard_amount") >= 0){
				CoreLocal::set("boxMsg","<b>Approved</b><font size=-1><p>No signature required<p>[enter] to continue<br>[void] to cancel and void</font>");
			}	
			break;
		case PaycardLib::PAYCARD_MODE_VOID:
			$v = new Void();
			$v->voidid(CoreLocal::get("paycard_id"));
			CoreLocal::set("boxMsg","<b>Voided</b><p><font size=-1>[enter] to continue<br>\"rp\" to reprint slip</font>");
			break;	
		}
		CoreLocal::set("ccCustCopy",0);
		if (CoreLocal::get("paycard_amount") > CoreLocal::get("CCSigLimit") || CoreLocal::get("paycard_amount") < 0)
			$json['receipt'] = "ccSlip";
		return $json;
	}

	function doSend($type)
    {
		switch($type){
		case PaycardLib::PAYCARD_MODE_AUTH: 
			return $this->send_auth();
		case PaycardLib::PAYCARD_MODE_VOID: 
			return $this->send_void(); 
		default:
			PaycardLib::paycard_reset();
			return $this->setErrorMsg(0);
		}
	}	

	function send_auth()
    {
		$dbTrans = PaycardLib::paycard_db();
		if( !$dbTrans){
			PaycardLib::paycard_reset();
			return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
		}

		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = CoreLocal::get("CashierNo");
		$laneNo = CoreLocal::get("laneno");
		$transNo = CoreLocal::get("transno");
		$transID = CoreLocal::get("paycard_id");
		$amount = CoreLocal::get("paycard_amount");
		$amountText = number_format(abs($amount), 2, '.', '');
		$mode = 'sale';
		$manual = (CoreLocal::get("paycard_manual") ? 1 : 0);
		$this->trans_pan['pan'] = CoreLocal::get("paycard_PAN");
		$cardPAN = $this->trans_pan['pan'];
		$cardPANmasked = PaycardLib::paycard_maskPAN($cardPAN,0,4);
		$cardIssuer = CoreLocal::get("paycard_issuer");
		$cardExM = substr(CoreLocal::get("paycard_exp"),0,2);
		$cardExY = substr(CoreLocal::get("paycard_exp"),2,2);
		$cardTr1 = CoreLocal::get("paycard_tr1");
		$cardTr2 = CoreLocal::get("paycard_tr2");
		$cardTr3 = CoreLocal::get("paycard_tr3");
		$cardName = CoreLocal::get("paycard_name");
		$refNum = $this->refnum($transID);
		$live = 1;
		$cvv2 = CoreLocal::get("paycard_cvv2");

		if (CoreLocal::get("training") == 1){
			$pan = "4111111111111111";
			$cardPANmasked = "xxxxxxxxxxxxTEST";
			$cardIssuer = "Visa";
			$cardTr1 = False;
			$cardTr2 = False;
			$cardName = "Just Testing";
			$nextyear = mktime(0,0,0,date("m"),date("d"),date("Y")+1);
			$cardExM = date("m",$nextyear);
			$cardExY = date("y",$nextyear);
			$live = 0;
		}

		$sendPAN = 0;
		$sendExp = 0;
		$sendTr1 = 0;
		$sendTr2 = 0;
		$magstripe = "";
		if (!$cardTr1 && !$cardTr2){
			$sendPAN = 1;
			$sendExp = 1;
		}
		if ($cardTr1) {
			$sendTr1 = 1;
			$magstripe .= "%".$cardTr1."?";
		}
		if ($cardTr2){
			$sendTr2 = 1;
			$magstripe .= ";".$cardTr2."?";
		}
		if ($cardTr2 && $cardTr3){
			$sendPAN = 1;
			$magstripe .= ";".$cardTr3."?";
		}

		$sqlCols = "sentPAN,sentExp,sentTr1,sentTr2";
		$sqlVals = "$sendPAN,$sendExp,$sendTr1,$sendTr2";
		// store request in the database before sending it
		$sqlCols .= "," . // already defined some sent* columns
			"date,cashierNo,laneNo,transNo,transID," .
			"datetime,refNum,live,mode,amount," .
			"PAN,issuer,manual,name";
		$fixedName = PaycardLib::paycard_db_escape($cardName, $dbTrans);
		$sqlVals .= "," . // already defined some sent* values
			sprintf("%d,%d,%d,%d,%d,",        $today, $cashierNo, $laneNo, $transNo, $transID) .
			sprintf("'%s','%s',%d,'%s',%s,",  $now, $refNum, $live, $mode, $amountText) .
			sprintf("'%s','%s',%d,'%s'",           $cardPANmasked, $cardIssuer, $manual,$fixedName);
		$sql = "INSERT INTO efsnetRequest (" . $sqlCols . ") VALUES (" . $sqlVals . ")";
        $table_def = $dbTrans->table_definition('efsnetRequest');
		if( !PaycardLib::paycard_db_query($sql, $dbTrans) ) {
			PaycardLib::paycard_reset();
			return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
		}

        if (isset($table_def['efsnetRequestID'])) {
            $this->last_req_id = $dbTrans->insert_id();
        }

		$xml = '<fdggwsapi:FDGGWSApiOrderRequest  
			 xmlns:v1="http://secure.linkpt.net/fdggwsapi/schemas_us/v1" 
		 	 xmlns:fdggwsapi="http://secure.linkpt.net/fdggwsapi/schemas_us/fdggwsapi"> 
			 <v1:Transaction>';

		$xml .= "<v1:CreditCardTxType> 
			   <v1:Type>$mode</v1:Type> 
			  </v1:CreditCardTxType>";
  		$xml .= "<v1:CreditCardData> 
			   <v1:CardNumber>$pan</v1:CardNumber> 
			   <v1:ExpMonth>$cardExM</v1:ExpMonth> 
			   <v1:ExpYear>$cardExY</v1:ExpYear> 
			   <v1:CardCodeValue>$cvv2</v1:CardCodeValue>
			  </v1:CreditCardData>";
		$xml .= "<v1:Payment>
			<v1:ChargeTotal>$amountText</v1:ChargeTotal> 
			</v1:Payment>";
		$xml .= "<v1:TransactionDetails>
			<v1:OrderId>$refNum</v1:OrderId>
			<v1:Ip>{$_SERVER['REMOTE_ADDR']}</v1:Ip>
			</v1:TransactionDetails>";
		$xml .= '</v1:Transaction> 
			</fdggwsapi:FDGGWSApiOrderRequest>';

		$this->GATEWAY = "https://ws.firstdataglobalgateway.com/fdggwsapi/services/order.wsdl";
		if ($live == 0)
			$this->GATEWAY = "https://ws.merchanttest.firstdataglobalgateway.com/fdggwsapi/services/order.wsdl";

		$extraCurlSetup = array(
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => "WS".FD_STORE_ID."._.1:".FD_PASSWD,
			CURLOPT_SSLCERT => FD_CERT_PATH."/WS".FD_STORE_ID."._.1.pem",
			CURLOPT_SSLKEY => FD_CERT_PATH."/WS".FD_STORE_ID."._.1.key",
			CURLOPT_SSLKEYPASSWD => FD_KEY_PASSWD
		);

		$soaptext = $this->soapify('', array('xml'=>$xml), '', False);
		return $this->curlSend($soaptext,'SOAP',True,$extraCurlSetup);
	}

	var $void_trans;
	var $void_ref;
	function send_void($amt,$pan,$exp){
		/*
		global $USER_INFO;
		$dbTrans = b2c_db();
		if( !$dbTrans){
			$this->ERRORS[] = "Could not connect to database";
			return PAYCARD_ERR_NOSEND;
		}

		// prepare data for the void request
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // new timestamp
		$cashierNo = (int)$USER_INFO['UID'];
		$laneNo = 1;
		$transNo = 1;
		$transID = $this->TRANS_ID;
		$amount = $amt;
		$amountText = number_format(abs($amount), 2, '.', '');
		$mode = 'void';
		$manual = 1;
		$cardPANmasked = paycard_maskPAN($pan,0,4);
		$cardIssuer = paycard_issuer($pan);
		$cardExM = substr($exp,0,2);
		$cardExY = substr($exp,2,2);
		$refNum = $this->refnum($cashierNo,$laneNo,$transNo,$transID);
		$live = 1;

		$merchantID = GOEMERCH_ID;
		$password = GOEMERCH_PASSWD;
		$gatewayID = GOEMERCH_GATEWAY_ID;
		if (GOEMERCH_TEST_MODE){
			$merchantID = "1264";
			$password = "password";
			$pan = "4111111111111111";
			$gatewayID = "a91c38c3-7d7f-4d29-acc7-927b4dca0dbe";
			$cardPANmasked = "xxxxxxxxxxxxTEST";
			$cardIssuer = "Visa";
			$cardName = "Just Testing";
			$nextyear = mktime(0,0,0,date("m"),date("d"),date("Y")+1);
			$cardExM = date("m",$nextyear);
			$cardExY = date("y",$nextyear);
			$live = 0;
		}

		// look up original request
		$sql = sprintf("SELECT transID FROM efsnetRequest WHERE amount=%.2f AND date=%s AND PAN=%s",
				$amt,$dbTrans->escape($today),$dbTrans->escape($cardPANmasked));
		$resp = $dbTrans->query($sql);
		if ($dbTrans->num_rows($resp) != 1){
			$this->ERRORS[] = "Could not find original transaction request";
			return PAYCARD_ERR_NOSEND;
		}
		$old_id = array_pop($dbTrans->fetch_row($resp));

		// look up the TransactionID from the original response 
		$sql = sprintf("SELECT refNum,xTransactionID FROM efsnetResponse WHERE date=%s
			 AND cashierNo=%d AND laneNo=%d AND transNo=%d AND transID=%d",
			$dbTrans->escape($today),$cashierNo,$laneNo,$transNo,$old_id);
		$result = $dbTrans->query($sql);
		if( !$result || $dbTrans->num_rows($result) != 1){
			$this->ERRORS[] = "Could not find original transaction response";
			return PAYCARD_ERR_NOSEND; 
		}
		$res = $dbTrans->fetch_array($result);
		$TransactionID = $res['xTransactionID'];
		$this->void_trans = $res['xTransactionID'];
		$this->void_ref = $res['refNum'];

		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		$xml .= "<TRANSACTION>";
		$xml .= "<FIELDS>";
		$xml .= "<FIELD KEY=\"merchant\">$merchantID</FIELD>";
		if ($password != "")
			$xml .= "<FIELD KEY=\"password\">$password</FIELD>";
		$xml .= "<FIELD KEY=\"gateway_id\">$gatewayID</FIELD>";
		$xml .= "<FIELD KEY=\"operation_type\">$mode</FIELD>";
		$xml .= "<FIELD KEY=\"total_number_transactions\">1</FIELD>";
		$xml .= "<FIELD KEY=\"reference_number1\">$TransactionID</FIELD>";
		$xml .= "<FIELD KEY=\"credit_amount1\">$amountText</FIELD>";
		$xml .= "</FIELDS>";
		$xml .= "</TRANSACTION>";

		$this->GATEWAY = "https://secure.goemerchant.com/secure/gateway/xmlgateway.aspx";
		return $this->curlSend($xml,'POST',True);
		*/
	}

	function refnum($transID)
    {
		$transNo   = (int)CoreLocal::get("transno");
		$cashierNo = (int)CoreLocal::get("CashierNo");
		$laneNo    = (int)CoreLocal::get("laneno");	

		// assemble string
		$ref = "";
		$ref .= date("ymdHis");
		$ref .= "-";
		$ref .= str_pad($cashierNo, 4, "0", STR_PAD_LEFT);
		$ref .= str_pad($laneNo,    2, "0", STR_PAD_LEFT);
		$ref .= str_pad($transNo,   3, "0", STR_PAD_LEFT);
		$ref .= str_pad($transID,   3, "0", STR_PAD_LEFT);
		return $ref;
	}

	protected $SOAP_ENVELOPE_ATTRS = array(
		"xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\""
		);

	/** FirstData has a signficantly different SOAP format
	    so the parent implementation is overriden
	  @param $action top level tag in the soap body
	  @param $objs keyed array of values	
	  @param $namespace include an xmlns attribute
	  @return soap string
	*/
	function soapify($action,$objs,$namespace="",$encode_tags=True){
		$ret = "<?xml version=\"1.0\"?>
			<SOAP-ENV:Envelope";
		foreach ($this->SOAP_ENVELOPE_ATTRS as $attr){
			$ret .= " ".$attr;
		}
		$ret .= ">
			<SOAP-ENV:Header />
			<SOAP-ENV:Body>";
		foreach($objs as $xml)
			$ret .= $xml;
		$ret .= "</SOAP-ENV:Body>
			</SOAP-ENV:Envelope>";

		return $ret;
	}
}

