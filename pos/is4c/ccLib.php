<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

include_once("connect.php");

function local_inx() {

	$inx = $_SESSION["CashierNo"].".inx";
	return $inx;
}

function remote_inx() {

	$inx = $_SESSION["ccSharePath"].$_SESSION["CashierNo"].".inx";
	return $inx;
}

function local_oux() {

	$oux = $_SESSION["CashierNo"].".oux";
	return $oux;
}

function remote_oux() {

	$oux = $_SESSION["ccSharePath"].$_SESSION["CashierNo"].".oux";
	return $oux;
}

function ccTicket() {
 // simple concatenation might be ambiguous: trans 3, lane 4, cashier 56 = 3456, trans 34, lane 5, cashier 6 = 3456 also
//	$ticket =  $_SESSION["transno"].$_SESSION["laneno"].$_SESSION["CashierNo"];
  // so, pad each component to two digits with 0's --atf 5/18/07
  $ticket = str_pad($_SESSION["transno"],3,"0",STR_PAD_LEFT).
            str_pad($_SESSION["laneno"],2,"0",STR_PAD_LEFT).
            str_pad($_SESSION["CashierNo"],2,"0",STR_PAD_LEFT);
  return $ticket;
}

function merchantNum() {
	if( $_SESSION["ccLive"] != 1 || $_SESSION["training"] == 1 || $_SESSION["CashierNo"] == 9999){
	  $merch = 123456;
	} else {
	  $merch = 508714;
	}
	return $merch;
}

function delete_file($file) {
	clearstatcache();
	if (file_exists($file)) exec("del ".$file." /q", $aDelete);
	clearstatcache();
}

function copy_file($source, $dest) {
	clearstatcache();
	$copied = 0;
	if (file_exists($source)) {

		if (copy($source, $dest)) {
			$copied = 1;
		} 
	}
	return $copied;
	clearstatcache();
}



// ---------------------------------------------------------------------------

function ccName($enter) {

	$ccName = "Customer";
	$aName = explode('^', $enter); // '^' == chr(94)
	
	foreach ($aName as $name) {
		if (strlen($name) > 0 && strpos($name, "/")) {
			$ccName = $name;
			$aFullName = explode("/", $name);
			$ccName = $aFullName[1]." ".$aFullName[0];
		}
	}
	return $ccName;     
}

// ---------------------------------------------------------------------------

// rewritten --atf 5/16/07
// rewritten again --atf 5/24/07
function ccSwipe($enter) {
/* summary of ISO standards for credit card magnetic stripe data tracks: http://www.cyberd.co.uk/support/technotes/isocards.htm
(hex codes and character representations do not match ASCII - they are defined in the ISO spec)

TRACK 1
  {S} start sentinel: 0x05 '%'
  {C} format code: varies
  {F} field seperator: 0x3F '^'
  {E} end sentinel: 0x1F '?'
  {V} checksum character
  format: {S}{C}data{F}data{F}data{E}{V}
  length: 79 characters

TRACK 2
  {S} start sentinel: 0x0B ';'
  {F} field seperator: 0x0D '='
  {E} end sentinel: 0x0F '?'
  {V} checksum character
  format: {S}data{F}data{E}{V}
  length: 40 characters

TRACK 3
  {S} start sentinel: 0x0B ';'
  {C} format code: varies
  {F} field seperator: 0x0D '='
  {E} end sentinel: 0x0F '?'
  {V} checksum character
  format: {S}{C}{C}data{F}data{E}{V}
  length: 107 characters

--atf 5/16/07 */
  
  $swipe = $enter;
  // replace test card string with Verifone's test Visa data, instead of TT's --atf 5/24/07
  if( substr($swipe,0,18) == ";9999999800000702=") { // only check beginning, we might get new test cards at some point with a different exp date
    $swipe = "%B4012000033330026^VERIFONE TEST 3^".date('y')."121011000 1111A123456789012?" .
             ";4012000033330026=".date('y')."121011000001234567?";
  }
  
  // init
  $track1 = false;
  $track2 = false;
  $track3 = false;
  
  // parse
  $tracks = explode('?', $swipe);
  foreach( $tracks as $track) {
    if( substr($track,0,1) == '%') {  // track1 start sentinel
      if( $track1 === false)       $track1 = substr($track,1);
      else                         return "invalid";  // can't have more than one track1
    } else if( substr($track,0,1) == ';') {  // track2/3 start sentinel
      if( $track2 === false)       $track2 = substr($track,1);
      else if( $track3 === false)  $track3 = substr($track,1);
      else                         return "invalid";  // just how many tracks are on this card, anyway?
    }
  }
  
  // some basic error checking:
  
  // if track1 is present, it must have 'B' format code
  if( $track1 !== false && substr($track1,0,1) !== 'B')
    return "invalid";
  
  // track2 is required and must have two fields (separator is '=' for track2 data)
  if( $track2 === false || strpos($track2, '=') === false)
    return "invalid";
  
  return $track2;
}

