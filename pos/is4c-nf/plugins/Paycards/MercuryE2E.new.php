<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
  Implementation status 9/18/12:

  Basics are done. XML spec for requests and responses should be right or close.
  Further work is mostly device specific. Overall interface flow may need
  an additional step if credit/debit/ebt cannot be inferred from device input
  and must be specified by cashier.

  @todo: 
  * Distinguish debit & ebt from credit
  * Read issuer and/or last 4 digits PAN and/or cardholder name from device input
*/

if (!class_exists("BasicCCModule")) include_once(realpath(dirname(__FILE__)."/BasicCCModule.php"));
if (!class_exists("xmlData")) include_once(realpath(dirname(__FILE__)."/lib/xmlData.php"));
if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/paycardLib.php"));

if (!isset($CORE_LOCAL)){
	include(realpath(dirname(__FILE__)."/lib/LS_Access.php"));
	$CORE_LOCAL = new LS_Access();
}

if (!class_exists("AutoLoader")) include_once(realpath(dirname(__FILE__).'/../../lib/AutoLoader.php'));

define('MERCURY_TERMINAL_ID',"");
define('MERCURY_PASSWORD',"");
define('MERCURY_USE_TOKENS',True);

class MercuryE2E extends BasicCCModule {

	var $voidTrans;
	var $voidRef;
	var $SOAPACTION = "http://www.mercurypay.com/CreditTransaction";
	var $second_try;

	function handlesType($type){
		if ($type == PaycardLib::PAYCARD_TYPE_ENCRYPTED) return True;
		else return False;
	}

