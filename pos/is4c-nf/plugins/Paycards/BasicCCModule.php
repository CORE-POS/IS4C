<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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
  @class BasicCCModule
  Generic Credit Card processing module

  All payment processing modules should descend
  from this class. Required methods are:
   - handlesType()
   - doSend()
   - cleanup()

  Additionally, while they technically may be
  omitted in the subclass, it is strongly 
  recommended that module implement its own:
   - entered()
   - paycard_void()

  The rest is utility methods that are often helpful.
 */

if (!class_exists("PaycardLib")) include_once(realpath(dirname(__FILE__)."/lib/PaycardLib.php"));
if (!isset($CORE_LOCAL)) {
	include_once(realpath(dirname(__FILE__)."/lib/LS_Access.php"));
	$CORE_LOCAL = new LS_Access();
}

define("LOCAL_CERT_PATH",realpath(dirname(__FILE__)).'/cacert.pem');

class BasicCCModule 
{

    public $last_ref_num = '';
    public $last_req_id = 0;
    public $last_paycard_transaction_id = 0;

	protected $GATEWAY;
	protected $SOAPACTION = '';

	/**
	  Envelope attributes for SOAP.
	*/
	protected $SOAP_ENVELOPE_ATTRS = array(
		"xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\"",
		"xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"",
		"xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\""
    );

	/** 
	  Constructor
	  takes no arguments
	  otherwise, do whatever you want here
	 */
	function BasicCCModule(){
		
	}

	// BEGIN INTERFACE METHODS

	/** 
	 Check whether module handles this paycard type
	 @param $type the type
	 @return True or False

	 Type constants are defined in paycardLib.php.
	 */
	public function handlesType($type)
    {
		return False;
	}

	/** 
	 Set up transaction and validate if desired
	 @param $validate boolean
	 @param $json A keyed array
	 @return An array see Parser::default_json()
	  for formatting.

	 This function typically does some validation
	 and sets some values in the session. 

	 If you have 'output' defined in the return
	 array, that gets shown as an error message.
	 If you set a URL in 'main_frame', POS
	 might go there but it's not guaranteed.
	 */
	public function entered($validate,$json)
    {
		if (!isset($json['output'])) {
            $json['output'] = '';
        }
		if (!isset($json['main_frame'])) {
            $json['main_frame'] = false;
        }

		return $json;
	}

	/** 
	 Process the paycard request and return
	 an error value as defined in paycardLib.php.
	 @param $type paycard type 
	 @return
	 - On success, return PaycardLib::PAYCARD_ERR_OK.
	 - On failure, return anything else and set any
	   error messages to be displayed in
	   $CORE_LOCAL->["boxMsg"].

	 This function should submit a request to the
	 gateway and process the result. By convention
	 credit card request and response info is stored
	 in the efsnet* tables and gift card request and
	 response info is stored in the valutec* tables.
	
	 <b>Do not store full card number when logging
	 request and response info</b>.
	 */
	public function doSend($type)
    {
		return $this->setErrorMsg(0);
	}

	/**
	  This function is called when doSend() returns
	  PaycardLib::PAYCARD_ERR_OK. 

	  I use it for tendering, printing
	  receipts, etc, but it's really only for code
	  cleanliness. You could leave this as is and
	  do all the everything inside doSend()
	 */
	public function cleanup($json)
    {

	}

	/**
	 Validation and setup for void transactions
	 @param $transID original transaction ID
	 @param $laneNo original transaction laneNo value
	 @param $transNo original transaction transNo value
	 @param $json keyed array
	 @return An array see Parser::default_json() for
	  formatting

	 This function is similar to entered(). Typically
	 with a void there is additional validation to
	 check the status of the original transaction before
	 proceeding.
	*/
	public function paycard_void($transID, $laneNo=-1, $transNo=-1, $json=array())
    {
		if (!isset($json['output'])) {
            $json['output'] = '';
        }
		if (!isset($json['main_frame'])) {
            $json['main_frame'] = false;
        }

		return $json;
	}

    /**
      The given efsnetRequest.refNum value corresponds to the
      format used by this class
      @param $ref [string] efsnetRequest.refNum
      @return [boolean]
    */
    public function myRefNum($ref)
    {
        return false;
    }

    /**
      Lookup transaction status
      @param $ref [string] efsnetRequest.refNum
      @param $local [int]
        1 => transaction is in local efsnetRequest table
        0 => transaction is in server's efsnetRequest table
      @param $mode [string]
        lookup => just fetch information from the processor
        verify => update efsnetResponse with retreived information
      @return [keyed array]
        output => HTML msg to display for the user
        confirm_dest => URL destination if the user presses enter
        cancel_dest => URL destination if the user presses clear
    */
    public function lookupTransaction($ref, $local, $mode)
    {
        return array(
            'output' => DisplayLib::boxMsg('Lookup not available for<br />this processor', '', true),
            'confirm_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
            'cancel_dest' => MiscLib::base_url() . 'gui-modules/pos2.php',
        );
    }