// ---------------------------------------------------------------------------

function ccType($CC) {   // ***** CvR check to see if card is a valid CC type **** END 

      // ***** CvR 09/22/05 trim off F for testing card type if forced cc ***** END	
      // disabled; this function is called with the track2 swipe contents, which should never contain these characters anyway
      // to check F or V, it must be done on the original form input, in ccValid() or above --atf 5/24/07
/*
      if(substr($CC,0,1) == 'F' || substr($CC,0,1) == 'f' || substr($CC,0,1) == 'V' || substr($CC,0,1) == 'v'){
         $CC = substr($CC,1,16);
      }      
*/
      If (substr($CC,0,1) == 4) { //check for VISA
		$issuer = "VISA";

	} elseif ( substr($CC,0,2) >= 50 && substr($CC,0,2) <= 59){ //check for MC
            $issuer = "MasterCard";

	} elseif ( substr($CC,0,2) == 34 || substr($CC,0,2) ==37){ //check for AMEX
		// American Express unsupported
            $issuer = "Unsupported";
		
	} elseif ( substr($CC,0,4) == 6011) { //check for Discover
            $issuer = "Discover Card";

	} elseif ( substr($CC,0,4) == 9999) { //check for Concord test card added 6/23/05 CvR
		if($_SESSION["CashierNo"] == 9999){
			$issuer = "Concord Test";
    		}else{
			$issuer = "Unsupported";
		}
	} else { //other CC cards
            $issuer = "Unsupported";

	}
	return $issuer;
}

// ---------------------------------------------------------------------------

function ccValid($enter) {
	$_SESSION["ccSwipe"] = ccSwipe($enter); // returns track2 magstripe data
	$_SESSION["ccName"] = ccName($enter); // scans track1 for cardholder name
	$_SESSION["ccType"] = ccType($_SESSION["ccSwipe"]); // determines card issuer from card number prefix
/*debug* echo "<script>alert('swipe [".$_SESSION["ccSwipe"]."]\\r\\ntype [".$_SESSION["ccType"]."]')</script>";/**/

	if ($_SESSION["ccSwipe"] == "invalid" || $_SESSION["ccType"] == "Unsupported") {
		$valid = 0;
	} else {
		$valid = 1;
	}
	return $valid;
}

// ------------------------------- parse_oux ------------------------------------------

function parse_oux(){

	// Get a file into an array.  In this example we'll go through HTTP to get
	// the HTML source of a URL.
	// Loop through our array, show HTML source as HTML source; and line numbers too.



	$lines = file(remote_oux());

	global $Result;
	global $User1;
	global $Auth;
	global $Reference;
	global $TroutD;
	global $Ticket;
	global $Trans_Date;
	global $Seq_Num;


	$Result = "";
	$User1 = "";
	$Auth = "";
	$Reference = "";
	$TroutD = "";
	$Ticket = "";
	$Trans_Date = "";
	$Seq_Num = "";

foreach ($lines as $line_num => $line) {
   $user1 = strstr(htmlspecialchars($line),'USER_ID');
   $result = strstr(htmlspecialchars($line),'RESULT');
   $troutd = strstr(htmlspecialchars($line),"TROUTD");
   $auth = strstr(htmlspecialchars($line),"AUTH_");
   $tdate = strstr(htmlspecialchars($line),"TRANS_DATE");
   $ticket = strstr(htmlspecialchars($line),"TICKET");
   $ref_num = strstr(htmlspecialchars($line),"REFERENCE");
   $seq_num = strstr(htmlspecialchars($line),"INTRN_");

   if(!empty($user1)){

	$User1 = trim($lines[$line_num]);
	$User1 = strip_tags($User1);
   }

   if(!empty($result)){

	$Result = trim($lines[$line_num]);
	$Result = strip_tags($Result);
   }

   if(!empty($troutd)){

	$TroutD = trim($lines[$line_num]);
	$TroutD = strip_tags($TroutD);
   }

   if(!empty($auth)){

	$Auth = trim($lines[$line_num]);
	$Auth = strip_tags($Auth);
   }

   if(!empty($tdate)){

	$Trans_Date = trim($lines[$line_num]);
	$Trans_Date = strip_tags($Trans_Date);
   }

   if(!empty($ticket)){

	$Ticket = trim($lines[$line_num]);
	$Ticket = strip_tags($Ticket);
   }

   if(!empty($ref_num)){

	$Reference = trim($lines[$line_num]);
	$Reference = strip_tags($Reference);
   }

   if(!empty($seq_num)){
	$Seq_Num = trim($lines[$line_num]);
	$Seq_Num = strip_tags($Seq_Num);
   }
	   
}

	if(empty($TroutD)){
		$TroutD = $Seq_Num;
	}

	$_SESSION["troutd"] = $TroutD; // added 04/01/05   Tak and CvR

	return $Result;
	return $User1;
	return $Auth;
	return $Reference;
	return $TroutD;
	return $Seq_Num;
	return $Ticket;
	return $Trans_Date;

}