	/**
	  Updated for E2E
	  Status: done
	*/
	function entered($validate,$json){
		global $CORE_LOCAL;
		// error checks based on card type
		if( $CORE_LOCAL->get("CCintegrate") != 1) { // credit card integration must be enabled
			PaycardLib::paycard_reset();
			$json['output'] = PaycardLib::paycard_errBox(PaycardLib::PAYCARD_TYPE_GIFT,
				"Card Integration Disabled",
				"Please process credit cards in standalone",
				"[clear] to cancel");
			return $json;
		}

		// error checks based on processing mode
		switch( $CORE_LOCAL->get("paycard_mode")) {
		case PaycardLib::PAYCARD_MODE_VOID:
			// use the card number to find the trans_id
			$dbTrans = PaycardLib::paycard_db();
			$today = date('Ymd');
			$pan4 = substr($this->$trans_pan['pan'],-4);
			$cashier = $CORE_LOCAL->get("CashierNo");
			$lane = $CORE_LOCAL->get("laneno");
			$trans = $CORE_LOCAL->get("transno");
			$sql = "SELECT transID FROM efsnetRequest WHERE ".$dbTrans->identifier_escape('date').
				"='".$today."' AND (PAN LIKE '%".$pan4."') " .
				"AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans;
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
			// set initial variables
			//Database::getsubtotals();
			$e2e = $this->parseEncBlock($CORE_LOCAL->get('paycard_PAN'));
			if (empty($e2e['Block']) || empty($e2e['Key'])){
				PaycardLib::paycard_reset();
				$json['output'] = PaycardLib::paycard_msgBox(PaycardLib::PAYCARD_TYPE_CREDIT,"Swipe Error",
					"Error reading card. Swipe again.","[clear] to cancel");
				UdpComm::udpSend('termReset');
				return $json;
			}
			if ($CORE_LOCAL->get("paycard_amount") == 0)
				$CORE_LOCAL->set("paycard_amount",$CORE_LOCAL->get("amtdue"));
			$CORE_LOCAL->set("paycard_id",$CORE_LOCAL->get("LastID")+1); // kind of a hack to anticipate it this way..
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

	/**
	  Updated for E2E
	*/
	function paycard_void($transID,$laneNo=-1,$transNo=-1,$json=array()) {
		global $CORE_LOCAL;
		$this->voidTrans = "";
		$this->voidRef = "";
		// situation checking
		if( $CORE_LOCAL->get("CCintegrate") != 1) { // credit card integration must be enabled
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
		$cashier = $CORE_LOCAL->get("CashierNo");
		$lane = $CORE_LOCAL->get("laneno");
		$trans = $CORE_LOCAL->get("transno");
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
				." and register_no=".$lane." and trans_no=".$trans;
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
		} else if( $request['live'] != PaycardLib::paycard_live(PaycardLib::PAYCARD_TYPE_ENCRYPTED)) {
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
		$CORE_LOCAL->set("paycard_amount",(($request['mode']=='retail_alone_credit') ? -1 : 1) * $request['amount']);
		$CORE_LOCAL->set("paycard_id",$transID);
		$CORE_LOCAL->set("paycard_trans",$cashier."-".$lane."-".$trans);
		$CORE_LOCAL->set("paycard_type",PaycardLib::PAYCARD_TYPE_ENCRYPTED);
		$CORE_LOCAL->set("paycard_mode",PaycardLib::PAYCARD_MODE_VOID);
		$CORE_LOCAL->set("paycard_name",$request['name']);
	
		// display FEC code box
		$CORE_LOCAL->set("inputMasked",1);
		$plugin_info = new Paycards();
		$json['main_frame'] = $plugin_info->plugin_url().'/gui/paycardboxMsgVoid.php';
		return $json;
	}

	function handleResponse($authResult){
		global $CORE_LOCAL;
		switch($CORE_LOCAL->get("paycard_mode")){
		case PaycardLib::PAYCARD_MODE_AUTH:
			return $this->handleResponseAuth($authResult);
		case PaycardLib::PAYCARD_MODE_VOID:
			return $this->handleResponseVoid($authResult);
		}
	}

	/**
	  Updated for E2E
	*/
	function handleResponseAuth($authResult){
		global $CORE_LOCAL;
		$resp = $this->desoapify("CreditTransactionResult",
			$authResult["response"]);
		$xml = new xmlData($resp);

		$dbTrans = PaycardLib::paycard_db();

		/*
		$fp = fopen("C:/is4c-nf/log.xml","a");
		fwrite($fp,"RECEIVED: ".$resp."\n\n");
		fclose($fp);
		 */

		// prepare some fields to store the parsed response; we'll add more as we verify it
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $CORE_LOCAL->get("CashierNo");
		$laneNo = $CORE_LOCAL->get("laneno");
		$transNo = $CORE_LOCAL->get("transno");
		$transID = $CORE_LOCAL->get("paycard_id");
		// mercury truncates the reference number in the response; recalculate so it
		// actually matches
		$refNum = $this->refnum($transID);

		$sqlColumns =
			$dbTrans->identifier_escape('date').",cashierNo,laneNo,transNo,transID," .
			$dbTrans->identifier_escape('datetime').",refNum," .
			"seconds,commErr,httpCode";
		$sqlValues =
			sprintf("%d,%d,%d,%d,%d,",  $today, $cashierNo, $laneNo, $transNo, $transID) .
			sprintf("'%s','%s',",            $now, $refNum ) .
			sprintf("%f,%d,%d",         $authResult['curlTime'], $authResult['curlErr'], $authResult['curlHTTP']);
		$validResponse = ($xml->isValid()) ? 1 : 0;

		$responseCode = $xml->get("CMDSTATUS");
		if ($responseCode){
			// map response status to 0/1/2 for compatibility
			if ($responseCode == "Approved") $responseCode=1;
			elseif ($responseCode == "Declined") $responseCode=2;
			elseif ($responseCode == "Error") $responseCode=0;
			$sqlColumns .= ",xResponseCode";
			$sqlValues .= sprintf(",%d",$responseCode);
		}
		else $validResponse = -3;
		$resultCode = $xml->get("DSIXRETURNCODE");
		if ($resultCode){
			$sqlColumns .= ",xResultCode";
			$sqlValues .= sprintf(",%d",$resultCode);
		}
		$resultMsg = $xml->get_first("CMDSTATUS");
		if ($resultMsg){
			$sqlColumns .= ",xResultMessage";
			$rMsg = $resultMsg;
			if (strlen($rMsg) > 100){
				$rMsg = substr($rMsg,0,100);
			}
			$aNum = $xml->get("AUTHCODE");
			if ($aNum)
				$rMsg .= ' '.$aNum;
			$sqlValues .= sprintf(",'%s'",$rMsg);
		}
		$xTransID = $xml->get("REFNO");
		if ($xTransID){
			$sqlColumns .= ",xTransactionID";
			$sqlValues .= sprintf(",'%s'",$xTransID);
		}
		else $validResponse = -3;
		$apprNumber = $xml->get("AUTHCODE");
		if ($apprNumber){
			$sqlColumns .= ",xApprovalNumber";
			$sqlValues .= sprintf(",'%s'",$apprNumber);
		}
		$sqlColumns .= ",validResponse";
		$sqlValues .= sprintf(",%d",$validResponse);

		$sql = "INSERT INTO efsnetResponse (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
		PaycardLib::paycard_db_query($sql, $dbTrans);

		if ($responseCode == 1){
			$amt = $xml->get_first("AUTHORIZE");
			if ($amt != abs($CORE_LOCAL->get("paycard_amount"))){
				$sql = sprintf("UPDATE efsnetRequest SET amount=%f WHERE "
					.$dbTrans->identifier_escape('date')."=%d 
					AND cashierNo=%d AND laneNo=%d AND transNo=%d
					AND transID=%d",
					$amt,$today, $cashierNo, $laneNo, $transNo, $transID);
				PaycardLib::paycard_db_query($sql, $dbTrans);
				$CORE_LOCAL->set("paycard_amount",$amt);
			}
		}

		if (MERCURY_USE_TOKENS){
			$tokenSql = sprintf("INSERT INTO efsnetTokens (expireDay, refNum, token, processData, acqRefData) 
					VALUES (%s,'%s','%s','%s','%s')",
				$dbTrans->now(),
				$refNum, $xml->get_first("RECORDNO"),
				$xml->get_first("PROCESSDATA"),
				$xml->get_first("ACQREFDATA")
			);
			PaycardLib::paycard_db_query($tokenSql, $dbTrans);
		}

		if( $authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
			if (!$this->second_try){
				$this->second_try = True;
				return $this->send_auth("w2.backuppay.com");
			}
			else if ($authResult['curlHTTP'] == '0'){
				$CORE_LOCAL->set("boxMsg","No response from processor<br />
							The transaction did not go through");
				return PaycardLib::PAYCARD_ERR_PROC;
			}	
			else
				return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
		}

		switch (strtoupper($xml->get_first("CMDSTATUS"))){
			case 'APPROVED':
				return PaycardLib::PAYCARD_ERR_OK;
			case 'DECLINED':
			case 'ERROR':
				$CORE_LOCAL->set("boxMsg","");
				$texts = $xml->get_first("TEXTRESPONSE");
				$CORE_LOCAL->set("boxMsg","Error: $texts");
				TransRecord::addcomment("");	
				break;
			default:
				$CORE_LOCAL->set("boxMsg","An unknown error occurred<br />at the gateway");
				TransRecord::addcomment("");	
		}
		return PaycardLib::PAYCARD_ERR_PROC;
	}

	/**
	  Updated for E2E
	*/
	function handleResponseVoid($authResult){
		global $CORE_LOCAL;
		$resp = $this->desoapify("CreditTransactionResult",
			$authResult["response"]);
		$xml = new xmlData($resp);
		$dbTrans = PaycardLib::paycard_db();

		/*
		$fp = fopen("C:/is4c-nf/log.xml","a");
		fwrite($fp,"RECEIVED: ".$resp."\n\n");
		fclose($fp);
		 */

		// prepare some fields to store the parsed response; we'll add more as we verify it
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $CORE_LOCAL->get("CashierNo");
		$laneNo = $CORE_LOCAL->get("laneno");
		$transNo = $CORE_LOCAL->get("transno");
		$transID = $CORE_LOCAL->get("paycard_id");
		$amount = $CORE_LOCAL->get("paycard_amount");
		$amountText = number_format(abs($amount), 2, '.', '');
		$refNum = $this->refnum($transID);

		// prepare some fields to store the request and the parsed response; we'll add more as we verify it
		$sqlColumns =
			$dbTrans->identifier_escape('date').",cashierNo,laneNo,transNo,transID,".
			$dbTrans->identifier_escape('datetime').
			",origAmount,mode,altRoute," .
			"seconds,commErr,httpCode";
		$sqlValues =
			sprintf("%d,%d,%d,%d,%d,'%s',",  $today, $cashierNo, $laneNo, $transNo, $transID, $now) .
			sprintf("%s,'%s',%d,",  $amountText, "VOID", 0) .
			sprintf("%f,%d,%d", $authResult['curlTime'], $authResult['curlErr'], $authResult['curlHTTP']);

		$validResponse = ($xml->isValid()) ? 1 : 0;

		$responseCode = $xml->get("CMDSTATUS");
		if ($responseCode){
			// map response status to 0/1/2 for compatibility
			if ($responseCode == "Approved") $responseCode=1;
			elseif ($responseCode == "Declined") $responseCode=2;
			elseif ($responseCode == "Error") $responseCode=0;
			$sqlColumns .= ",xResponseCode";
			$sqlColumns .= ",xResponseCode";
			$sqlValues .= sprintf(",%d",$responseCode);
		}
		else $validResponse = -3;
		$resultCode = $xml->get_first("DSIXRETURNCODE");
		if ($resultCode){
			$sqlColumns .= ",xResultCode";
			$sqlValues .= sprintf(",%d",$resultCode);
		}
		$resultMsg = $xml->get_first("CMDSTATUS");
		if ($resultMsg){
			$sqlColumns .= ",xResultMessage";
			$rMsg = $resultMsg;
			if (strlen($rMsg) > 100){
				$rMsg = substr($rMsg,0,100);
			}
			$sqlValues .= sprintf(",'%s'",$rMsg);
		}
		$sqlColumns .= ",origTransactionID";
		$sqlValues .= sprintf(",'%s'",$this->voidTrans);
		$sqlColumns .= ",origRefNum";
		$sqlValues .= sprintf(",'%s'",$this->voidRef);

		$sqlColumns .= ",validResponse";
		$sqlValues .= sprintf(",%d",$validResponse);

		$sql = "INSERT INTO efsnetRequestMod (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
		PaycardLib::paycard_db_query($sql, $dbTrans);

		$tokenRef = $xml->get_first("INVOICENO");
		$sql = sprintF("DELETE FROM efsnetTokens WHERE refNum='%s'",$tokenRef);
		PaycardLib::paycard_db_query($sql, $dbTrans);

		if( $authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
			if ($authResult['curlHTTP'] == '0'){
				$CORE_LOCAL->set("boxMsg","No response from processor<br />
							The transaction did not go through");
				return PaycardLib::PAYCARD_ERR_PROC;
			}	
			return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_COMM);
		}

		switch (strtoupper($xml->get("CMDSTATUS"))){
			case 'APPROVED':
				return PaycardLib::PAYCARD_ERR_OK;
			case 'DECLINED':
				// if declined, try again with a regular Void op
				// and no reversal information
				$skipReversal = $CORE_LOCAL->get("MercuryE2ESkipReversal");
				if ($skipReversal == True)
					$CORE_LOCAL->set("MercuryE2ESkipReversal",False);
				else
					return $this->send_void(True);
			case 'ERROR':
				$CORE_LOCAL->set("boxMsg","");
				$texts = $xml->get_first("TEXTRESPONSE");
				$CORE_LOCAL->set("boxMsg","Error: $texts");
				break;
			default:
				$CORE_LOCAL->set("boxMsg","An unknown error occurred<br />at the gateway");
		}
		return PaycardLib::PAYCARD_ERR_PROC;
	}

	function cleanup($json){
		global $CORE_LOCAL;
		switch($CORE_LOCAL->get("paycard_mode")){
		case PaycardLib::PAYCARD_MODE_AUTH:
			$CORE_LOCAL->set("ccTender",1); 
			// cast to string. tender function expects string input
			// numeric input screws up parsing on negative values > $0.99
			$amt = "".(-1*($CORE_LOCAL->get("paycard_amount")));
			$type = $CORE_LOCAL->get("CacheCardType");
			if (substr($type,0,3) == 'EBT' && $type=="EBTCASH")
				TransRecord::addtender("EBT Cash", "EC", $amt);
			elseif (substr($type,0,3) == 'EBT')
				TransRecord::addtender("EBT Food", "EF", $amt);
			elseif ($type == "DEBIT")
				TransRecord::addtender("Debit Card", "DC", $amt);
			else
				TransRecord::addtender("Credit Card", "CC", $amt);
			$CORE_LOCAL->set("boxMsg","<b>Approved</b><font size=-1><p>Please verify cardholder signature<p>[enter] to continue<br>\"rp\" to reprint slip</font>");
			if (($CORE_LOCAL->get("paycard_amount") <= $CORE_LOCAL->get("CCSigLimit") && $CORE_LOCAL->get("paycard_amount") >= 0)
			     || $type == "DEBIT" || substr($type,0,3)=="EBT"){
				$CORE_LOCAL->set("boxMsg","<b>Approved</b><font size=-1><p>No signature required<p>[enter] to continue</font>");
			}
			break;
		case PaycardLib::PAYCARD_MODE_VOID:
			$v = new Void();
			$v->voidid($CORE_LOCAL->get("paycard_id"));
			$CORE_LOCAL->set("boxMsg","<b>Voided</b><p><font size=-1>[enter] to continue<br>\"rp\" to reprint slip</font>");
			break;	
		}
		$CORE_LOCAL->set("ccCustCopy",0);
		if (($CORE_LOCAL->get("paycard_amount") > $CORE_LOCAL->get("CCSigLimit") || $CORE_LOCAL->get("paycard_amount") < 0)
			&& ($CORE_LOCAL->get('CacheCardType')=='CREDIT' || $CORE_LOCAL->get('CacheCardType')=='')){
			$json['receipt'] = "ccSlip";
		}
		return $json;
	}

	function doSend($type){
		global $CORE_LOCAL;
		$this->second_try=False;
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

	/**
	  Updated for E2E
	  Status: Should be functional once device is available
	*/
	function send_auth($domain="w1.mercurypay.com"){
		global $CORE_LOCAL;
		// initialize
		$dbTrans = PaycardLib::paycard_db();
		if( !$dbTrans){
			PaycardLib::paycard_reset();
			return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)
		}

		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $CORE_LOCAL->get("CashierNo");
		$laneNo = $CORE_LOCAL->get("laneno");
		$transNo = $CORE_LOCAL->get("transno");
		$transID = $CORE_LOCAL->get("paycard_id");
		$amount = $CORE_LOCAL->get("paycard_amount");
		$type = $CORE_LOCAL->get("CacheCardType");
		if ($type == "CREDIT") $type = "Credit";
		elseif ($type == "DEBIT") $type = "Debit";
		elseif ($type == "") $type = "Credit";
		$cashback = 0;
		if (($type == "Debit" || $type == "EBTCASH") && $amount > $CORE_LOCAL->get("amtdue")){
			$cashback = $amount - $CORE_LOCAL->get("amtdue");
			$amount = $CORE_LOCAL->get("amtdue");
		}
		$amountText = number_format(abs($amount), 2, '.', '');
		$cashbackText = number_format(abs($cashback), 2, '.', '');
		$mode = (($amount < 0) ? 'Return' : 'Sale');
		if ($CORE_LOCAL->get("paycard_voiceauthcode") != "")
			$mode = "VoiceAuth";
		else if ($CORE_LOCAL->get("ebt_authcode") != "" && $CORE_LOCAL->get("ebt_vnum") != "")
			$mode = "Voucher";
		$logged_mode = $type."_".$mode;
		$manual = ($CORE_LOCAL->get("paycard_keyed")===True ? 1 : 0);
		$refNum = $this->refnum($transID);
		$live = 1;
		if( $CORE_LOCAL->get("training") != 0 || $CORE_LOCAL->get("CashierNo") == 9999)
			$live = 0;
		$termID = $this->getTermID();
		$password = $this->getPw();
		$e2e = $this->parseEncBlock($CORE_LOCAL->get("paycard_PAN"));
		$pin = $this->parsePinBlock($CORE_LOCAL->get("CachePinEncBlock"));
		$cardIssuer = $e2e['Issuer'];
		$CORE_LOCAL->set('paycard_issuer',$e2e['Issuer']);
		$cardName = $e2e['Name'];
		$cardPANmasked = "XXXX";
		if (strlen($e2e['Last4']) == 4)
			$cardPANmasked = '************'.$e2e['Last4'];
		
		$sendPAN = 0;
		$sendExp = 0;
		$sendTr1 = 0;
		$sendTr2 = 1;
		$sqlCols = "sentPAN,sentExp,sentTr1,sentTr2";
		$sqlVals = "$sendPAN,$sendExp,$sendTr1,$sendTr2";

		// store request in the database before sending it
		$sqlCols .= "," . // already defined some sent* columns
			$dbTrans->identifier_escape('date').",cashierNo,laneNo,transNo,transID," .
			$dbTrans->identifier_escape('datetime').",refNum,live,mode,amount," .
			"PAN,issuer,manual,name";
		$fixedName = PaycardLib::paycard_db_escape($cardName, $dbTrans);
		if (strlen($fixedName) > 50) $fixedName = 'Cardholder';
		$sqlVals .= "," . // already defined some sent* values
			sprintf("%d,%d,%d,%d,%d,",        $today, $cashierNo, $laneNo, $transNo, $transID) .
			sprintf("'%s','%s',%d,'%s',%s,",  $now, $refNum, $live, $logged_mode, ($amountText+$cashbackText)) .
			sprintf("'%s','%s',%d,'%s'",           $cardPANmasked, $cardIssuer, $manual,$fixedName);
		$sql = "INSERT INTO efsnetRequest (" . $sqlCols . ") VALUES (" . $sqlVals . ")";

		if( !PaycardLib::paycard_db_query($sql, $dbTrans) ) {
			PaycardLib::paycard_reset();
			return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
		}

		// weird string concat here so vim's color highlighting
		// doesn't screw up with what LOOKS LIKE a close-PHP tag
		$msgXml = '<?xml version="1.0"?'.'>
			<TStream>
			<Transaction>
			<MerchantID>'.$termID.'</MerchantID>
			<OperatorID>'.$cashierNo.'</OperatorID>';
		if (substr($type,0,3) == 'EBT'){
			$msgXml .= '<TranType>EBT</TranType>';
			if ($type == 'EBTFOOD')
				$msgXml .= '<CardType>Foodstamp</CardType>';
			else if ($type == 'EBTCASH')
				$msgXml .= '<CardType>Cash</CardType>';
		}
		else {
			$msgXml .= '<TranType>'.$type.'</TranType>';
		}
		$msgXml .= '<TranCode>'.$mode.'</TranCode>
			<InvoiceNo>'.$refNum.'</InvoiceNo>
			<RefNo>'.$refNum.'</RefNo>
			<Memo>CORE POS 1.0.0</Memo>';
		if ($logged_mode == "Credit_Sale")
			$msgXml .= "<PartialAuth>Allow</PartialAuth>";
		if (MERCURY_USE_TOKENS){
			$msgXml .= '<RecordNo>RecordNumberRequested</RecordNo>';
			$msgXml .= '<Frequency>OneTime</Frequency>';	
		}
		$msgXml .= '<Account>
				<EncryptedFormat>'.$e2e['Format'].'</EncryptedFormat>
				<AccountSource>'.($manual ? 'Keyed' : 'Swiped').'</AccountSource>
				<EncryptedBlock>'.$e2e['Block'].'</EncryptedBlock>
				<EncryptedKey>'.$e2e['Key'].'</EncryptedKey>
			</Account>
			<Amount>
				<Purchase>'.$amountText.'</Purchase>';
		if ($cashback > 0 && ($type == "Debit" || $type == "EBTCASH"))
				$msgXml .= "<CashBack>$cashbackText</CashBack>";
		$msgXml .= "</Amount>";
		if ($type == "Debit" || (substr($type,0,3) == "EBT" && $mode != "Voucher")){
			$msgXml .= "<PIN>
				<PINBlock>".$pin['block']."</PINBlock>
				<DervdKey>".$pin['key']."</DervdKey>
				</PIN>";
		}
		if ($CORE_LOCAL->get("paycard_voiceauthcode") != ""){
			$msgXml .= "<TransInfo>";
			$msgXml .= "<AuthCode>";
			$msgXml .= $CORE_LOCAL->get("paycard_voiceauthcode");
			$msgXml .= "</AuthCode>";
			$msgXml .= "</TransInfo>";
		}
		else if ($CORE_LOCAL->get("ebt_authcode") != "" && $CORE_LOCAL->get("ebt_vnum") != ""){
			$msgXml .= "<TransInfo>";
			$msgXml .= "<AuthCode>";
			$msgXml .= $CORE_LOCAL->get("ebt_authcode");
			$msgXml .= "</AuthCode>";
			$msgXml .= "<VoucherNo>";
			$msgXml .= $CORE_LOCAL->get("ebt_vnum");
			$msgXml .= "</VoucherNo>";
			$msgXml .= "</TransInfo>";
		}
		$msgXml .= "</Transaction>
			</TStream>";

		$soaptext = $this->soapify("CreditTransaction",
			array("tran"=>$msgXml,"pw"=>$password),
			"http://www.mercurypay.com");

		/*
		$fp = fopen("C:/is4c-nf/log.xml","a");
		fwrite($fp,"SENT: ".$msgXml."\n\n");
		fclose($fp);
		*/
				
		if ($CORE_LOCAL->get("training") == 1)
			$this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
		else
			$this->GATEWAY = "https://$domain/ws/ws.asmx";

		return $this->curlSend($soaptext,'SOAP');
	}

	/**
	  Updated for E2E
	*/
	function send_void($skipReversal=False){
		global $CORE_LOCAL;
		// initialize
		$dbTrans = PaycardLib::paycard_db();
		if( !$dbTrans){
			PaycardLib::paycard_reset();
			return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND);
		}

		if ($skipReversal){
			$CORE_LOCAL->set("MercuryE2ESkipReversal",True);
		}
		else {
			$CORE_LOCAL->set("MercuryE2ESkipReversal",False);
		}

		// prepare data for the void request
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // new timestamp
		$cashierNo = $CORE_LOCAL->get("CashierNo");
		$laneNo = $CORE_LOCAL->get("laneno");
		$transNo = $CORE_LOCAL->get("transno");
		$transID = $CORE_LOCAL->get("paycard_id");
		$amount = $CORE_LOCAL->get("paycard_amount");
		$amountText = number_format(abs($amount), 2, '.', '');
		$mode = 'VoidSaleByRecordNo';
		$manual = ($CORE_LOCAL->get("paycard_keyed")===True ? 1 : 0);
		$cardIssuer = 'UNKNOWN';
		$cardName = 'Cardholder';
		$refNum = $this->refnum($transID);
		$live = 1;
		if( $CORE_LOCAL->get("training") != 0 || $CORE_LOCAL->get("CashierNo") == 9999)
			$live = 0;
		$termID = $this->getTermID();
		$password = $this->getPw();

		$this->voidTrans = $transID;
		$this->voidRef = $CORE_LOCAL->get("paycard_trans");
		$temp = explode("-",$this->voidRef);
		$laneNo = $temp[1];
		$transNo = $temp[2];

		// look up the transaction info for voiding
		$sql = "SELECT r.refNum,xTransactionID,q.amount,t.token,t.processData,t.acqRefData,r.xApprovalNumber,q.mode FROM efsnetResponse AS r
			LEFT JOIN efsnetRequest AS q ON q.refNum=r.refNum LEFT JOIN efsnetTokens AS t
			ON r.refNum=t.refNum
			WHERE r.".$dbTrans->identifier_escape('date')."='".$today."' 
			AND ".$dbTrans->datediff($dbTrans->now(),'t.expireDay')."=0
			AND r.cashierNo=".$cashierNo." AND r.laneNo=".$laneNo." AND r.transNo=".$transNo." AND r.transID=".$transID;
		$result = PaycardLib::paycard_db_query($sql, $dbTrans);
		if( !$result || PaycardLib::paycard_db_num_rows($result) != 1){
			PaycardLib::paycard_reset();
			return $this->setErrorMsg(PaycardLib::PAYCARD_ERR_NOSEND); 
		}
		$res = PaycardLib::paycard_db_fetch_row($result);
		if (substr($res['mode'],-7)=="_Return")
			$mode = 'VoidReturnByRecordNo';

		$msgXml = "<?xml version=\"1.0\""."?".">
			<TStream>
			<Transaction>
			<MerchantID>$termID</MerchantID>
			<OperatorID>$cashierNo</OperatorID>
			<TranType>Credit</TranType>
			<TranCode>$mode</TranCode>
			<InvoiceNo>$refNum</InvoiceNo>
			<RefNo>".$res['xTransactionID']."</RefNo>
			<Memo>CORE POS 1.0.0</Memo>
			<RecordNo>".$res['token']."</RecordNo>
			<Frequency>OneTime</Frequency>
			<Amount>
				<Purchase>$amountText</Purchase>
			</Amount>
			<TransInfo>";
		if (!$skipReversal){
			$msgXml .= "<AcqRefData>".$res['acqRefData']."</AcqRefData>
				<ProcessData>".$res['processData']."</ProcessData>";
		}
		$msgXml .= "<AuthCode>".$res['xApprovalNumber']."</AuthCode>
			</TransInfo>
			</Transaction>
			</TStream>";

		$soaptext = $this->soapify("CreditTransaction",
			array("tran"=>$msgXml,"pw"=>$password),
			"http://www.mercurypay.com");
				
		if ($CORE_LOCAL->get("training") == 1)
			$this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
		else
			$this->GATEWAY = "https://$domain/ws/ws.asmx";

		/*
		$fp = fopen("C:/is4c-nf/log.xml","a");
		fwrite($fp,"SENT: ".$msgXml."\n\n");
		fclose($fp);
		*/

		return $this->curlSend($soaptext,'SOAP');
	}

	// tack time onto reference number for goemerchant order_id
	// field. requires uniqueness, doesn't seem to cycle daily
	function refnum($transID){
		global $CORE_LOCAL;
		$transNo   = (int)$CORE_LOCAL->get("transno");
		$cashierNo = (int)$CORE_LOCAL->get("CashierNo");
		$laneNo    = (int)$CORE_LOCAL->get("laneno");	

		// assemble string
		$ref = "";
		$ref .= date("md");
		$ref .= str_pad($cashierNo, 4, "0", STR_PAD_LEFT);
		$ref .= str_pad($laneNo,    2, "0", STR_PAD_LEFT);
		$ref .= str_pad($transNo,   3, "0", STR_PAD_LEFT);
		$ref .= str_pad($transID,   3, "0", STR_PAD_LEFT);
		return $ref;
	}

	/**
	  Return real or testing ID depending on
	  whether training mode is on
	*/
	function getTermID(){
		global $CORE_LOCAL;
		if ($CORE_LOCAL->get("training") == 1){
			//return "595901";
			return "395347308=E2ETKN";
		}
		else
			return MERCURY_TERMINAL_ID;
	}

	/**
	  Return real or testing password depending on
	  whether training mode is on
	*/
	function getPw(){
		global $CORE_LOCAL;
		if ($CORE_LOCAL->get("training") == 1){
			//return "xyz";
			return "123E2ETKN";
		}
		else
			return MERCURY_PASSWORD;
	}

	/**
	  In theory parses output produced by MagTek and ID tech
	  devices (based on spec / examples)
	  @return array with keys
	   - Format is encrypt format
	   - Block is encryped PAN block
	   - Key is encrypted key
	   - Issuer is card issuer (Visa, MasterCard, etc)
	   - Last4 is last four PAN digits
	   - Name is cardholder name (if available)
	*/
	function parseEncBlock($str){
		$ret = array(
			'Format'=>'MagneSafe',
			'Block'=>'',
			'Key'=>'',
			'Issuer'=>'Unknown',
			'Last4'=>'XXXX',
			'Name'=>'Cardholder'
		);
		if (strstr($str,"|")){
			/* magtek style block */
			$parts = explode("|",$str);
			$tr1 = False;
			$tr2 = False;
			if ($str[0] == "%"){
				/* non-numbered format */
				$ret['Block'] = $parts[3];
				$ret['Key'] = $parts[9];
				$tr1 = $parts[0];
				$tr2 = $parts[1];
			}
			else if ($str[0] == "1"){
				/* numbered format */
				foreach($parts as $p){
					if (strlen($p) > 2 && substr($p,0,2)=="3~"){
						$ret['Block'] = substr($p,2);	
					}
					elseif (strlen($p) > 3 && substr($p,0,3)=="11~"){
						$ret['Key'] = substr($p,3);	
					}
					elseif (strlen($p) > 2 && substr($p,0,3)=="6~"){
						$tr1 = substr($p,2);
					}
					elseif (strlen($p) > 2 && substr($p,0,3)=="7~"){
						$tr2 = substr($p,2);
					}
				}
			}

			// extract info from masked tracks
			if ($tr1 && $tr1[0] == "%"){
				$split = explode("^",$tr1);
				$pan = substr($split[0],1);
				if (strlen($split[1]) <= 26)
					$ret['Name'] = $split[1];
				$ret['Last4'] = substr($pan,-4);
				$ret['Issuer'] = PaycardLib::paycard_issuer($pan);
			}
			elseif($tr2 && $tr2[0] == ";"){
				$tr2 = substr($tr2,1);
				$pan = substr($tr2,0,strpos("="));
				$ret['Last4'] = substr($pan,-4);
				$ret['Issuer'] = PaycardLib::paycard_issuer($pan);
			}
		}
		else if (strlen($str)>2 && substr($str,0,2)=="02"){
			/* IDtech style block */

			// read track length from block
			$track_length = array(
				1 => hexdec(substr($str,10,2)),
				2 => hexdec(substr($str,12,2)),
				3 => hexdec(substr($str,14,2))
			);

			// skip to track data start point
			$pos = 20;
			// move through masked track data
			foreach($track_length as $num=>$kl){
				if ($num == 1){
					// read name and masked PAN from track 1
					$caret = strpos($str,"5E",$pos);
					$pan = substr($str,$pos,$caret-$pos);
					$pan = substr($pan,4); // remove leading %*
					$caret2 = strpos($str,"5E",$caret+2);
					if ($caret2 < ($pos + ($kl*2))){ // still in track 1
						$name = substr($str,$caret+2,$caret2-$caret-2);
						$ret['Name'] = $this->dehexify($name);	
					}
					$pan = $this->dehexify($pan);
					$ret['Last4'] = substr($pan,-4);
					$ret['Issuer'] = PaycardLib::paycard_issuer(str_replace("*","0",$pan));
				}
				elseif ($num == 2){
					$equal = strpos($str,"3D",$pos);
					$pan = substr($str,$pos,$equal-$pos);
					$pan = substr($pan,2); // remove leading ;
					$pan = $this->dehexify($pan);
					$ret['Last4'] = substr($pan,-4);
					$ret['Issuer'] = PaycardLib::paycard_issuer(str_replace("*",0,$pan));
				}
				$pos += $kl*2;
			}

			// if encrypted track 1 present, move past
			if($track_length[1] > 0){
				while($track_length[1] % 8 != 0) $track_length[1]++;
				$pos += ($track_length[1]*2);
			}

			// read encrypted track 2
			if($track_length[2] > 0){
				while($track_length[2] % 8 != 0) $track_length[2]++;
				$ret['Block'] = substr($str,$pos,$track_length[2]*2);
				$pos += ($track_length[2]*2);
			}

			// move past hash 1 if present, hash 2 if present
			if ($track_length[1] > 0)
				$pos += (20*2);
			if ($track_length[2] > 0)
				$pos += (20*2);

			// read key segment
			$ret['Key'] = substr($str,$pos,20);
		}
		return $ret;
	}

	function parsePinBlock($str){
		$ret = array('block'=>'', 'key'=>'');
		if (strlen($str) == 36 && substr($str,0,2) == "FF"){
			// idtech
			$ret['key'] = substr($str,4,16);
			$ret['block'] = substr($str,-16);
		}
		return $ret;
	}

	/*
	  Utility. Convert hex string to ascii characters
	*/
	function dehexify($in){
		if(strlen($in) % 2 != 0) return False;
		$ret = "";
		for($i=0;$i<strlen($in);$i+=2){
			$ret .= chr(hexdec(substr($in,$i,2)));
		}
		return $ret;
	}
}

?>
