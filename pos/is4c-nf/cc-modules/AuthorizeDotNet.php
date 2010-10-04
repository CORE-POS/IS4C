<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/


if (!class_exists("BasicCCModule")) include_once($_SERVER["DOCUMENT_ROOT"]."/cc-modules/BasicCCModule.php");

if (!class_exists("xmlData")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/xmlData.php");
if (!class_exists("Void")) include_once($_SERVER["DOCUMENT_ROOT"]."/parser-class-lib/parse/Void.php");
if (!function_exists("truncate2")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/lib.php");
if (!function_exists("paycard_reset")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/paycardLib.php");
if (!function_exists("tDataConnect")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/connect.php");
if (!function_exists("tender")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/prehkeys.php");
if (!function_exists("receipt")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/clientscripts.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

define('AUTHDOTNET_LOGIN','6Jc5c8QcB');
define('AUTHDOTNET_TRANS_KEY','68j46u5S3RL4CCbX');

class AuthorizeDotNet extends BasicCCModule {

	function handlesType($type){
		if ($type == PAYCARD_TYPE_CREDIT) return True;
		else return False;
	}

	function entered($validate){
		global $IS4C_LOCAL;
		// error checks based on card type
		if( $IS4C_LOCAL->get("CCintegrate") != 1) { // credit card integration must be enabled
			return paycard_errBox(PAYCARD_TYPE_GIFT,
				"Card Integration Disabled",
				"Please process credit cards in standalone",
				"[clear] to cancel");
		}

		// error checks based on processing mode
		switch( $IS4C_LOCAL->get("paycard_mode")) {
		case PAYCARD_MODE_VOID:
			// use the card number to find the trans_id
			$dbTrans = tDataConnect();
			$today = date('Ymd');
			$pan4 = substr($IS4C_LOCAL->get("paycard_PAN"),-4);
			$cashier = $IS4C_LOCAL->get("CashierNo");
			$lane = $IS4C_LOCAL->get("laneno");
			$trans = $IS4C_LOCAL->get("transno");
			$sql = "SELECT transID FROM efsnetRequest WHERE [date]='".$today."' AND (PAN LIKE '%".$pan4."') " .
				"AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans;
			if ($IS4C_LOCAL->get("DBMS") == "mysql"){
				$sql = str_replace("[","",$sql);
				$sql = str_replace("]","",$sql);
			}
			$search = $dbTrans->query($sql);
			$num = $dbTrans->num_rows($search);
			if( $num < 1) {
				return paycard_msgBox(PAYCARD_TYPE_CREDIT,"Card Not Used",
					"That card number was not used in this transaction","[clear] to cancel");
			} else if( $num > 1) {
				return paycard_msgBox(PAYCARD_TYPE_CREDIT,"Multiple Uses",
					"That card number was used more than once in this transaction; select the payment and press VOID","[clear] to cancel");
			}
			$payment = $dbTrans->fetch_array($search);
			return $this->paycard_void($payment['transID']);

		case PAYCARD_MODE_AUTH:
			if( $validate) {
				if( paycard_validNumber($IS4C_LOCAL->get("paycard_PAN")) != 1) {
					return paycard_errBox(PAYCARD_TYPE_CREDIT,
						"Invalid Card Number",
						"Swipe again or type in manually",
						"[clear] to cancel");
				} else if( paycard_accepted($IS4C_LOCAL->get("paycard_PAN"), !paycard_live(PAYCARD_TYPE_CREDIT)) != 1) {
					return paycard_msgBox(PAYCARD_TYPE_CREDIT,
						"Unsupported Card Type",
						"We cannot process " . $IS4C_LOCAL->get("paycard_issuer") . " cards",
						"[clear] to cancel");
				} else if( paycard_validExpiration($IS4C_LOCAL->get("paycard_exp")) != 1) {
					return paycard_errBox(PAYCARD_TYPE_CREDIT,
						"Invalid Expiration Date",
						"The expiration date has passed or was not recognized",
						"[clear] to cancel");
				}
			}
			// set initial variables
			getsubtotals();
			$IS4C_LOCAL->set("paycard_amount",$IS4C_LOCAL->get("amtdue"));
			$IS4C_LOCAL->set("paycard_id",$IS4C_LOCAL->get("LastID")+1); // kind of a hack to anticipate it this way..
			changeCurrentPage('/gui-modules/paycardboxMsgAuth.php');
			return True;
		} // switch mode
	
		// if we're still here, it's an error
		return paycard_errBox(PAYCARD_TYPE_CREDIT,"Invalid Mode",
			"This card type does not support that processing mode","[clear] to cancel");

	}

	function paycard_void($transID) {
		global $IS4C_LOCAL;
		// situation checking
		if( $IS4C_LOCAL->get("CCintegrate") != 1) { // credit card integration must be enabled
			return paycard_errBox(PAYCARD_TYPE_CREDIT,
				"Card Integration Disabled",
				"Please process credit cards in standalone",
				"[clear] to cancel");
		}
	
		// initialize
		$dbTrans = tDataConnect();
		$today = date('Ymd');
		$cashier = $IS4C_LOCAL->get("CashierNo");
		$lane = $IS4C_LOCAL->get("laneno");
		$trans = $IS4C_LOCAL->get("transno");
	
		// look up the request using transID (within this transaction)
		$sql = "SELECT * FROM efsnetRequest WHERE [date]='".$today."' AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$search = $dbTrans->query($sql);
		$num = $dbTrans->num_rows($search);
		if( $num < 1) {
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Internal Error",
				"Card request not found, unable to void","[clear] to cancel");
		} else if( $num > 1) {
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Internal Error",
				"Card request not distinct, unable to void","[clear] to cancel");
		}
		$request = $dbTrans->fetch_array($search);

		// look up the response
		$sql = "SELECT * FROM efsnetResponse WHERE [date]='".$today."' AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$search = $dbTrans->query($sql);
		$num = $dbTrans->num_rows($search);
		if( $num < 1) {
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Internal Error",
				"Card response not found, unable to void","[clear] to cancel");
		} else if( $num > 1) {
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Internal Error",
				"Card response not distinct, unable to void","[clear] to cancel");
		}
		$response = $dbTrans->fetch_array($search);

		// look up any previous successful voids
		$sql = "SELECT * FROM efsnetRequestMod WHERE [date]=".$today." AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID
				." AND mode='void' AND xResponseCode=0";
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$search = $dbTrans->query($sql);
		$voided = $dbTrans->num_rows($search);
		// look up the transaction tender line-item
		$sql = "SELECT * FROM localtemptrans WHERE trans_id=" . $transID;
		$search = $dbTrans->query($sql);
		$num = $dbTrans->num_rows($search);
		if( $num < 1) {
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Internal Error",
				"Transaction item not found, unable to void","[clear] to cancel");
		} else if( $num > 1) {
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Internal Error",
				"Transaction item not distinct, unable to void","[clear] to cancel");
		}
		$lineitem = $dbTrans->fetch_array($search);

		// make sure the payment is applicable to void
		if( $response['commErr'] != 0 || $response['httpCode'] != 200 || $response['validResponse'] != 1) {
			return paycard_msgBox(PAYCARD_TYPE_CREDIT,"Unable to Void",
				"Card transaction not successful","[clear] to cancel");
		} else if( $voided > 0) {
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Unable to Void",
				"Card transaction already voided","[clear] to cancel");
		} else if( $request['live'] != paycard_live(PAYCARD_TYPE_CREDIT)) {
			// this means the transaction was submitted to the test platform, but we now think we're in live mode, or vice-versa
			// I can't imagine how this could happen (short of serious $_SESSION corruption), but worth a check anyway.. --atf 7/26/07
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Unable to Void",
				"Processor platform mismatch","[clear] to cancel");
		} else if( $response['xResponseCode'] != 1) {
			return paycard_msgBox(PAYCARD_TYPE_CREDIT,"Unable to Void",
				"Credit card transaction not approved<br>The result code was " . $response['xResponseCode'],"[clear] to cancel");
		} else if( $response['xTransactionID'] < 1) {
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Internal Error",
				"Invalid reference number","[clear] to cancel");
		}

		// make sure the tender line-item is applicable to void
		if( $lineitem['trans_type'] != "T" || $lineitem['trans_subtype'] != "CC" ){
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Internal Error",
				"Authorization and tender records do not match $transID","[clear] to cancel");
		} else if( $lineitem['trans_status'] == "V" || $lineitem['voided'] != 0) {
			return paycard_errBox(PAYCARD_TYPE_CREDIT,"Internal Error",
				"Void records do not match","[clear] to cancel");
		}
	
		// save the details
		$IS4C_LOCAL->set("paycard_PAN",$request['PAN']);
		$IS4C_LOCAL->set("paycard_amount",(($request['mode']=='refund') ? -1 : 1) * $request['amount']);
		$IS4C_LOCAL->set("paycard_id",$transID);
		$IS4C_LOCAL->set("paycard_type",PAYCARD_TYPE_CREDIT);
		$IS4C_LOCAL->set("paycard_mode",PAYCARD_MODE_VOID);
		$IS4C_LOCAL->set("paycard_name",$request["name"]);
	
		// display FEC code box
		$IS4C_LOCAL->set("inputMasked",1);
		changeBothPages('/gui-modules/input.php',
			'/gui-modules/paycardboxMsgVoid.php');
		return False;
	}

	function handleResponse($authResult){
		global $IS4C_LOCAL;
		switch($IS4C_LOCAL->get("paycard_mode")){
		case PAYCARD_MODE_AUTH:
			return $this->handleResponseAuth($authResult);
		case PAYCARD_MODE_VOID:
			return $this->handleResponseVoid($authResult);
		}
	}

	function handleResponseAuth($authResult){
		global $IS4C_LOCAL;
		$xml = new xmlData($authResult['response']);
		// prepare some fields to store the parsed response; we'll add more as we verify it
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $IS4C_LOCAL->get("CashierNo");
		$laneNo = $IS4C_LOCAL->get("laneno");
		$transNo = $IS4C_LOCAL->get("transno");
		$transID = $IS4C_LOCAL->get("paycard_id");
		$sqlColumns =
			"[date],cashierNo,laneNo,transNo,transID," .
			"[datetime]," .
			"seconds,commErr,httpCode";
		$sqlValues =
			sprintf("%d,%d,%d,%d,%d,",  $today, $cashierNo, $laneNo, $transNo, $transID) .
			sprintf("'%s',",            $now ) .
			sprintf("%f,%d,%d",         $authResult['curlTime'], $authResult['curlErr'], $authResult['curlHTTP']);
		$validResponse = ($xml->isValid()) ? 1 : 0;

		$refNum = $xml->get("USERREF");
		if ($refNum){
			$sqlColumns .= ",refNum";
			$sqlValues .= sprintf(",'%s'",$refNum);
		}
		$responseCode = $xml->get("RESPONSECODE");
		if ($responseCode){
			$sqlColumns .= ",xResponseCode";
			$sqlValues .= sprintf(",%d",$responseCode);
		}
		else $validResponse = -3;
		$resultCode = $xml->get_first("CODE");
		if ($resultCode){
			$sqlColumns .= ",xResultCode";
			$sqlValues .= sprintf(",%d",$resultCode);
		}
		$resultMsg = $xml->get_first("DESCRIPTION");
		if ($resultMsg){
			$sqlColumns .= ",xResultMessage";
			$sqlValues .= sprintf(",'%s'",$resultMsg);
		}
		$xTransID = $xml->get("TRANSID");
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
		else $validResponse = -3;
		$sqlColumns .= ",validResponse";
		$sqlValues .= sprintf(",%d",$validResponse);

		$dbTrans = tDataConnect();
		$sql = "INSERT INTO efsnetResponse (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$dbTrans->query($sql);

		if( $authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
			return $this->setErrorMsg(PAYCARD_ERR_COMM);
		}

		switch ($xml->get("RESPONSECODE")){
			case 1: // APPROVED
			//	$_SESSION["msgrepeat"] = 1;
			//	$_SESSION["strRemembered"] = ($this->AMOUNT*100)."CC";
				return PAYCARD_ERR_OK;
			case 2: // DECLINED
				$IS4C_LOCAL->set("boxMsg","Transaction declined");
				if ($xml->get_first("ERRORCODE") == 4)
					$IS4C_LOCAL->set("boxMsg",$IS4C_LOCAL.get("boxMsg")."<br />Pick up card)");
				break;
			case 3: // ERROR
				$IS4C_LOCAL->set("boxMsg","");
				$codes = $xml->get("ERRORCODE");
				$texts = $xml->get("ERRORTEXT");
				if (!is_array($codes))
					$IS4C_LOCAL->set("boxMsg","EC$codes: $texts");
				else{
					for($i=0; $i<count($codes);$i++){
						$IS4C_LOCAl->set("boxMsg",$IS4C_LOCAL->get("boxMsg")."EC".$codes[$i].": ".$texts[$i]);
						if ($i != count($codes)-1) 
							$IS4C_LOCAL->set("boxMsg",$IS4C_LOCAL->get("boxMsg")."<br />");
					}
				}
				break;
			default:
				$IS4C_LOCAL->set("boxMsg","An unknown error occurred<br />at the gateway");
		}
		return PAYCARD_ERROR_PROC;
	}

	function handleResponseVoid($authResult){
		global $IS4C_LOCAL;
		$xml = new xmlData($authResult['response']);
		// prepare some fields to store the parsed response; we'll add more as we verify it
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $IS4C_LOCAL->get("CashierNo");
		$laneNo = $IS4C_LOCAL->get("laneno");
		$transNo = $IS4C_LOCAL->get("transno");
		$transID = $IS4C_LOCAL->get("paycard_id");
		$amount = $IS4C_LOCAL->get("paycard_amount");
		$amountText = number_format(abs($amount), 2, '.', '');

		// prepare some fields to store the request and the parsed response; we'll add more as we verify it
		$sqlColumns =
			"[date],cashierNo,laneNo,transNo,transID,[datetime]," .
			"origAmount,mode,altRoute," .
			"seconds,commErr,httpCode";
		$sqlValues =
			sprintf("%d,%d,%d,%d,%d,'%s',",  $today, $cashierNo, $laneNo, $transNo, $transID, $now) .
			sprintf("%s,'%s',%d,",  $amountText, "VOID", 0) .
			sprintf("%f,%d,%d",              $authResult['curlTime'], $authResult['curlErr'], $authResult['curlHTTP']);

		$validResponse = ($xml->isValid()) ? 1 : 0;

		$refNum = $xml->get("USERREF");
		if ($refNum){
			$sqlColumns .= ",origRefNum";
			$sqlValues .= sprintf(",'%s'",$refNum);
		}
		$responseCode = $xml->get("RESPONSECODE");
		if ($responseCode){
			$sqlColumns .= ",xResponseCode";
			$sqlValues .= sprintf(",%d",$responseCode);
		}
		else $validResponse = -3;
		$resultCode = $xml->get_first("CODE");
		if ($resultCode){
			$sqlColumns .= ",xResultCode";
			$sqlValues .= sprintf(",%d",$resultCode);
		}
		$resultMsg = $xml->get_first("DESCRIPTION");
		if ($resultMsg){
			$sqlColumns .= ",xResultMessage";
			$sqlValues .= sprintf(",'%s'",$resultMsg);
		}
		$refID = $xml->get("REFTRANSID");
		if ($refID){
			$sqlColumns .= ",origTransactionID";
			$sqlValues .= sprintf(",'%s'",$refID);
		}
		$sqlColumns .= ",validResponse";
		$sqlValues .= sprintf(",%d",$validResponse);

		$dbTrans = tDataConnect();
		$sql = "INSERT INTO efsnetRequestMod (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$dbTrans->query($sql);

		if( $authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
			return $this->setErrorMsg(PAYCARD_ERR_COMM);
		}

		switch ($xml->get("RESPONSECODE")){
			case 1: // APPROVED
			//	$_SESSION["msgrepeat"] = 1;
			//	$_SESSION["strRemembered"] = ($this->AMOUNT*100)."CC";
				return PAYCARD_ERR_OK;
			case 2: // DECLINED
				$IS4C_LOCAL->set("boxMsg","Transaction declined");
				if ($xml->get_first("ERRORCODE") == 4)
					$IS4C_LOCAL->set("boxMsg",$IS4C_LOCAL->get("boxMsg")."<br />Pick up card");
				break;
			case 3: // ERROR
				$IS4C_LOCAL->set("boxMsg","");
				$codes = $xml->get("ERRORCODE");
				$texts = $xml->get("ERRORTEXT");
				if (!is_array($codes))
					$IS4C_LOCAL->set("boxMsg","EC$codes: $texts");
				else{
					for($i=0; $i<count($codes);$i++){
						$IS4C_LOCAL->set("boxMsg",$IS4C_LOCAL->get("boxMsg")."EC".$codes[$i].": ".$texts[$i]);
						if ($i != count($codes)-1) 
							$IS4C_LOCAL->set("boxMsg",$IS4C_LOCAL->get("boxMsg")."<br />");
					}
				}
				break;
			default:
				$IS4C_LOCAL->set("boxMsg","An unknown error occurred<br />at the gateway");
		}
		return PAYCARD_ERROR_PROC;
	}

	function cleanup(){
		global $IS4C_LOCAL;
		switch($IS4C_LOCAL->get("paycard_mode")){
		case PAYCARD_MODE_AUTH:
			$IS4C_LOCAL->set("ccTender",1); 
			tender("CC", ($IS4C_LOCAL->get("paycard_amount")*100));
			$IS4C_LOCAL->set("boxMsg","<b>Approved</b><font size=-1><p>Please verify cardholder signature<p>[enter] to continue<br>\"rp\" to reprint slip<br>[clear] to cancel and void</font>");
			break;
		case PAYCARD_MODE_VOID:
			$v = new Void();
			$v->voidid($IS4C_LOCAL->get("paycard_id"));
			$IS4C_LOCAL->set("boxMsg","<b>Voided</b><p><font size=-1>[enter] to continue<br>\"rp\" to reprint slip</font>");
			break;	
		}
		receipt("ccSlip");
	}

	function doSend($type){
		switch($type){
		case PAYCARD_MODE_AUTH: return $this->send_auth();
		case PAYCARD_MODE_VOID: return $this->send_void(); 
		default:
			return $this->setErrorMsg(0);
		}
	}	

	function send_auth(){
		global $IS4C_LOCAL;
		// initialize
		$dbTrans = tDataConnect();
		if( !$dbTrans)
			return $this->setErrorMsg(PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)

		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $IS4C_LOCAL->get("CashierNo");
		$laneNo = $IS4C_LOCAL->get("laneno");
		$transNo = $IS4C_LOCAL->get("transno");
		$transID = $IS4C_LOCAL->get("paycard_id");
		$amount = $IS4C_LOCAL->get("paycard_amount");
		$amountText = number_format(abs($amount), 2, '.', '');
		$mode = (($amount < 0) ? 'refund' : 'tender');
		$manual = ($IS4C_LOCAL->get("paycard_manual") ? 1 : 0);
		$cardPAN = $IS4C_LOCAL->get("paycard_PAN");
		$cardPANmasked = paycard_maskPAN($cardPAN,0,4);
		$cardIssuer = $IS4C_LOCAL->get("paycard_issuer");
		$cardExM = substr($IS4C_LOCAL->get("paycard_exp"),0,2);
		$cardExY = substr($IS4C_LOCAL->get("paycard_exp"),2,2);
		$cardTr1 = $IS4C_LOCAL->get("paycard_tr1");
		$cardTr2 = $IS4C_LOCAL->get("paycard_tr2");
		$cardName = $IS4C_LOCAL->get("paycard_name");
		$refNum = $this->refnum($transID);
		$live = 1;

		// x_login & x_tran_key need to be
		// filled in to work
		$postValues = array(
		"x_login"	=> AUTHDOTNET_LOGIN,
		"x_tran_key"	=> AUTHDOTNET_TRANS_KEY,
		"x_market_type"	=> "2",
		"x_device_type"	=> "5",
		"cp_version"	=> "1.0",
		"x_test_request"=> "0",
		"x_amount"	=> $amount,
		"x_user_ref"	=> $refNum
		);
		if ($IS4C_LOCAL->get("training") == 1)
			$postValues["x_test_request"] = "1";

		if ($mode == "refund")
			$postValues["x_type"] = "CREDIT";
		else
			$postValues["x_type"] = "AUTH_CAPTURE";

		$sqlCols = "sentPAN,sentExp,sentTr1,sentTr2";
		$sqlVals = "";
		if ((!$cardTr1 && !$cardTr2) || $mode == "refund"){
			$postValues["x_card_num"] = $cardPAN;
			$postValues["x_exp_date"] = $cardExM.$cardExY;
			$sqlVals = "1,1,0,0";
		}
		elseif ($cardTr1){
			$postValues["x_track1"] = $cardTr1;
			$sqlVals = "0,0,1,0";
		}
		elseif ($cardTr2){
			$postValues["x_track2"] = $cardTr2;
			$sqlVals = "0,0,0,1";
		}

		// store request in the database before sending it
		$sqlCols .= "," . // already defined some sent* columns
			"[date],cashierNo,laneNo,transNo,transID," .
			"[datetime],refNum,live,mode,amount," .
			"PAN,issuer,manual,name";
		$sqlVals .= "," . // already defined some sent* values
			sprintf("%d,%d,%d,%d,%d,",        $today, $cashierNo, $laneNo, $transNo, $transID) .
			sprintf("'%s','%s',%d,'%s',%s,",  $now, $refNum, $live, $mode, $amountText) .
			sprintf("'%s','%s',%d,'%s'",           $cardPANmasked, $cardIssuer, $manual, $name);
		$sql = "INSERT INTO efsnetRequest (" . $sqlCols . ") VALUES (" . $sqlVals . ")";
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}

		if( !$dbTrans->query($sql) ) 
			return $this->setErrorMsg(PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)

		$postData = $this->array2post($postValues);
		$this->GATEWAY = "https://test.authorize.net/gateway/transact.dll";
		return $this->curlSend($postData);
	}

	function send_void(){
		global $IS4C_LOCAL;
		// initialize
		$dbTrans = tDataConnect();
		if( !$dbTrans)
			return $this->setErrorMsg(PAYCARD_ERR_NOSEND);

		// prepare data for the void request
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // new timestamp
		$cashierNo = $IS4C_LOCAL->get("CashierNo");
		$laneNo = $IS4C_LOCAL->get("laneno");
		$transNo = $IS4C_LOCAL->get("transno");
		$transID = $IS4C_LOCAL->get("paycard_id");
		$amount = $IS4C_LOCAL->get("paycard_amount");
		$amountText = number_format(abs($amount), 2, '.', '');
		$mode = 'void';
		$manual = ($IS4C_LOCAL->get("paycard_manual") ? 1 : 0);
		$cardPAN = $IS4C_LOCAL->get("paycard_PAN");
		$cardPANmasked = paycard_maskPAN($cardPAN,0,4);
		$cardIssuer = $IS4C_LOCAL->get("paycard_issuer");
		$cardExM = substr($IS4C_LOCAL->get("paycard_exp"),0,2);
		$cardExY = substr($IS4C_LOCAL->get("paycard_exp"),2,2);
		$cardTr1 = $IS4C_LOCAL->get("paycard_tr1");
		$cardTr2 = $IS4C_LOCAL->get("paycard_tr2");
		$cardName = $IS4C_LOCAL->get("paycard_name");
		$refNum = $this->refnum($transID);
		$live = 1;

		// x_login and x_tran_key need to
		// be filled in to work
		$postValues = array(
		"x_login"	=> AUTHDOTNET_LOGIN,
		"x_tran_key"	=> AUTHDOTNET_TRANS_KEY,
		"x_market_type"	=> "2",
		"x_device_type"	=> "5",
		"cp_version"	=> "1.0",
		"x_text_request"=> "1",
		"x_amount"	=> $amount,
		"x_user_ref"	=> $refNum,
		"x_type"	=> "VOID",
		"x_card_num"	=> $cardPAN,
		"x_exp_date"	=> $cardExM.$cardExY
		);

		// look up the TransactionID from the original response (card number and amount should already be in session vars)
		$sql = "SELECT xTransactionID FROM efsnetResponse WHERE [date]='".$today."'" .
			" AND cashierNo=".$cashierNo." AND laneNo=".$laneNo." AND transNo=".$transNo." AND transID=".$transID;
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$result = $dbTrans->query($sql);
		if( !$result || $dbTrans->num_rows($result) != 1)
			return $this->setErrorMsg(PAYCARD_ERR_NOSEND); 
		$res = $dbTrans->fetch_array($result);
		$TransactionID = $res['xTransactionID'];

		$postValues["x_ref_trans_id"] = $TransactionID;

		$postData = $this->array2post($postValues);
		$this->GATEWAY = "https://test.authorize.net/gateway/transact.dll";
		return $this->curlSend($postData);
	}
}

?>