// -------------------------------------------------------------------------------

function sys_pcc() {
	$syspcc = $_SESSION["ccSharePath"]."sys.pcc";
	if (file_exists($syspcc)) {
		$problem = 1;
	} else {
		$problem = 0;
	}
	return $problem;
}
	
// ---------------------------- ccXML --------------------------------------------

function ccXML() {

$inxUploaded = 0;

//$connect = pDataConnect(); // no longer used? --atf 5/24/07

// cache filenames
$remote_inx = remote_inx();
$remote_oux = remote_oux();
$local_inx = local_inx();

// check if PCCharge has both #.inx and #.pro (for any #, not just CashierNo) -- stuck queue, alert FEC --atf 5/24/07
// also note a new return value for ccXML(): -1 means stuck-queue, 0 means some other file copy error
$testcmd = "for %f in (\\".$_SESSION["ccServer"]."\\".$_SESSION["ccShare"]."\\temp\\*.pro) do @if exist \"\\".$_SESSION["ccServer"]."\\".$_SESSION["ccShare"]."\\temp\\%%~nf.inx\" echo 1";
$teststuck = shell_exec($testcmd);
if( $teststuck != "")
    return -1;
// end stuck-queue-check

$input = $_SESSION["ccSwipe"];
$transno =  ccTicket();
$amountIn = $_SESSION["ccAmt"];

$aCCInput = explode("=", $input);

// ***** CvR 09/22/05 test for forced transactions ***** END
// $input comes from SESSION[ccSwipe] which has track2 data: these characters could not be there, and all digits up to '=' are the card num --atf 5/24/07
$CC = $aCCInput[0];
/*
if(substr($input,0,1) == 'F' || substr($input,0,1) == 'f'){
   $CC = substr($aCCInput[0], -17);
}else{
   $CC = substr($aCCInput[0], -16);
}
*/
$expYear = substr($aCCInput[1], 0, 2);
$expMonth = substr($aCCInput[1], 2, 2);


//-----added 03/31/05 CvR

if($amountIn > 0) {
   $command = "1"; //action code for transaction sale = 1
}else{
   $command = "2"; //action code for return = 2
}

//-----
// in the PCCharge DevKit manual, 'MANUAL_FLAG' is defined as '0=manual, 1=swiped'  --atf 5/18/07
$manual = 1;
if ($_SESSION["ccManual"] <> 0) {
	$manual = 0;
	$_SESSION["ccManual"] = 0;
}
$expDate = $expMonth.$expYear; 		//set expDate 
$present = 1; 					//card present
$proc = 'BPAS'; 					//processor (will be set to CCRD when done testing)
$TID = merchantNum(); 				//merchant ID for processor
$user = $_SESSION["CashierNo"];

$trackII = $_SESSION["ccSwipe"];
$amount = truncate2(abs($amountIn));


// $string_out creates the string that will be saved to the *.inx file
// of the XML format that PC Charge expects
// Note: ref to x-schema requires specific order sequence

// could not resolve 'Incomplete Trans' errors; last resort, just turn off XML validation --atf 5/18/07
//'<XML_FILE xmlns="x-schema:.\dtd\stnd.xdr">
$string_out = 
'<XML_FILE>
	<XML_REQUEST>
		<USER_ID>'. $user .'</USER_ID>
		<COMMAND>'.$command.'</COMMAND>
		<PROCESSOR_ID>'.$proc.'</PROCESSOR_ID>
		<MERCH_NUM>'.$TID.'</MERCH_NUM>
		<ACCT_NUM>'.$CC.'</ACCT_NUM>
		<EXP_DATE>'.$expDate.'</EXP_DATE>
		<MANUAL_FLAG>'.$manual.'</MANUAL_FLAG>
		<TRANS_AMOUNT>'.$amount.'</TRANS_AMOUNT>';
if( $manual == 1) { // counter-intuitive: manual==1 means swiped, not manual, so pass the track data --atf 5/24/07
  $string_out .= '
		<TRACK_DATA>'.$trackII.'</TRACK_DATA>';
}
$string_out .= '
		<TICKET_NUM>'.$transno.'</TICKET_NUM> 
		<PRESENT_FLAG>1</PRESENT_FLAG>
	</XML_REQUEST>
</XML_FILE>
';

//------added 04/01/05 Tak & CvR-----provide for voiding CC entry on receipt

if(substr($input,0,1) == 'V'|| substr($input,0,1) == 'v'){
        $troutd = $getCCOutW[2];
        $command = 3;
        $string_out =
'<XML_FILE xmlns="x-schema:.\dtd\stnd.xdr">
	<XML_REQUEST>
		<USER_ID>'. $user .'</USER_ID>
		<COMMAND>'.$command.'</COMMAND>
		<TROUTD>'.$troutd.'</TROUTD>
	</XML_REQUEST>
</XML_FILE>
';

	// echo $string_out;
}

//----

//below added to log .inx info. To be tested on lane9, etc 
//added 07/07/05 CvR

$trans_id = $_SESSION["LastID"] + 1;
$lane = $_SESSION["laneno"];
$trans_no = $_SESSION["transno"];
$now = date('Y-m-d H:i:s');
$dbinx = tDataconnect();
$inxQ = "INSERT INTO INX
         VALUES('$user','$command','$proc','$TID','$CC','$expDate','$manual','$trackII','$transno',1,$amount,'','$now','$trans_id','$trans_no','$lane')";
if (sql_query($inxQ,$dbinx)) {

//---------
// changed all filename functions (remote_oux(), local_inx(), etc) to variables, cached up top --atf 5/24/07
	delete_file($local_inx);		// ***** changed local_inx function to variable abpw 3/05/07 *****

	$fp = fopen($local_inx,'w'); 	// open the file with write permissions -- ***** changed local_inx function to variable abpw 3/05/07 *****
	fwrite($fp, $string_out); 	// write $string_out to the *.inx file
	fclose($fp); 				// close the file

	delete_file($remote_inx);
	delete_file($remote_oux);

	$inxUploaded = copy_file($local_inx, $remote_inx); //***** changed local_inx function to variable abpw 3/05/07 *****
}

return $inxUploaded;

}

