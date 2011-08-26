<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

/*
 * Mercury Gift Card processing module
 *
 */

if (!class_exists("BasicCCModule")) include_once($IS4C_PATH."cc-modules/BasicCCModule.php");

if (!class_exists("xmlData")) include_once($IS4C_PATH."lib/xmlData.php");
if (!function_exists("paycard_reset")) include_once($IS4C_PATH."lib/paycardLib.php");
if (!function_exists("receipt")) include_once($IS4C_PATH."lib/clientscripts.php");
if (!function_exists("deptkey")) include_once($IS4C_PATH."lib/prehkeys.php");
if (!function_exists("tDataConnect")) include_once($IS4C_PATH."lib/connect.php");
if (!class_exists("Void")) include_once($IS4C_PATH."parser-class-lib/parse/Void.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

define('MERCURY_TERMINAL_ID',"");
define('MERCURY_PASSWORD',"");

class MercuryGift extends BasicCCModule {
	var $temp;
	var $SOAPACTION = "http://www.mercurypay.com/GiftTransaction";
	var $second_try;
	// BEGIN INTERFACE METHODS

	/* handlesType($type)
	 * $type is a constant as defined in paycardLib.php.
	 * If you class can handle the given type, return
	 * True
	 */
	function handlesType($type){
		if ($type == PAYCARD_TYPE_GIFT) return True;
		else return False;
	}

	/* entered($validate)
	 * This function is called in paycardEntered()
	 * [paycardEntered.php]. This function exists
	 * to move all type-specific handling code out
	 * of the paycard* files
	 */
	function entered($validate,$json){
		global $IS4C_LOCAL,$IS4C_PATH;
		// error checks based on card type
		if( $IS4C_LOCAL->get("gcIntegrate") != 1) { // credit card integration must be enabled
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Card Integration Disabled","Please process gift cards in standalone","[clear] to cancel");
			return $json;
		}

		// error checks based on processing mode
		if( $IS4C_LOCAL->get("paycard_mode") == PAYCARD_MODE_VOID) {
			// use the card number to find the trans_id
			$dbTrans = tDataConnect();
			$today = date('Ymd'); // numeric date only, in an int field
			$pan = $this->getPAN();
			$cashier = $IS4C_LOCAL->get("CashierNo");
			$lane = $IS4C_LOCAL->get("laneno");
			$trans = $IS4C_LOCAL->get("transno");
			$sql = "SELECT transID FROM valutecRequest WHERE [date]=".$today." AND PAN='".$pan."' " .
				"AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans;
			if ($IS4C_LOCAL->get("DBMS") == "mysql"){
				$sql = str_replace("[","",$sql);
				$sql = str_replace("]","",$sql);
			}
			$search = $dbTrans->query($sql);
			$num = $dbTrans->num_rows($search);
			if( $num < 1) {
				$json['output'] = paycard_msgBox(PAYCARD_TYPE_GIFT,"Card Not Used","That card number was not used in this transaction","[clear] to cancel");
				return $json;
			} else if( $num > 1) {
				$json['output'] = paycard_msgBox(PAYCARD_TYPE_GIFT,"Multiple Uses","That card number was used more than once in this transaction; select the payment and press VOID","[clear] to cancel");
				return $json;
			}
			$payment = $dbTrans->fetch_array($search);
			return $this->paycard_void($payment['transID'],$json);
		}

		// check card data for anything else
		if( $validate) {
			if( paycard_validNumber($IS4C_LOCAL->get("paycard_PAN")) != 1) {
				$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Invalid Card Number",
					"Swipe again or type in manually","[clear] to cancel");
				return $json;
			} else if( paycard_accepted($IS4C_LOCAL->get("paycard_PAN"), !paycard_live(PAYCARD_TYPE_GIFT)) != 1) {
				$json['output'] = paycard_msgBox(PAYCARD_TYPE_GIFT,"Unsupported Card Type",
					"We cannot process " . $IS4C_LOCAL->get("paycard_issuer") . " cards","[clear] to cancel");
				return $json;
			}
		}

		// other modes
		switch( $IS4C_LOCAL->get("paycard_mode")) {
		case PAYCARD_MODE_AUTH:
			$IS4C_LOCAL->set("paycard_amount",$IS4C_LOCAL->get("amtdue"));
			$IS4C_LOCAL->set("paycard_id",$IS4C_LOCAL->get("LastID")+1); // kind of a hack to anticipate it this way..
			$json['main_frame'] = $IS4C_PATH.'gui-modules/paycardboxMsgAuth.php';
			return $json;
	
		case PAYCARD_MODE_ACTIVATE:
		case PAYCARD_MODE_ADDVALUE:
			$IS4C_LOCAL->set("paycard_amount",0);
			$IS4C_LOCAL->set("paycard_id",$IS4C_LOCAL->get("LastID")+1); // kind of a hack to anticipate it this way..
			$json['main_frame'] = $IS4C_PATH.'gui-modules/paycardboxMsgGift.php';
			return $json;
	
		case PAYCARD_MODE_BALANCE:
			$json['main_frame'] = $IS4C_PATH.'gui-modules/paycardboxMsgBalance.php';
			return $json;
		} // switch mode
	
		// if we're still here, it's an error
		paycard_reset();
		$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Invalid Mode",
			"This card type does not support that processing mode",
			"[clear] to cancel");
		return $json;
	}

	/* doSend()
	 * Process the paycard request and return
	 * an error value as defined in paycardLib.php.
	 *
	 * On success, return PAYCARD_ERR_OK.
	 * On failure, return anything else and set any
	 * error messages to be displayed in
	 * $IS4C_LOCAL->["boxMsg"].
	 */
	function doSend($type){
		$this->second_try = False;
		switch($type){
		case PAYCARD_MODE_ACTIVATE:
		case PAYCARD_MODE_ADDVALUE:
		case PAYCARD_MODE_AUTH: 
			return $this->send_auth();
		case PAYCARD_MODE_VOID:
		case PAYCARD_MODE_VOIDITEM:
			return $this->send_void();
		case PAYCARD_MODE_BALANCE:
			return $this->send_balance();
		default:
			return $this->setErrorMsg(0);
		}
	}

	/* cleanup()
	 * This function is called when doSend() returns
	 * PAYCARD_ERR_OK. (see paycardAuthorize.php)
	 * I use it for tendering, printing
	 * receipts, etc, but it's really only for code
	 * cleanliness. You could leave this as is and
	 * do all the everything inside doSend()
	 */
	function cleanup($json){
		global $IS4C_LOCAL;
		switch($IS4C_LOCAL->get("paycard_mode")){
		case PAYCARD_MODE_BALANCE:
			$resp = $IS4C_LOCAL->get("paycard_response");
			$IS4C_LOCAL->set("boxMsg","<b>Success</b><font size=-1><p>Gift card balance: $".$resp["Balance"]."<p>\"rp\" to print<br>[enter] to continue</font>");	
			break;
		case PAYCARD_MODE_ADDVALUE:
		case PAYCARD_MODE_ACTIVATE:
			//receipt("gcSlip");
			$IS4C_LOCAL->set("autoReprint",1);
			$ttl = $IS4C_LOCAL->get("paycard_amount");
			deptkey($ttl*100,9020);
			$resp = $IS4C_LOCAL->get("paycard_response");	
			$IS4C_LOCAL->set("boxMsg","<b>Success</b><font size=-1><p>New card balance: $".$resp["Balance"]);
				// reminder to void everything on testgift2, so that it remains inactive to test activations
			$IS4C_LOCAL->set("boxMsg",$IS4C_LOCAL->get("boxMsg")."<p>[enter] to continue<br>\"rp\" to reprint slip</font>");
			break;
		case PAYCARD_MODE_AUTH:
			//receipt("gcSlip");
			$IS4C_LOCAL->set("autoReprint",1);
			tender("GD", ($IS4C_LOCAL->get("paycard_amount")*100));
			$resp = $IS4C_LOCAL->get("paycard_response");
			$IS4C_LOCAL->set("boxMsg","<b>Approved</b><font size=-1><p>Used: $".$IS4C_LOCAL->get("paycard_amount")."<br />New balance: $".$resp["Balance"]);
			// reminder to void everything on testgift2, so that it remains inactive to test activations
			if( !strcasecmp($IS4C_LOCAL->get("paycard_PAN"),"7018525757980004481")) // == doesn't work because the string is numeric and very large, so PHP has trouble turning it into an (int) for comparison
				$IS4C_LOCAL->set("boxMsg",$IS4C_LOCAL->get("boxMsg")."<br><b>REMEMBER TO VOID THIS</b><br>(ask IT for details)");
			$IS4C_LOCAL->set("boxMsg",$IS4C_LOCAL->get("boxMsg")."<p>[enter] to continue<br>\"rp\" to reprint slip<br>[clear] to cancel and void</font>");
			break;
		case PAYCARD_MODE_VOID:
		case PAYCARD_MODE_VOIDITEM:
			//receipt("gcSlip");
			$IS4C_LOCAL->set("autoReprint",1);
			$v = new Void();
			$v->voidid($IS4C_LOCAL->get("paycard_id"));
			$resp = $IS4C_LOCAL->get("paycard_response");
			$IS4C_LOCAL->set("boxMsg","<b>Voided</b><font size=-1><p>New balance: $".$resp["Balance"]."<p>[enter] to continue<br>\"rp\" to reprint slip</font>");
			break;
		}
		return $json;
	}

	/* paycard_void($transID)
	 * Argument is trans_id to be voided
	 * Again, this is for removing type-specific
	 * code from paycard*.php files.
	 */
	function paycard_void($transID,$json=array()){
		global $IS4C_LOCAL,$IS4C_PATH;
		// situation checking
		if( $IS4C_LOCAL->get("gcIntegrate") != 1) { // gift card integration must be enabled
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Card Integration Disabled",
				"Please process gift cards in standalone","[clear] to cancel");
			return $json;
		}
		
		// initialize
		$dbTrans = tDataConnect();
		$today = date('Ymd');
		$cashier = $IS4C_LOCAL->get("CashierNo");
		$lane = $IS4C_LOCAL->get("laneno");
		$trans = $IS4C_LOCAL->get("transno");

		// look up the request using transID (within this transaction)
		$sql = "SELECT live,PAN,mode,amount FROM valutecRequest WHERE [date]=".$today." AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$search = $dbTrans->query($sql);
		$num = $dbTrans->num_rows($search);
		if( $num < 1) {
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Internal Error",
				"Card request not found, unable to void","[clear] to cancel");
			return $json;
		} else if( $num > 1) {
			$json['output'] =  paycard_errBox(PAYCARD_TYPE_GIFT,"Internal Error",
				"Card request not distinct, unable to void","[clear] to cancel");
			return $json;
		}
		$request = $dbTrans->fetch_array($search);

		// look up the response
		$sql = "SELECT commErr,httpCode,validResponse,xAuthorized,
			xAuthorizationCode FROM valutecResponse WHERE [date]=".$today." 
			AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID;
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$search = $dbTrans->query($sql);
		$num = $dbTrans->num_rows($search);
		if( $num < 1) {
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Internal Error",
				"Card response not found, unable to void","[clear] to cancel");
			return $json;
		} else if( $num > 1) {
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Internal Error",
				"Card response not distinct, unable to void","[clear] to cancel");
			return $json;
		}
		$response = $dbTrans->fetch_array($search);

		// look up any previous successful voids
		$sql = "SELECT transID FROM valutecRequestMod WHERE [date]=".$today." AND cashierNo=".$cashier." AND laneNo=".$lane." AND transNo=".$trans." AND transID=".$transID." AND mode='void' AND (xAuthorized='true' or xAuthorized='Appro')";
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$search = $dbTrans->query($sql);
		$voided = $dbTrans->num_rows($search);
		// look up the transaction tender line-item
		$sql = "SELECT trans_type,trans_subtype,trans_status,voided
		       	FROM localtemptrans WHERE trans_id=" . $transID;
		$search = $dbTrans->query($sql);
		$num = $dbTrans->num_rows($search);
		if( $num < 1) {
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Internal Error",
				"Transaction item not found, unable to void","[clear] to cancel");
			return $json;
		} else if( $num > 1) {
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Internal Error",
				"Transaction item not distinct, unable to void","[clear] to cancel");
			return $json;
		}
		$lineitem = $dbTrans->fetch_array($search);

		// make sure the gift card transaction is applicable to void
		if( !$response || $response['commErr'] != 0 || 
		     $response['httpCode'] != 200 || $response['validResponse'] != 1) {
			$json['output'] = paycard_msgBox(PAYCARD_TYPE_GIFT,"Unable to Void",
				"Card transaction not successful","[clear] to cancel");
			return $json;
		} else if( $voided > 0) {
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Unable to Void",
				"Card transaction already voided","[clear] to cancel");
			return $json;
		} else if( $request['live'] != paycard_live(PAYCARD_TYPE_GIFT)) {
			// this means the transaction was submitted to the test platform, but we now think we're in live mode, or vice-versa
			// I can't imagine how this could happen (short of serious $_SESSION corruption), but worth a check anyway.. --atf 7/26/07
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Unable to Void",
				"Processor platform mismatch","[clear] to cancel");
			return $json;
		} else if( $response['xAuthorized'] != 'true'
			&& $response['xAuthorized'] != 'Appro') {
			$json['output'] = paycard_msgBox(PAYCARD_TYPE_GIFT,"Unable to Void",
				"Card transaction not approved","[clear] to cancel");
			return $json;
		} else if( $response['xAuthorizationCode'] < 1) {
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Internal Error",
				"Invalid authorization number","[clear] to cancel");
			return $json;
		}

		// make sure the transaction line-item is applicable to void
		if( $lineitem['trans_status'] == "V" || $lineitem['voided'] != 0) {
			$json['output'] = paycard_errBox(PAYCARD_TYPE_GIFT,"Internal Error",
				"Void records do not match","[clear] to cancel");
			return $json;
		}

		// save the details
		$IS4C_LOCAL->set("paycard_PAN",$request['PAN']);
		if( $request['mode'] == 'refund')
			$IS4C_LOCAL->set("paycard_amount",-$request['amount']);
		else
			$IS4C_LOCAL->set("paycard_amount",$request['amount']);
		$IS4C_LOCAL->set("paycard_id",$transID);
		$IS4C_LOCAL->set("paycard_type",PAYCARD_TYPE_GIFT);
		if( $lineitem['trans_type'] == "T" && $lineitem['trans_subtype'] == "GD") {
			$IS4C_LOCAL->set("paycard_mode",PAYCARD_MODE_VOID);
		} else {
			$IS4C_LOCAL->set("paycard_mode",PAYCARD_MODE_VOIDITEM);
		}
	
		// display FEC code box
		$json['main_frame'] = $IS4C_PATH.'gui-modules/paycardboxMsgVoid.php';
		return $json;
	}

	// END INTERFACE METHODS
	
	function send_auth($domain="w1.mercurypay.com"){
		global $IS4C_LOCAL;
		// initialize
		$dbTrans = tDataConnect();
		if( !$dbTrans)
			return $this->setErrorMsg(PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)

		// prepare data for the request
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $IS4C_LOCAL->get("CashierNo");
		$laneNo = $IS4C_LOCAL->get("laneno");
		$transNo = $IS4C_LOCAL->get("transno");
		$transID = $IS4C_LOCAL->get("paycard_id");
		$program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
		$amount = $IS4C_LOCAL->get("paycard_amount");
		$amountText = number_format(abs($amount), 2, '.', '');
		$mode = "";
		switch( $IS4C_LOCAL->get("paycard_mode")) {
		case PAYCARD_MODE_AUTH:      $mode = (($amount < 0) ? 'refund' : 'tender');  break;
		case PAYCARD_MODE_ADDVALUE:  $mode = 'addvalue';  break;
		case PAYCARD_MODE_ACTIVATE:  $mode = 'activate';  break;
		default:  return $this->setErrorMsg(PAYCARD_ERR_NOSEND);
		}
		$termID = $this->getTermID();
		$password = $this->getPw();
		$live = 0;
		$manual = ($IS4C_LOCAL->get("paycard_manual") ? 1 : 0);
		$cardPAN = $this->getPAN();
		$cardTr2 = $this->getTrack2();
		$identifier = $this->valutec_identifier($transID); // valutec allows 10 digits; this uses lanenum-transnum-transid since we send cashiernum in another field
		
		// store request in the database before sending it
		$sqlColumns =
			"[date],cashierNo,laneNo,transNo,transID," .
			"[datetime],identifier,terminalID,live," . 
			"mode,amount,PAN,manual";
		$sqlValues =
			sprintf("%d,%d,%d,%d,%d,",    $today, $cashierNo, $laneNo, $transNo, $transID) .
			sprintf("'%s','%s','%s',%d,", $now, $identifier, $termID, $live) .
			sprintf("'%s',%s,'%s',%d",    $mode, $amountText, $cardPAN, $manual);
		$sql = "INSERT INTO valutecRequest (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		if( !$dbTrans->query($sql)){
			return $this->setErrorMsg(PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)
		}
                
		// assemble and send request
		$authMethod = "";
		switch( $mode) {
		case 'tender':    $authMethod = 'NoNSFSale';  break;
		case 'refund':
		case 'addvalue':  $authMethod = 'Reload';  break;
		case 'activate':  $authMethod = 'Issue';  break;
		}

		$msgXml = "<?xml version=\"1.0\"?>
			<TStream>
			<Transaction>
			<IpPort>9100</IpPort>
			<MerchantID>$termID</MerchantID>
			<TranType>PrePaid</TranType>
			<TranCode>$authMethod</TranCode>
			<InvoiceNo>$identifier</InvoiceNo>
			<RefNo>$identifier</RefNo>
			<Memo>IS4C (WFC)</Memo>
			<Account>";
		if ($cardTr2)
			$msgXml .= "<Track2>$cardTr2</Track2>";
		else
			$msgXml .= "<AcctNo>$cardPAN</AcctNo>";
		$msgXml .= "</Account>
			<Amount>
			<Purchase>$amountText</Purchase>
			</Amount>
			</Transaction>
			</TStream>";
		

		$soaptext = $this->soapify("GiftTransaction",
			array("tran"=>$msgXml,"pw"=>$password),
			"http://www.mercurypay.com");


		if ($IS4C_LOCAL->get("training") == 1)
			$this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
		else
			$this->GATEWAY = "https://$domain/ws/ws.asmx";

		return $this->curlSend($soaptext,'SOAP');
	}

	function send_void($domain="w1.mercurypay.com"){
		global $IS4C_LOCAL;
		// initialize
		$dbTrans = tDataConnect();
		if( !$dbTrans)
			return $this->setErrorMsg(PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)

		// prepare data for the void request
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $IS4C_LOCAL->get("CashierNo");
		$laneNo = $IS4C_LOCAL->get("laneno");
		$transNo = $IS4C_LOCAL->get("transno");
		$transID = $IS4C_LOCAL->get("paycard_id");
		$program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
		$amount = $IS4C_LOCAL->get("paycard_amount");
		$amountText = number_format(abs($amount), 2, '.', '');
		$mode = 'void';
		$cardPAN = $this->getPAN();
		$identifier = date('mdHis'); // the void itself needs a unique identifier, so just use a timestamp minus the year (10 digits only)
		$termID = $this->getTermID();

		// look up the auth code from the original response 
		// (card number and amount should already be in session vars)
		$sql = "SELECT xAuthorizationCode FROM valutecResponse WHERE [date]='".$today."'" .
			" AND cashierNo=".$cashierNo." AND laneNo=".$laneNo." AND 
			transNo=".$transNo." AND transID=".$transID;
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$search = $dbTrans->query($sql);
		if( !$search || $dbTrans->num_rows($search) != 1)
			return PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
		$log = $dbTrans->fetch_array($search);
		$authcode = $log['xAuthorizationCode'];
		$this->temp = $authcode;
		
		// look up original transaction type
		$sql = "SELECT mode FROM valutecRequest WHERE [date]='".$today."'" .
			" AND cashierNo=".$cashierNo." AND laneNo=".$laneNo." AND 
			transNo=".$transNo." AND transID=".$transID;
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$search = $dbTrans->query($sql);
		if( !$search || $dbTrans->num_rows($search) != 1)
			return PAYCARD_ERR_NOSEND; // database error, nothing sent (ok to retry)
		$row = $dbTrans->fetch_array($search);
		$vdMethod = "";
		switch($row[0]){
		case 'tender': $vdMethod='VoidSale'; break;
		case 'refund': $vdMethod='VoidReturn'; break;
		case 'addvalue': $vdMethod='VoidReload'; break;
		case 'activate': $vdMethod='VoidIssue'; break;
		}
		

		$msgXml = "<?xml version=\"1.0\"?>
			<TStream>
			<Transaction>
			<IpPort>9100</IpPort>
			<MerchantID>$termID</MerchantID>
			<TranType>PrePaid</TranType>
			<TranCode>$vdMethod</TranCode>
			<InvoiceNo>$identifier</InvoiceNo>
			<RefNo>$authcode</RefNo>
			<Memo>IS4C (WFC)</Memo>
			<Account>";
		if ($cardTr2)
			$msgXml .= "<Track2>$cardTr2</Track2>";
		else
			$msgXml .= "<AcctNo>$cardPAN</AcctNo>";
		$msgXml .= "</Account>
			<Amount>
			<Purchase>$amountText</Purchase>
			</Amount>
			</Transaction>
			</TStream>";

		$soaptext = $this->soapify("GiftTransaction",
			array("tran"=>$msgXml,"pw"=>$password),
			"http://www.mercurypay.com");

		if ($IS4C_LOCAL->get("training") == 1)
			$this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
		else
			$this->GATEWAY = "https://$domain/ws/ws.asmx";

		return $this->curlSend($soaptext,'SOAP');
	}

	function send_balance($domain="w1.mercurypay.com"){
		global $IS4C_LOCAL;
		// prepare data for the request
		$cashierNo = $IS4C_LOCAL->get("CashierNo");
		$program = 'Gift'; // valutec also has 'Loyalty' cards which store arbitrary point values
		$cardPAN = $this->getPAN();
		$cardTr2 = $this->getTrack2();
		$identifier = date('mdHis'); // the balance check itself needs a unique identifier, so just use a timestamp minus the year (10 digits only)
		$termID = $this->getTermID();
		$password = $this->getPw();

		$msgXml = "<?xml version=\"1.0\"?>
			<TStream>
			<Transaction>
			<IpPort>9100</IpPort>
			<MerchantID>$termID</MerchantID>
			<TranType>PrePaid</TranType>
			<TranCode>Balance</TranCode>
			<InvoiceNo>$identifier</InvoiceNo>
			<RefNo>$identifier</RefNo>
			<Memo>IS4C (WFC)</Memo>
			<Account>";
		if ($cardTr2)
			$msgXml .= "<Track2>$cardTr2</Track2>";
		else
			$msgXml .= "<AcctNo>$cardPAN</AcctNo>";
		$msgXml .= "</Account>
			</Transaction>
			</TStream>";

		$soaptext = $this->soapify("GiftTransaction",
			array("tran"=>$msgXml,"pw"=>$password),
			"http://www.mercurypay.com");

		//echo $soaptext; exit;
		//$soaptext = str_replace("<pw>$password</pw>","",$soaptext);
		//$soaptext = str_replace("</GiftTransaction>",
		//	"</GiftTransaction><pw>$password</pw>",$soaptext);

		if ($IS4C_LOCAL->get("training") == 1)
			$this->GATEWAY = "https://w1.mercurydev.net/ws/ws.asmx";
		else
			$this->GATEWAY = "https://$domain/ws/ws.asmx";

		return $this->curlSend($soaptext,'SOAP');
	}

	function handleResponse($authResult){
		global $IS4C_LOCAL;
		switch($IS4C_LOCAL->get("paycard_mode")){
		case PAYCARD_MODE_AUTH:
		case PAYCARD_MODE_ACTIVATE:
		case PAYCARD_MODE_ADDVALUE:
			return $this->handleResponseAuth($authResult);
		case PAYCARD_MODE_VOID:
		case PAYCARD_MODE_VOIDITEM:
			return $this->handleResponseVoid($authResult);
		case PAYCARD_MODE_BALANCE:
			return $this->handleResponseBalance($authResult);
		}
	}

	function handleResponseAuth($authResult){
		global $IS4C_LOCAL;
		$resp = $this->desoapify("GiftTransactionResult",
			$authResult["response"]);
		$xml = new xmlData($resp);

		// initialize
		$dbTrans = tDataConnect();
		if( !$dbTrans)
			return $this->setErrorMsg(PAYCARD_ERR_NOSEND); // internal error, nothing sent (ok to retry)

		// prepare data for the request
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $IS4C_LOCAL->get("CashierNo");
		$laneNo = $IS4C_LOCAL->get("laneno");
		$transNo = $IS4C_LOCAL->get("transno");
		$transID = $IS4C_LOCAL->get("paycard_id");
		$program = 'Gift';
		$identifier = $this->valutec_identifier($transID); // valutec allows 10 digits; this uses lanenum-transnum-transid since we send cashiernum in another field

		// prepare some fields to store the parsed response; we'll add more as we verify it
		$sqlColumns =
			"[date],cashierNo,laneNo,transNo,transID," .
			"[datetime],identifier," .
			"seconds,commErr,httpCode";
		$sqlValues =
			sprintf("%d,%d,%d,%d,%d,",  $today, $cashierNo, $laneNo, $transNo, $transID) .
			sprintf("'%s','%s',",       $now, $identifier) .
			sprintf("%f,%d,%d",         $authResult['curlTime'], $authResult['curlErr'], $authResult['curlHTTP']);

		$validResponse = ($xml->isValid()) ? 1 : 0;
		$errorMsg = $xml->get_first("TEXTRESPONSE");
		$balance = $xml->get_first("BALANCE");


		if ($validResponse){
			// verify that echo'd fields match our request
			if( $xml->get('TRANTYPE') && $xml->get('TRANTYPE') == "PrePaid"
				&& $xml->get('INVOICENO') && $xml->get('INVOICENO') == $identifier
				&& $xml->get('CMDSTATUS')
			)
				$validResponse = 1; // response was parsed normally, echo'd fields match, and other required fields are present
			else{
				if (!$xml->get('CMDSTATUS'))
					$validResponse = -2; // response was parsed as XML but fields didn't match
				if (!$xml->get('TRANTYPE'))
					$validResponse = -3; // response was parsed as XML but fields didn't match
				if (!$xml->get('INVOICENO'))
					$validResponse = -4; // response was parsed as XML but fields didn't match
			}

			$sqlColumns .= ",xAuthorized,xAuthorizationCode,xBalance,xErrorMsg";
			$sqlValues .= ",'".substr($xml->get("CMDSTATUS"),0,5)."'";
			$sqlValues .= ",'".$xml->get("REFNO")."'";
			$sqlValues .= ",'".$balance."'";
			$sqlValues .= ",'".str_replace("'","",$errorMsg)."'";
		}

		 // finish storing the response in the database before reacting to it
		$sqlColumns .= ",validResponse";
		$sqlValues .= ",".$validResponse;
		$sql = "INSERT INTO valutecResponse (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$dbTrans->query($sql);

		// check for communication errors (any cURL error or any HTTP code besides 200)
		if( $authResult['curlErr'] != CURLE_OK || $authResult['curlHTTP'] != 200){
			if ($authResult['curlHTTP'] == '0'){
				if (!$this->second_try){
					$this->second_try = True;
					return $this->send_auth("w2.backuppay.com");
				}
				else {
					$IS4C_LOCAL->set("boxMsg","No response from processor<br />
								The transaction did not go through");
					return PAYCARD_ERR_PROC;
				}
			}
			return $this->setErrorMsg(PAYCARD_ERR_COMM);
		}

		 // check for data errors (any failure to parse response XML or echo'd field mismatch
		if( $validResponse != 1) {
			return $this->setErrorMsg(PAYCARD_ERR_DATA); // invalid server response, we don't know if the transaction was processed (use carbon)
		}

		// put the parsed response into $IS4C_LOCAL so the caller, receipt printer, etc can get the data they need
		$IS4C_LOCAL->set("paycard_response",array());
		$IS4C_LOCAL->set("paycard_response",$xml->array_dump());
		$temp = $IS4C_LOCAL->get("paycard_response");
		$temp["Balance"] = $temp["BALANCE"];
		$IS4C_LOCAL->set("paycard_response",$temp);
		if ($xml->get_first("AUTHORIZE")){
			$IS4C_LOCAL->set("paycard_amount",$xml->get_first("AUTHORIZE"));	
			$correctionQ = sprintf("UPDATE valutecRequest SET amount=%f WHERE
				date=%s AND identifier='%s'",
				$xml->get_first("AUTHORIZE"),date("Ymd"),$identifier);
			$dbTrans->query($correctionQ);
		}

		// comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
		if( $xml->get('CMDSTATUS') == 'Approved' && $xml->get('REFNO') != '' ){
			return PAYCARD_ERR_OK; // authorization approved, no error
		}

		// the authorizor gave us some failure code
		$IS4C_LOCAL->set("boxMsg","Processor error: ".$errorMsg);
		return PAYCARD_ERR_PROC; // authorization failed, response fields in $_SESSION["paycard_response"]
	}

	function handleResponseVoid($vdResult){
		global $IS4C_LOCAL;
		$resp = $this->desoapify("GiftTransactionResult",
			$vdResult["response"]);
		$xml = new xmlData($resp);

		// initialize
		$dbTrans = tDataConnect();
		if( !$dbTrans)
			return $this->setErrorMsg(PAYCARD_ERR_NOSEND); // database error, nothing sent (ok to retry)

		// prepare data for the void request
		$today = date('Ymd'); // numeric date only, it goes in an 'int' field as part of the primary key
		$now = date('Y-m-d H:i:s'); // full timestamp
		$cashierNo = $IS4C_LOCAL->get("CashierNo");
		$laneNo = $IS4C_LOCAL->get("laneno");
		$transNo = $IS4C_LOCAL->get("transno");
		$transID = $IS4C_LOCAL->get("paycard_id");
		$amount = $IS4C_LOCAL->get("paycard_amount");
		$amountText = number_format(abs($amount), 2, '.', '');
		$mode = 'void';
		$authcode = $this->temp;
		$program = "Gift";

		$sqlColumns =
			"[date],cashierNo,laneNo,transNo,transID,[datetime]," .
			"mode,origAuthCode," .
			"seconds,commErr,httpCode";
		$sqlValues =
			sprintf("%d,%d,%d,%d,%d,'%s',", $today, $cashierNo, $laneNo, $transNo, $transID, $now) .
			sprintf("'%s','%s',",           $mode, $authcode) .
			sprintf("%f,%d,%d",             $vdResult['curlTime'], $vdResult['curlErr'], $vdResult['curlHTTP']);

		$validResponse = 0;
		// verify that echo'd fields match our request
                if( $xml->get('TRANTYPE') 
                        && $xml->get('CMDSTATUS')
                        && $xml->get('BALANCE')
                )
                        $validResponse = 1; // response was parsed normally, echo'd fields match, and other required fields are present
                else
                        $validResponse = -2; // response was parsed as XML but fields didn't match

		$sqlColumns .= ",xAuthorized,xAuthorizationCode,xBalance,xErrorMsg";
		$sqlValues .= ",'".substr($xml->get("CMDSTATUS"),0,5)."'";
		$sqlValues .= ",'".$xml->get("REFNO")."'";
		$sqlValues .= ",'".$xml->get("BALANCE")."'";
		$sqlValues .= ",'".$xml->get_first("TEXTRESPONSE")."'";
		
		// finish storing the request and response in the database before reacting to it
		$sqlColumns .= ",validResponse";
		$sqlValues .= ",".$validResponse;
		$sql = "INSERT INTO valutecRequestMod (" . $sqlColumns . ") VALUES (" . $sqlValues . ")";
		if ($IS4C_LOCAL->get("DBMS") == "mysql"){
			$sql = str_replace("[","",$sql);
			$sql = str_replace("]","",$sql);
		}
		$dbTrans->query($sql);

		if( $vdResult['curlErr'] != CURLE_OK || $vdResult['curlHTTP'] != 200) {
			if ($authResult['curlHTTP'] == '0'){
				if (!$this->second_try){
					$this->second_try = True;
					return $this->send_void("w2.backuppay.com");
				}
				else {
					$IS4C_LOCAL->set("boxMsg","No response from processor<br />
								The transaction did not go through");
					return PAYCARD_ERR_PROC;
				}
			}
			return $this->setErrorMsg(PAYCARD_ERR_COMM); // comm error, try again
		}
		// check for data errors (any failure to parse response XML or echo'd field mismatch)
		if( $validResponse != 1) {
			return $this->setErrorMsg(PAYCARD_ERR_DATA); // invalid server response, we don't know if the transaction was voided (use carbon)
		}

		// put the parsed response into $IS4C_LOCAL so the caller, receipt printer, etc can get the data they need
		$IS4C_LOCAL->set("paycard_response",array());
		$IS4C_LOCAL->set("paycard_response",$xml->array_dump());
		$temp = $IS4C_LOCAL->get("paycard_response");
		$temp["Balance"] = $temp["BALANCE"];
		$IS4C_LOCAL->set("paycard_response",$temp);

		// comm successful, check the Authorized, AuthorizationCode and ErrorMsg fields
		if( $xml->get('CMDSTATUS') == 'Approved' && $xml->get('REFNO') != '' ){
			return PAYCARD_ERR_OK; // void successful, no error
		}

		// the authorizor gave us some failure code
		$IS4C_LOCAL->set("boxMsg","PROCESSOR ERROR: ".$xml->get_first("ERRORMSG"));
		return PAYCARD_ERR_PROC; 
	}

	function handleResponseBalance($balResult){
		global $IS4C_LOCAL;
		$resp = $this->desoapify("GiftTransactionResult",
			$balResult["response"]);
		$xml = new xmlData($resp);
		$program = 'Gift';

		if( $balResult['curlErr'] != CURLE_OK || $balResult['curlHTTP'] != 200) {
			if ($authResult['curlHTTP'] == '0'){
				if (!$this->second_try){
					$this->second_try = True;
					return $this->send_balance("w2.backuppay.com");
				}
				else {
					$IS4C_LOCAL->set("boxMsg","No response from processor<br />
								The transaction did not go through");
					return PAYCARD_ERR_PROC;
				}
			}
			return $this->setErrorMsg(PAYCARD_ERR_COMM); // comm error, try again
		}

		$IS4C_LOCAL->set("paycard_response",array());
		$IS4C_LOCAL->set("paycard_response",$xml->array_dump());
		$resp = $IS4C_LOCAL->get("paycard_response");
		if (isset($resp["BALANCE"])){
			$resp["Balance"] = $resp["BALANCE"];
			$IS4C_LOCAL->set("paycard_response",$resp);
		}

		// there's less to verify for balance checks, just make sure all the fields are there
		if( $xml->isValid() &&
                        $xml->get('TRANTYPE') && $xml->get('TRANTYPE') == 'PrePaid'
                        && $xml->get('CMDSTATUS') && $xml->get('CMDSTATUS') == 'Approved'
                        && $xml->get('BALANCE')
		) {
			return PAYCARD_ERR_OK; // balance checked, no error
		}

		// the authorizor gave us some failure code
		$IS4C_LOCAL->set("boxMsg","Processor error: ".$xml->get_first("TEXTRESPONSE"));
		return PAYCARD_ERR_PROC; // authorization failed, response fields in $_SESSION["paycard_response"]
	}

	// generate a partially-daily-unique identifier number according to the gift card processor's limitations
	// along with their CashierID field, it will be a daily-unique identifier on the transaction
	function valutec_identifier($transID) {
		global $IS4C_LOCAL;
		$transNo   = (int)$IS4C_LOCAL->get("transno");
		$laneNo    = (int)$IS4C_LOCAL->get("laneno");
		// fail if any field is too long (we don't want to truncate, since that might produce a non-unique refnum and cause bigger problems)
		if( $transID > 999 || $transNo > 999 || $laneNo > 99)
			return "";
		// assemble string
		$ref = "00"; // fill all 10 digits, since they will if we don't and we want to compare with == later
		$ref .= str_pad($laneNo,    2, "0", STR_PAD_LEFT);
		$ref .= str_pad($transNo,   3, "0", STR_PAD_LEFT);
		$ref .= str_pad($transID,   3, "0", STR_PAD_LEFT);
		return $ref;
	} // valutec_identifier()
	
	function getTermID(){
		global $IS4C_LOCAL;
		if ($IS4C_LOCAL->get("training") == 1)
			return "595901";
		else
			return MERCURY_TERMINAL_ID;
	}

	function getPw(){
		global $IS4C_LOCAL;
		if ($IS4C_LOCAL->get("training") == 1){
			return "xyz";
		}
		else
			return MERCURY_PASSWORD;
	}

	function getPAN(){
		global $IS4C_LOCAL;
		if ($IS4C_LOCAL->get("training") == 1)
			return "6050110000000296951";
		else
			return $IS4C_LOCAL->get("paycard_PAN");
	}

	function getTrack2(){
		global $IS4C_LOCAL;
		if ($IS4C_LOCAL->get("training") == 1)
			return False;
		else
			return $IS4C_LOCAL->get("paycard_tr2");
	}
}