	// END INTERFACE METHODS
	
	// These are utility methods I found useful
	// in implementing subclasses
	// They don't need to be defined or used. Any class
	// that implements the interface methods above
	// will work modularly

	/**
	 Send a curl request with the specified data.
	 @param $data string of data
	 @param $type 'GET', 'POST', or 'SOAP'
	 @param $xml True or False
	 @param $extraOpts array of curl options and values
     @param $auto_handle [boolean]
        true => call handleResponse method automatically
        false => just return curl result
	 @return integer error code
	 
	 The url should be specified in $this->GATEWAY.
	 SOAP requests should aso set $this->$SOAPACTION.

	 Data is usually a string of XML or an HTTP
	 argument like key1=val1&key2=val2...
	 Setting xml to True adds an content-type header

	 This function calls the handleResponse method
	 and returns the result of that call.
	 */
	function curlSend($data=False,$type='POST',$xml=False, $extraOpts=array(), $auto_handle=true)
    {
		global $CORE_LOCAL;
		if($data && $type == 'GET') {
			$this->GATEWAY .= $data;
        }

		$curl_handle = curl_init($this->GATEWAY);

		curl_setopt($curl_handle, CURLOPT_HEADER, 0);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT,15);
		curl_setopt($curl_handle, CURLOPT_FAILONERROR,false);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION,false);
		curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT,true);
		curl_setopt($curl_handle, CURLOPT_TIMEOUT,30);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
        if (MiscLib::win32()) {
			curl_setopt($curl_handle, CURLOPT_CAINFO, LOCAL_CERT_PATH);
        }
		if ($type == 'SOAP') {
			$headers = array();
			if (!empty($this->SOAPACTION)) {
				$headers[] = "SOAPAction: ".$this->SOAPACTION;
            }
			$headers[] = "Content-type: text/xml";
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
		} else if ($xml) {
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER,
				array("Content-type: text/xml"));
		}

		foreach ($extraOpts as $opt => $value) {
			curl_setopt($curl_handle, $opt, $value);
        }

		if ($data && ($type == 'POST' || $type == 'SOAP')) {
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data);
        }

		set_time_limit(60);

		$response = curl_exec($curl_handle);

		// request sent; get rid of PAN info
		$this->setPAN(array());

		if ($type == "SOAP") {
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

        if ($auto_handle) {
            return $this->handleResponse($funcReturn);
        } else {
            return $funcReturn;
        }
	}

	/** 
	 Processes data fetched by $this->curlSend()
	 @param $response is a keyed array with:
	  - curlErr cURL error code
	  - curlErrText cURL error message
	  - curlTime time spent fetching response
	  - curlHTTP response HTTP code
	  - response is the actual text result
	 @return An error code. Constants are specified
	  in paycardLib.php. PaycardLib::PAYCARD_ERR_OK should be
	  return on success.
	 */
	public function handleResponse($response)
    {
		return false;
	}

	/**
	 Create a reference number from
	 session variables.
	 @param $transID current trans_id in localtemptrans
	 @return A string CCCCLLNNNIII where
	  - CCCC is cashier number
	  - LL is lane number
	  - NNN is transaction number
	  - III is transaction ID
	 */
	public function refnum($transID)
    {
		global $CORE_LOCAL;
		$transNo   = (int)$CORE_LOCAL->get("transno");
		$cashierNo = (int)$CORE_LOCAL->get("CashierNo");
		$laneNo    = (int)$CORE_LOCAL->get("laneno");
		// fail if any field is too long (we don't want to truncate, since that might produce a non-unique refnum and cause bigger problems)
		if ($transID > 999 || $transNo > 999 || $laneNo > 99 || $cashierNo > 9999) {
			return "";
        }
		// assemble string
		$ref = "";
		$ref .= str_pad($cashierNo, 4, "0", STR_PAD_LEFT);
		$ref .= str_pad($laneNo,    2, "0", STR_PAD_LEFT);
		$ref .= str_pad($transNo,   3, "0", STR_PAD_LEFT);
		$ref .= str_pad($transID,   3, "0", STR_PAD_LEFT);

		return $ref;
	}

	/** 
	  urlencodes the given array for use with curl
	  @param $parray keyed array
	  @return formatted string
	 */
	public function array2post($parray)
    {
		$postData = "";
		foreach ($parray as $k=>$v) {
			$postData .= "$k=".urlencode($v)."&";
        }
		$postData = rtrim($postData,"&");

		return $postData;
	}

	/** Put objects into a soap envelope
	  @param $action top level tag in the soap body
	  @param $objs keyed array of values	
	  @param $namespace include an xmlns attribute
	  @return soap string
	*/
	public function soapify($action,$objs,$namespace="",$encode_tags=true)
    {
		$ret = "<?xml version=\"1.0\"?>
			<soap:Envelope";
		foreach ($this->SOAP_ENVELOPE_ATTRS as $attr) {
			$ret .= " ".$attr;
		}
		$ret .= ">
			<soap:Body>
			<".$action;
		if ($namespace != "") {
			$ret .= " xmlns=\"".$namespace."\"";
        }
		$ret .= ">\n";
		foreach ($objs as $key=>$value) {
			$ret .= "<".$key.">";
			if ($encode_tags) {
				$value = str_replace("<","&lt;",$value);
				$value = str_replace(">","&gt;",$value);
			}
			$ret .= $value;
			$ret .= "</".$key.">\n";
		}
		$ret .= "</".$action.">
			</soap:Body>
			</soap:Envelope>";

		return $ret;
	}

	/**
	  Extract response from a soap envelope
	  @param $action is the top level tag in the soap body
	  @param $soaptext is the full soap response
	*/
	public function desoapify($action,$soaptext)
    {
		preg_match("/<$action.*?>(.*?)<\/$action>/s",
			$soaptext,$groups);

		return isset($groups[1]) ? $groups[1] : "";
	}

	/** 
	  @param $errorCode error code contstant from paycardLib.php

	  Set $CORE_LOCAL->["boxMsg"] appropriately for
	  the given error code. I find this easier
	  than manually setting an appropriate message
	  every time I return a common error like
	  PaycardLib::PAYCARD_ERR_NOSEND. I think everything but
	  PaycardLib::PAYCARD_ERR_PROC can have one default message
	  assigned here
	 */
	public function setErrorMsg($errorCode)
    {
		global $CORE_LOCAL;
		switch ($errorCode) {
            case PaycardLib::PAYCARD_ERR_NOSEND:
                $CORE_LOCAL->set("boxMsg",
                                 PaycardLib::paycard_errorText("Internal Error",
                                                               $errorCode,
                                                               "",
                                                               1,
                                                               1,
                                                               0,
                                                               0,
                                                               1,
                                                               $CORE_LOCAL->get("paycard_type")
                                 )
                );
                break;
            case PaycardLib::PAYCARD_ERR_COMM:
                $CORE_LOCAL->set("boxMsg",
                                 PaycardLib::paycard_errorText("Communication Error",
                                                               $errorCode,
                                                               "",
                                                               1,
                                                               1,
                                                               0,
                                                               0,
                                                               0,
                                                               $CORE_LOCAL->get("paycard_type")
                                 )
                );
                break;
            case PaycardLib::PAYCARD_ERR_TIMEOUT:
                $CORE_LOCAL->set("boxMsg",
                                 PaycardLib::paycard_errorText("Timeout Error",
                                                               $errorCode,
                                                               "",
                                                               0,
                                                               0,
                                                               0,
                                                               1,
                                                               0,
                                                               $CORE_LOCAL->get("paycard_type")
                                 )
                );
                break;
            case PaycardLib::PAYCARD_ERR_DATA:
                $CORE_LOCAL->set("boxMsg",
                                 PaycardLib::paycard_errorText("System Error",
                                                               $errorCode,
                                                               "",
                                                               0,
                                                               0,
                                                               0,
                                                               1,
                                                               1,
                                                               $CORE_LOCAL->get("paycard_type")
                                 )
                );
                break;
            default:
                $CORE_LOCAL->set("boxMsg",
                                 PaycardLib::paycard_errorText("Internal Error",
                                                               $errorCode,
                                                               "",
                                                               1,
                                                               1,
                                                               0,
                                                               0,
                                                               1,
                                                               $CORE_LOCAL->get("paycard_type")
                                 )
                );
                break;
		}

        return $errorCode;
	}

	protected $trans_pan;
	/**
	  Store card data in class member $trans_pan.
	  @param $in is a keyed array:
	  - pan is the card number
	  - tr1 is magnetic stripe track 1, if available
	  - tr2 is magnetic stripe track 2, if available
	  - tr3 is magnetic stripe track 3, if available

	  Recommended for credit card modules. Card data
	  can be populated at the last possible moment
	  before calling doSend and expunged again once
	  the request has been submitted to the gateway.
	*/
	public function setPAN($in)
    {
		$this->trans_pan = $in;
	}
}

