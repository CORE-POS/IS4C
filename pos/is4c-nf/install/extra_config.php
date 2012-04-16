<?php
include('../ini.php');
include('util.php');
?>
<html>
<head>
<title>Extra configuration options</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Additional Configuration
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="scanning.php">Scanning Options</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="security.php">Security</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_data.php">Sample Data</a>
<form action=extra_config.php method=post>
<b>Browser only</b>: <select name=BROWSER_ONLY>
<?php
if (isset($_REQUEST['BROWSER_ONLY'])) $CORE_LOCAL->set('browserOnly',$_REQUEST['BROWSER_ONLY']);
if ($CORE_LOCAL->get('browserOnly') == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0>No</option>";
}
else{
	echo "<option value=1>Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('browserOnly',$CORE_LOCAL->get('browserOnly'));
?>
</select><br />
If Yes, the "exit" button on the login screen attempts to close the window.
<br />
<b>Store</b>:
<?php
if (isset($_REQUEST['STORE'])) $CORE_LOCAL->set('store',$_REQUEST['STORE']);
printf("<input type=text name=STORE value=\"%s\" />",$CORE_LOCAL->get('store'));
confsave('store',"'".$CORE_LOCAL->get('store')."'");
?>
<br />
In theory, any hard-coded, store specific sequences should be blocked
off based on the store setting. Adherence to this principle is less than
ideal.
<br />
<b>Discounts enabled</b>: <select name=DISCOUNTS>
<?php
if(isset($_REQUEST['DISCOUNTS'])) $CORE_LOCAL->set('discountEnforced',$_REQUEST['DISCOUNTS']);
if ($CORE_LOCAL->get("discountEnforced") == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0 >No</option>";
}
else {
	echo "<option value=1>Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('discountEnforced',$CORE_LOCAL->get('discountEnforced'));
?>
</select><br />
If yes, members get a percentage discount as specified in custdata.
<br />
<b>Lock screen on idle</b>: <select name=LOCKSCREEN>
<?php
if (isset($_REQUEST['LOCKSCREEN'])) $CORE_LOCAL->set('lockScreen',$_REQUEST['LOCKSCREEN']);
if ($CORE_LOCAL->get("lockScreen") == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0 >No</option>";
}
else {
	echo "<option value=1>Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('lockScreen',$CORE_LOCAL->get('lockScreen'));
?>
</select>
<hr />
<b>Default Non-member #</b>: 
<?php
if(isset($_REQUEST['NONMEM'])) $CORE_LOCAL->set('defaultNonMem',$_REQUEST['NONMEM']);
printf("<input type=text name=NONMEM value=\"%s\" />",$CORE_LOCAL->get('defaultNonMem'));
confsave('defaultNonMem',"'".$CORE_LOCAL->get('defaultNonMem')."'");
?>
<br />
Normally a single account number is used for most if not all non-member
transactions. Specify that account number here.
<b>Visiting Member #</b>: 
<?php
if(isset($_REQUEST['VISMEM'])) $CORE_LOCAL->set('visitingMem',$_REQUEST['VISMEM']);
printf("<input type=text name=VISMEM value=\"%s\" />",$CORE_LOCAL->get('visitingMem'));
confsave('visitingMem',"'".$CORE_LOCAL->get('visitingMem')."'");
?>
<br />
This account provides members of other co-ops with member pricing
but no other benefits. Leave blank to disable.
<br />
<b>Show non-member account in searches</b>: <select name=SHOW_NONMEM>
<?php
if(isset($_REQUEST['SHOW_NONMEM'])) $CORE_LOCAL->set('memlistNonMember',$_REQUEST['SHOW_NONMEM']);
if ($CORE_LOCAL->get("memlistNonMember") == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0 >No</option>";
}
else {
	echo "<option value=1>Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('memlistNonMember',$CORE_LOCAL->get('memlistNonMember'));
?>
</select>
<hr />
<b>Allow members to write checks over purchase amount</b>: <select name=OVER>
<?php
if(isset($_REQUEST['OVER'])) $CORE_LOCAL->set('cashOverLimit',$_REQUEST['OVER']);
if ($CORE_LOCAL->get("cashOverLimit") == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0 >No</option>";
}
else {
	echo "<option value=1>Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('cashOverLimit',$CORE_LOCAL->get('cashOverLimit'));
?>
</select><br />
<b>Check over limit</b>: $
<?php
if(isset($_REQUEST['OVER_LIMIT'])) $CORE_LOCAL->set('dollarOver',$_REQUEST['OVER_LIMIT']);
printf("<input type=text size=4 name=OVER_LIMIT value=\"%s\" />",$CORE_LOCAL->Get('dollarOver'));
confsave('dollarOver',$CORE_LOCAL->get('dollarOver'));
?>
<hr />
<b>Enable receipts</b>: <select name=PRINT>
<?php
if(isset($_REQUEST['PRINT'])) $CORE_LOCAL->set('print',$_REQUEST['PRINT']);
if ($CORE_LOCAL->get("print") == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0>No</option>";
}
else {
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('print',$CORE_LOCAL->get("print"));
?>
</select><br />
<b>Use new receipt</b>: <select name=NEWRECEIPT>
<?php
if (isset($_REQUEST['NEWRECEIPT'])) $CORE_LOCAL->set('newReceipt',$_REQUEST['NEWRECEIPT']);
if ($CORE_LOCAL->get("newReceipt") == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0>No</option>";
}
else {
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('newReceipt',$CORE_LOCAL->get("newReceipt"));
?>
</select><br />
The new receipt groups items by category; the old one just lists
them in order.<br />
<b>Printer port</b>:
<?php
if(isset($_REQUEST['PPORT'])) $CORE_LOCAL->set('printerPort',$_REQUEST['PPORT']);
printf("<input type=text name=PPORT value=\"%s\" />",$CORE_LOCAL->get('printerPort'));
confsave('printerPort',"'".$CORE_LOCAL->get('printerPort')."'");
?>
<br />
Path to the printer. Common ports are LPT1: (windows) and /dev/lp0 (linux).
Can also print to a text file if it's just a regular file name.
<hr />
<b>Scanner/scale port</b>:
<?php
if(isset($_REQUEST['SPORT'])) $CORE_LOCAL->set('scalePort',$_REQUEST['SPORT']);
printf("<input type=text name=SPORT value=\"%s\" />",$CORE_LOCAL->get('scalePort'));
confsave('scalePort',"'".$CORE_LOCAL->get('scalePort')."'");
?>
<br />
Path to the scanner scale. Common values are COM1 (windows) and /dev/ttyS0 (linux).
<br />
<b>Scanner/scale driver</b>:
<?php
if(isset($_REQUEST['SDRIVER'])) $CORE_LOCAL->set('scaleDriver',$_REQUEST['SDRIVER']);
printf("<input type=text name=SDRIVER value=\"%s\" />",$CORE_LOCAL->get('scaleDriver'));
confsave('scaleDriver',"'".$CORE_LOCAL->get('scaleDriver')."'");
?>
<br />
The name of your scale driver. Known good values include "ssd" and "NewMagellan".
<?php
// try to initialize scale driver
if ($CORE_LOCAL->get("scaleDriver") != ""){
	$classname = $CORE_LOCAL->get("scaleDriver");
	if (!file_exists('../scale-drivers/php-wrappers/'.$classname.'.php'))
		echo "<br /><i>Warning: PHP driver file not found</i>";
	else {
		include('../scale-drivers/php-wrappers/'.$classname.'.php');
		$instance = new $classname();
		$instance->SavePortConfiguration($CORE_LOCAL->get("scalePort"));
		$abs_path = substr($_SERVER['PATH_TRANSLATED'],0,
				strlen($_SERVER['PATH_TRANSLATED'])-strlen('install/extra_config.php')-1);
		$instance->SaveDirectoryConfiguration($abs_path);
	}
}
?>
<hr />
<b>Receipt Headers</b>:<br />
You can add more in the customreceipt table, but for historical
reasons the first three are hard coded here.<br />
<?php
if (isset($_REQUEST['RH1'])) $CORE_LOCAL->set('receiptHeader1',$_REQUEST['RH1']);
printf("<input size=40 type=text name=RH1 value=\"%s\" />",$CORE_LOCAL->get('receiptHeader1'));
confsave('receiptHeader1',"'".$CORE_LOCAL->get('receiptHeader1')."'");
if (isset($_REQUEST['RH2'])) $CORE_LOCAL->set('receiptHeader2',$_REQUEST['RH2']);
printf("<br /><input size=40 type=text name=RH2 value=\"%s\" />",$CORE_LOCAL->get('receiptHeader2'));
confsave('receiptHeader2',"'".$CORE_LOCAL->get('receiptHeader2')."'");
if (isset($_REQUEST['RH3'])) $CORE_LOCAL->set('receiptHeader3',$_REQUEST['RH3']);
printf("<br /><input size=40 type=text name=RH3 value=\"%s\" />",$CORE_LOCAL->get('receiptHeader3'));
confsave('receiptHeader3',"'".$CORE_LOCAL->get('receiptHeader3')."'");
?>
<hr />
<b>Receipt Footers</b>:<br />
Same deal as headers.<br />
<?php
if (isset($_REQUEST['RF1'])) $CORE_LOCAL->set('receiptFooter1',$_REQUEST['RF1']);
printf("<input size=40 type=text name=RF1 value=\"%s\" />",$CORE_LOCAL->get('receiptFooter1'));
confsave('receiptFooter1',"'".$CORE_LOCAL->get('receiptFooter1')."'");
if (isset($_REQUEST['RF2'])) $CORE_LOCAL->set('receiptFooter2',$_REQUEST['RF2']);
printf("<br /><input size=40 type=text name=RF2 value=\"%s\" />",$CORE_LOCAL->get('receiptFooter2'));
confsave('receiptFooter2',"'".$CORE_LOCAL->get('receiptFooter2')."'");
if (isset($_REQUEST['RF3'])) $CORE_LOCAL->set('receiptFooter3',$_REQUEST['RF3']);
printf("<br /><input size=40 type=text name=RF3 value=\"%s\" />",$CORE_LOCAL->get('receiptFooter3'));
confsave('receiptFooter3',"'".$CORE_LOCAL->get('receiptFooter3')."'");
?>
<hr />
<b>Check endrosement</b>:<br />
These lines get printed on the back of checks.<br />
<?php
if (isset($_REQUEST['CE1'])) $CORE_LOCAL->set('ckEndorse1',$_REQUEST['CE1']);
printf("<input size=40 type=text name=CE1 value=\"%s\" />",$CORE_LOCAL->get('ckEndorse1'));
confsave('ckEndorse1',"'".$CORE_LOCAL->get('ckEndorse1')."'");
if (isset($_REQUEST['CE2'])) $CORE_LOCAL->set('ckEndorse2',$_REQUEST['CE2']);
printf("<br /><input size=40 type=text name=CE2 value=\"%s\" />",$CORE_LOCAL->get('ckEndorse2'));
confsave('ckEndorse2',"'".$CORE_LOCAL->get('ckEndorse2')."'");
if (isset($_REQUEST['CE3'])) $CORE_LOCAL->set('ckEndorse3',$_REQUEST['CE3']);
printf("<br /><input size=40 type=text name=CE3 value=\"%s\" />",$CORE_LOCAL->get('ckEndorse3'));
confsave('ckEndorse3',"'".$CORE_LOCAL->get('ckEndorse3')."'");
if (isset($_REQUEST['CE4'])) $CORE_LOCAL->set('ckEndorse4',$_REQUEST['CE4']);
printf("<br /><input size=40 type=text name=CE4 value=\"%s\" />",$CORE_LOCAL->get('ckEndorse4'));
confsave('ckEndorse4',"'".$CORE_LOCAL->get('ckEndorse4')."'");
?>
<hr />
<b>Begin transaction message</b>:<br />
<?php
if (isset($_REQUEST['WM1'])) $CORE_LOCAL->set('welcomeMsg1',$_REQUEST['WM1']);
printf("<input size=40 type=text name=WM1 value=\"%s\" />",$CORE_LOCAL->get('welcomeMsg1'));
confsave('welcomeMsg1',"'".$CORE_LOCAL->get('welcomeMsg1')."'");
if (isset($_REQUEST['WM2'])) $CORE_LOCAL->set('welcomeMsg2',$_REQUEST['WM2']);
printf("<br /><input size=40 type=text name=WM2 value=\"%s\" />",$CORE_LOCAL->get('welcomeMsg2'));
confsave('welcomeMsg2',"'".$CORE_LOCAL->get('welcomeMsg2')."'");
?>
<hr />
<b>End trnasaction message</b>:<br />
<?php
if (isset($_REQUEST['FM1'])) $CORE_LOCAL->set('farewellMsg1',$_REQUEST['FM1']);
printf("<input size=40 type=text name=FM1 value=\"%s\" />",$CORE_LOCAL->get('farewellMsg1'));
confsave('farewellMsg1',"'".$CORE_LOCAL->get('farewellMsg1')."'");
if (isset($_REQUEST['FM2'])) $CORE_LOCAL->set('farewellMsg2',$_REQUEST['FM2']);
printf("<br /><input size=40 type=text name=FM2 value=\"%s\" />",$CORE_LOCAL->get('farewellMsg2'));
confsave('farewellMsg2',"'".$CORE_LOCAL->get('farewellMsg2')."'");
if (isset($_REQUEST['FM3'])) $CORE_LOCAL->set('farewellMsg3',$_REQUEST['FM3']);
printf("<br /><input size=40 type=text name=FM3 value=\"%s\" />",$CORE_LOCAL->get('farewellMsg3'));
confsave('farewellMsg3',"'".$CORE_LOCAL->get('farewellMsg3')."'");
?>
<hr />
<b>Training transaction message</b>:<br />
<?php
if (isset($_REQUEST['TM1'])) $CORE_LOCAL->set('trainingMsg1',$_REQUEST['TM1']);
printf("<input size=40 type=text name=TM1 value=\"%s\" />",$CORE_LOCAL->get('trainingMsg1'));
confsave('trainingMsg1',"'".$CORE_LOCAL->get('trainingMsg1')."'");
if (isset($_REQUEST['TM2'])) $CORE_LOCAL->set('trainingMsg2',$_REQUEST['TM2']);
printf("<br /><input size=40 type=text name=TM2 value=\"%s\" />",$CORE_LOCAL->get('trainingMsg2'));
confsave('trainingMsg2',"'".$CORE_LOCAL->get('trainingMsg2')."'");
?>
<hr />
<b>Alert Bar</b>:<br />
<?php
if (isset($_REQUEST['ALERT'])) $CORE_LOCAL->set('alertBar',$_REQUEST['ALERT']);
printf("<input size=40 type=text name=ALERT value=\"%s\" />",$CORE_LOCAL->get('alertBar'));
confsave('alertBar',"'".$CORE_LOCAL->get('alertBar')."'");
?>
<hr />
<b>Enable onscreen keys</b>: <select name=SCREENKEYS>
<?php
if(isset($_REQUEST['SCREENKEYS'])){
	$CORE_LOCAL->set('touchscreen',($_REQUEST['SCREENKEYS']==1)?True:False);
}
if ($CORE_LOCAL->get('touchscreen')){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0 >No</option>";
	confsave('touchscreen','True');
}
else {
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
	confsave('touchscreen','False');
}
?>
</select><br />
<b>Separate customer display</b>: <select name=CUSTDISPLAY>
<?php
if(isset($_REQUEST['CUSTDISPLAY'])) $CORE_LOCAL->set('CustomerDisplay',$_REQUEST['CUSTDISPLAY']);
if ($CORE_LOCAL->get('CustomerDisplay')){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0 >No</option>";
}
else {
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('CustomerDisplay',$CORE_LOCAL->get('CustomerDisplay'));
?>
</select><br />
Touchscreen keys and menus really don't need to appear on
the customer-facing display. Experimental feature where one
window always shows the item listing. Very alpha.
<hr />
<i>Integrated card processing configuration is included for the sake
of completeness. The modules themselves require individual configuration,
too</i><br />
<b>Integrated Credit Cards</b>: <select name=INT_CC>
<?php
if(isset($_REQUEST['INT_CC'])) $CORE_LOCAL->set('CCintegrate',$_REQUEST['INT_CC']);
if ($CORE_LOCAL->get('CCintegrate') == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0>No</option>";
}
else {
	echo "<option value=1>Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('CCintegrate',$CORE_LOCAL->get('CCintegrate'));
?>
</select><br />
<b>Integrated Gift Cards</b>: <select name=INT_GC>
<?php
if(isset($_REQUEST['INT_GC'])) $CORE_LOCAL->set('gcIntegrate',$_REQUEST['INT_GC']);
if ($CORE_LOCAL->get('gcIntegrate') == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0>No</option>";
}
else {
	echo "<option value=1>Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('gcIntegrate',$CORE_LOCAL->get('gcIntegrate'));
?>
</select><br />
<b>Enabled paycard modules</b>:<br />
<select multiple size=10 name=PAY_MODS[]>
<?php
if (isset($_REQUEST['PAY_MODS'])) $CORE_LOCAL->set('RegisteredPaycardClasses',$_REQUEST['PAY_MODS']);

$mods = array();
$dh = opendir('../cc-modules/');
while(False !== ($f = readdir($dh))){
	if ($f == "." || $f == ".." || $f == "BasicCCModule.php")
		continue;
	if (substr($f,-4) == ".php")
		$mods[] = rtrim($f,".php");
}

foreach($mods as $m){
	$selected = "";
	foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $r){
		if ($r == $m){
			$selected = "selected";
			break;
		}
	}
	echo "<option $selected>$m</option>";
}

$saveStr = "array(";
foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $r){
	$saveStr .= "'".$r."',";
}
$saveStr = rtrim($saveStr,",").")";
confsave('RegisteredPaycardClasses',$saveStr);
?>
</select><br />
<b>Charge slip lines</b>:<br />
If running integrated credit cards, these lines get
printed on the signature slips.<br />
<?php
if (isset($_REQUEST['CS1'])) $CORE_LOCAL->set('chargeSlip1',$_REQUEST['CS1']);
printf("<input size=40 type=text name=CS1 value=\"%s\" />",$CORE_LOCAL->get('chargeSlip1'));
confsave('chargeSlip1',"'".$CORE_LOCAL->get('chargeSlip1')."'");
if (isset($_REQUEST['CS2'])) $CORE_LOCAL->set('chargeSlip2',$_REQUEST['CS2']);
printf("<br /><input size=40 type=text name=CS2 value=\"%s\" />",$CORE_LOCAL->get('chargeSlip2'));
confsave('chargeSlip2',"'".$CORE_LOCAL->get('chargeSlip2')."'");
if (isset($_REQUEST['CS3'])) $CORE_LOCAL->set('chargeSlip3',$_REQUEST['CS3']);
printf("<br /><input size=40 type=text name=CS3 value=\"%s\" />",$CORE_LOCAL->get('chargeSlip3'));
confsave('chargeSlip3',"'".$CORE_LOCAL->get('chargeSlip3')."'");
if (isset($_REQUEST['CS4'])) $CORE_LOCAL->set('chargeSlip4',$_REQUEST['CS4']);
printf("<br /><input size=40 type=text name=CS4 value=\"%s\" />",$CORE_LOCAL->get('chargeSlip4'));
confsave('chargeSlip4',"'".$CORE_LOCAL->get('chargeSlip4')."'");
if (isset($_REQUEST['CS5'])) $CORE_LOCAL->set('chargeSlip5',$_REQUEST['CS5']);
printf("<br /><input size=40 type=text name=CS5 value=\"%s\" />",$CORE_LOCAL->get('chargeSlip5'));
confsave('chargeSlip5',"'".$CORE_LOCAL->get('chargeSlip5')."'");
?>
<br /><b>Signature Required Limit</b>:
<?php
if (isset($_REQUEST['CCSigLimit'])) $CORE_LOCAL->set('CCSigLimit',$_REQUEST['CCSigLimit']);
if ($CORE_LOCAL->get('CCSigLimit')=="") $CORE_LOCAL->set('CCSigLimit',0.00);
printf(" \$<input size=4 type=text name=CCSigLimit value=\"%s\" />",$CORE_LOCAL->get('CCSigLimit'));
confsave('CCSigLimit',$CORE_LOCAL->get('CCSigLimit'));
?>
<br /><b>Signature Capture Device</b>:
<?php
if (isset($_REQUEST['SigCapture'])) $CORE_LOCAL->set('SigCapture',$_REQUEST['SigCapture']);
printf("<br /><input size=4 type=text name=SigCapture value=\"%s\" />",$CORE_LOCAL->get('SigCapture'));
confsave('SigCapture',"'".$CORE_LOCAL->get('SigCapture')."'");
?>
<i>(blank for none)</i>
<hr />
<input type=submit value="Save Changes" />
</form>
</body>
</html>