// ------------------------------------------------------------------------------------
function ccout($User1,$Result,$TroutD,$ccamt,$catTroutD){

		$ccIDQ = "SELECT top 1 trans_id FROM INX order by transdate desc";
		$dbCC = pDataconnect();
		$ccIDR = mssql_query($ccIDQ,$dbCC);
		$ccIDW = mssql_fetch_row($ccIDR);
	
		$lane = $_SESSION["laneno"];
		$trans = $_SESSION["transno"];
		//$trans_id = $_SESSION["LastID"];
		$trans_id = $ccIDW[0];
		$pccharge = $_SESSION["ccServer"];
		$cn = mysql_connect($pccharge,'sa');
		mysql_select_db('is4cc',$cn);
		
		$insCCOutQ = "INSERT INTO CC_OUT(user_id,result,troutd,amount,four,register_no,trans_no,trans_id)
				  VALUES('$User1', '$Result','$TroutD',$ccamt,$catTroutD,$lane,$trans,$trans_id)";
		//echo $insCCOutQ;
		$insCCOutR = mysql_query($insCCOutQ,$cn);
}


function addOux($user_id, $troutd, $result, $auth_code, $reference, $trans_date, $ticket, $intrn_seq_num) {

	$trans_id = $_SESSION["LastID"] + 1;
	$user_id = $_SESSION["CashierNo"];
	$register_no = $_SESSION["laneno"];
	$trans_no = $_SESSION["transno"];
	$now = date('Y-m-d H:i:s');

	$oux_connect = tDataconnect();
	$oux_Q = "INSERT INTO oux VALUES($user_id,'$troutd','$result','$auth_code','$reference','$trans_date','$ticket','$intrn_seq_num', $register_no, $trans_no, $trans_id, '$now')";

	sql_query($oux_Q, $oux_connect);
}


?>
