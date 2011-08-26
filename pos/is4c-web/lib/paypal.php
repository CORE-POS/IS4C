<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
 // session_start();
 
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

define("PAYPAL_TEST_URL","https://api-3t.sandbox.paypal.com/nvp");
define("PAYPAL_TEST_RD","https://www.sandbox.paypal.com/webscr");

define("PAYPAL_LIVE_URL","https://api-3t.paypal.com/nvp");
define("PAYPAL_LIVE_RD","https://www.paypal.com/webscr");

define("PAYPAL_NVP_VERSION",63);
define("PAYPAL_LIVE",True);

include_once($IS4C_PATH."lib/pp-api-credentials.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

/* utility to transform array to url-encoded
   argument string */
function argstring($arr){
	$ret = "";
	foreach($arr as $key=>$val){
		$ret .= $key."=".urlencode($val)."&";
	}
	return substr($ret,0,strlen($ret)-1);
}

/* set up arguments required on
   all paypal NVP requests */
function pp_init_args($method){
	$args = array(
		'METHOD'	=> $method,
		'USER'		=> (PAYPAL_LIVE ? PAYPAL_LIVE_UID : PAYPAL_TEST_UID),
		'PWD'		=> (PAYPAL_LIVE ? PAYPAL_LIVE_PWD : PAYPAL_TEST_PWD),
		'SIGNATURE'	=> (PAYPAL_LIVE ? PAYPAL_LIVE_KEY : PAYPAL_TEST_KEY),
		'VERSION'	=> PAYPAL_NVP_VERSION,
	);
	return $args;
}

/* submit request to paypal nvp, return
   results as keyed array */
function pp_do_curl($args){
	$curl_handle = curl_init((PAYPAL_LIVE ? PAYPAL_LIVE_URL : PAYPAL_TEST_URL));
	curl_setopt($curl_handle,CURLOPT_POST,True);
	curl_setopt($curl_handle,CURLOPT_POSTFIELDS,argstring($args));
	curl_setopt($curl_handle, CURLOPT_HEADER, 0);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT,15);
	curl_setopt($curl_handle, CURLOPT_FAILONERROR,False);
	curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION,False);
	curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT,True);
	curl_setopt($curl_handle, CURLOPT_TIMEOUT,30);
	//curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);

	$response = curl_exec($curl_handle);
	$result = array();
	parse_str($response,$result);
	return $result;
}

/* Set up express checkout request
   NOTE: redirects to paypal on success

   Returns paypal token on success, False on failure
*/
function SetExpressCheckout($amt,$tax=0,$email=""){
	global $PAYPAL_URL_SUCCESS, $PAYPAL_URL_FAILURE;
	$args = pp_init_args('SetExpressCheckout');
	$args['RETURNURL'] = $PAYPAL_URL_SUCCESS;
	$args['CANCELURL'] = $PAYPAL_URL_FAILURE;
	$args['PAYMENTREQUEST_0_AMT'] = $amt;
	$args['PAYMENTREQUEST_0_TAXAMT'] = $tax;
	$args['PAYMENTREQUEST_0_ITEMAMT'] = $amt - $tax;
	$args['PAYMENTREQUEST_0_DESC'] = "WFC Purchase";
	$args['SOLUTIONTYPE'] = 'Sole';
	if (!empty($email))
		$args['EMAIL'] = $email;

	$result = pp_do_curl($args);
	if ($result['ACK'] == 'Success' && isset($result['TOKEN'])){
		header("Location: ".(PAYPAL_LIVE ? PAYPAL_LIVE_RD : PAYPAL_TEST_RD)."?cmd=_express-checkout&token=".$result['TOKEN']);
		return $result['TOKEN'];
	}
	else return False;
}

/* Collect user information from paypal. 
   $token is a $_GET argument provided by paypal
   when users return to our site 

   Returns paypal response as keyed array
*/
function GetExpressCheckoutDetails($token){
	$args = pp_init_args('GetExpressCheckoutDetails');
	$args['TOKEN'] = $token;
	$result = pp_do_curl($args);
	return $result;
}

/* Finalize payment. Token and ppID (PAYERID) are
   provided by paypal.

   Returns paypal response as keyed array
*/
function DoExpressCheckoutPayment($token,$ppID,$amt){
	$args = pp_init_args('DoExpressCheckoutPayment');
	$args['TOKEN'] = $token;
	$args['PAYMENTREQUEST_0_PAYMENTACTION'] = 'Sale';
	$args['PAYERID'] = $ppID;
	$args['PAYMENTREQUEST_0_AMT'] = $amt;

	$result = pp_do_curl($args);
	return $result;
}

?>
