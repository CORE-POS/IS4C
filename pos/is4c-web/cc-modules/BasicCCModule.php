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

/*
 * Generic Credit Card processing module
 *
 * The idea here is to have a general module for
 * processing via HTTPS & curl
 *
 */

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!function_exists("paycard_errorText")) include_once($IS4C_PATH."lib/paycardLib.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class BasicCCModule {
	/* constructor
	 * takes no arguments
	 * otherwise, do whatever you want here
	 */
	function BasicCCModule(){
		
	}

	// BEGIN INTERFACE METHODS

	/* handlesType($type)
	 * $type is a constant as defined in paycardLib.php.
	 * If you class can handle the given type, return
	 * True
	 */
	function handlesType($type){
		return False;
	}

	/* entered($validate)
	 * This function is called in paycardEntered()
	 * [paycardEntered.php]. This function exists
	 * to move all type-specific handling code out
	 * of the paycard* files
	 */
	function entered($validate,$json=array()){
		return False;
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
		return $this->setErrorMsg(0);
	}

	/* cleanup()
	 * This function is called when doSend() returns
	 * PAYCARD_ERR_OK. (see paycardAuthorize.php)
	 * I use it for tendering, printing
	 * receipts, etc, but it's really only for code
	 * cleanliness. You could leave this as is and
	 * do all the everything inside doSend()
	 */
	function cleanup($json=array()){

	}

	/* paycard_void($transID)
	 * Argument is trans_id to be voided
	 * Again, this is for removing type-specific
	 * code from paycard*.php files.
	 */
	function paycard_void($transID){

	}

	// END INTERFACE METHODS
	
	// These are utility methods I found useful
	// in implementing subclasses
	// They don't need to be defined or used. Any class
	// that implements the interface methods above
	// will work modularly

	/* curlSend($data,$type)
	 * Send a curl request with the specified
	 * POST data. The url should be specified in
	 * $this->GATEWAY.
	 * Passes an array containing curl error number,
	 * curl error message, curl processing time, http
	 * code, and response text to the handleResponse()
	 * method
	 *
	 * Valid types: GET, POST, SOAP
	 * Setting xml to True adds an content-type header
	 */
	var $GATEWAY;
	var $SOAPACTION;
	function curlSend($data=False,$type='POST',$xml=False){
		global $IS4C_PATH;
		if($data && $type == 'GET')
			$this->GATEWAY .= $data;

		$curl_handle = curl_init($this->GATEWAY);

		curl_setopt($curl_handle, CURLOPT_HEADER, 0);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT,15);
		curl_setopt($curl_handle, CURLOPT_FAILONERROR,false);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION,false);
		curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT,true);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT,30);
		//curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl_handle, CURLOPT_CAINFO, 
			$IS4C_PATH."cc-modules/cacert.pem");
		if ($type == 'SOAP'){
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER,
				array("SOAPAction: ".$this->SOAPACTION,
				      "Content-type: text/xml"));
		}
		elseif ($xml){
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER,
				array("Content-type: text/xml"));
		}

		if ($data && ($type == 'POST' || $type == 'SOAP'))
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);

		set_time_limit(60);

		$response = curl_exec($curl_handle);

		if ($type == "SOAP"){
			$response = str_replace("&lt;","<",$response);
			$response = str_replace("&gt;",">",$response);
		}

		$funcReturn = array(
			'curlErr' => curl_errno($curl_handle),
			'curlErrText' => curl_error($curl_handle),
			'curlTime' => curl_getinfo($curl_handle,
					CURLINFO_TOTAL_TIME),
			'curlHTTP' => curl_getinfo($curl_handle,
					CURLINFO_HTTP_CODE),
			'response' => $response
		);

		curl_close($curl_handle);

		return $this->handleResponse($funcReturn);
	}

	/* handleResponse($response)
	 * Takes care of data fetched by
	 * $this->curlSend()
	 */
	function handleResponse($response){
		return False;
	}

	/* refnum($transID)
	 * Create a reference number from
	 * session variables. Taken straight from
	 * efsnet.php
	 */
	function refnum($transID){
		global $IS4C_LOCAL;
		$transNo   = (int)$IS4C_LOCAL->get("transno");
		$cashierNo = (int)$IS4C_LOCAL->get("CashierNo");
		$laneNo    = (int)$IS4C_LOCAL->get("laneno");
		// fail if any field is too long (we don't want to truncate, since that might produce a non-unique refnum and cause bigger problems)
		if( $transID > 999 || $transNo > 999 || $laneNo > 99 || $cashierNo > 9999)
			return "";
		// assemble string
		$ref = "";
		$ref .= str_pad($cashierNo, 4, "0", STR_PAD_LEFT);
		$ref .= str_pad($laneNo,    2, "0", STR_PAD_LEFT);
		$ref .= str_pad($transNo,   3, "0", STR_PAD_LEFT);
		$ref .= str_pad($transID,   3, "0", STR_PAD_LEFT);
		return $ref;
	}

	/* array2post($parray)
	 * urlencodes the given array for use with curl
	 */
	function array2post($parray){
		$postData = "";
		foreach($parray as $k=>$v) 
			$postData .= "$k=".urlencode($v)."&";
		$postData = rtrim($postData,"&");
		return $postData;
	}

	// put objects into a soap envelope
	// action is top level tag in the soap body
	// Envelope attributes can be overridden by a subclass
	// if needed
	var $SOAP_ENVELOPE_ATTRS = array(
		"xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\"",
		"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"",
		"xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\""
		);
	function soapify($action,$objs,$namespace=""){
		$ret = "<?xml version=\"1.0\"?>
			<soap:Envelope";
		foreach ($this->SOAP_ENVELOPE_ATTRS as $attr){
			$ret .= " ".$attr;
		}
		$ret .= ">
			<soap:Body>
			<".$action;
		if ($namespace != "")
			$ret .= " xmlns=\"".$namespace."\"";
		$ret .= ">\n";
		foreach($objs as $key=>$value){
			$ret .= "<".$key.">";
			$value = str_replace("<","&lt;",$value);
			$value = str_replace(">","&gt;",$value);
			$ret .= $value;
			$ret .= "</".$key.">\n";
		}
		$ret .= "</".$action.">
			</soap:Body>
			</soap:Envelope>";

		return $ret;
	}

	// extract response from soap envelope
	function desoapify($action,$soaptext){
		preg_match("/<$action.*?>(.*?)<\/$action>/s",
			$soaptext,$groups);
		return $groups[1];
	}

	/* setErrorMsg($errorCode)
	 * Set $IS4C_LOCAL->["boxMsg"] appropriately for
	 * the given error code. I find this easier
	 * than manually setting an appropriate message
	 * every time I return a common error like
	 * PAYCARD_ERR_NOSEND. I think everything but
	 * PAYCARD_ERR_PROC can have one default message
	 * assigned here
	 */
	function setErrorMsg($errorCode){
		global $IS4C_LOCAL;
		switch ($errorCode){
		case PAYCARD_ERR_NOSEND:
			$IS4C_LOCAL->set("boxMsg",paycard_errorText("Internal Error",$errorCode,"",1,1,0,0,1,$IS4C_LOCAL->get("paycard_type")));
			break;
		case PAYCARD_ERR_COMM:
			$IS4C_LOCAL->set("boxMsg",paycard_errorText("Communication Error",$errorCode,"",1,1,0,0,0,$IS4C_LOCAL->get("paycard_type")));
			break;
		case PAYCARD_ERR_TIMEOUT:
			$IS4C_LOCAL->set("boxMsg",paycard_errorText("Timeout Error",$errorCode,"",0,0,0,1,0,$IS4C_LOCAL->get("paycard_type")));
			break;
		case PAYCARD_ERR_DATA:
			$IS4C_LOCAL->set("boxMsg",paycard_errorText("System Error",$errorCode,"",0,0,0,1,1,$IS4C_LOCAL->get("paycard_type")));
			break;
		default:
			$IS4C_LOCAL->set("boxMsg",paycard_errorText("Internal Error",$errorCode,"",1,1,0,0,1,$IS4C_LOCAL->get("paycard_type")));
			break;
		return $errorCode;
		}
	}
}

?>
