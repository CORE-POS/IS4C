<?php
include('ini.php');
include('../util.php');
?>
<html>
<head>
<title>Lane Global: Extra configuration options</title>
<link rel="stylesheet" href="../../src/css/install.css" type="text/css" />
<script type="text/javascript" src="../../src/jquery/jquery.js"></script>
</head>
<body>
<?php
echo showLinkToFannie();
echo showInstallTabsLane("Additional Configuration", '');
?>

<form action=extra_config.php method=post>
<h1>IT CORE Lane Global Configuration: Additional Configuration</h1>
<b>Browser only</b>: <select name=BROWSER_ONLY>
<?php
if ($CORE_LOCAL->get('browserOnly')==="") $CORE_LOCAL->set('browserOnly','1');
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
if ($CORE_LOCAL->get('store')==="") $CORE_LOCAL->set('store','utopia');
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
if ($CORE_LOCAL->get('discountEnforced')==="") $CORE_LOCAL->set('discountEnforced','1');
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
<b>Line Item Discount (member)</b>: 
<?php
if ($CORE_LOCAL->get('LineItemDiscountMem')==="") $CORE_LOCAL->set('LineItemDiscountMem','0');
if(isset($_REQUEST['LD_MEM'])) $CORE_LOCAL->set('LineItemDiscountMem',$_REQUEST['LD_MEM']);
printf("<input type=text name=LD_MEM value=\"%f\" />",$CORE_LOCAL->get('LineItemDiscountMem'));
confsave('LineItemDiscountMem',"'".$CORE_LOCAL->get('LineItemDiscountMem')."'");
?>
(percentage; 0.05 =&gt; 5%)
<br />
<b>Line Item Discount (non-member)</b>: 
<?php
if ($CORE_LOCAL->get('LineItemDiscountNonMem')==="") $CORE_LOCAL->set('LineItemDiscountNonMem','0');
if(isset($_REQUEST['LD_NONMEM'])) $CORE_LOCAL->set('LineItemDiscountNonMem',$_REQUEST['LD_NONMEM']);
printf("<input type=text name=LD_NONMEM value=\"%f\" />",$CORE_LOCAL->get('LineItemDiscountNonMem'));
confsave('LineItemDiscountNonMem',"'".$CORE_LOCAL->get('LineItemDiscountNonMem')."'");
?>
(percentage; 0.05 =&gt; 5%)
<br />
<b>Lock screen on idle</b>: <select name=LOCKSCREEN>
<?php
if ($CORE_LOCAL->get('lockScreen')==="") $CORE_LOCAL->set('lockScreen','1');
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
if ($CORE_LOCAL->get('defaultNonMem')==="") $CORE_LOCAL->set('defaultNonMem','11');
if(isset($_REQUEST['NONMEM'])) $CORE_LOCAL->set('defaultNonMem',$_REQUEST['NONMEM']);
printf("<input type=text name=NONMEM value=\"%s\" />",$CORE_LOCAL->get('defaultNonMem'));
confsave('defaultNonMem',"'".$CORE_LOCAL->get('defaultNonMem')."'");
?>
<br />
Normally a single account number is used for most if not all non-member
transactions. Specify that account number here.
<br />
<b>Visiting Member #</b>: 
<?php
if ($CORE_LOCAL->get('visitingMem')==="") $CORE_LOCAL->set('visitingMem','9');
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
if ($CORE_LOCAL->get('memlistNonMember')==="") $CORE_LOCAL->set('memlistNonMember','0');
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
if ($CORE_LOCAL->get('cashOverLimit')==="") $CORE_LOCAL->set('cashOverLimit','0');
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
if ($CORE_LOCAL->get('dollarOver')==="") $CORE_LOCAL->set('dollarOver','0');
if(isset($_REQUEST['OVER_LIMIT'])) $CORE_LOCAL->set('dollarOver',$_REQUEST['OVER_LIMIT']);
printf("<input type=text size=4 name=OVER_LIMIT value=\"%s\" />",$CORE_LOCAL->Get('dollarOver'));
confsave('dollarOver',$CORE_LOCAL->get('dollarOver'));
?>
<hr />
<b>Enable receipts</b>: <select name=PRINT>
<?php
if ($CORE_LOCAL->get('print')==="") $CORE_LOCAL->set('print','1');
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
if ($CORE_LOCAL->get('newReceipt')==="") $CORE_LOCAL->set('newReceipt','0');
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
if ($CORE_LOCAL->get('printerPort')==="") $CORE_LOCAL->set('printerPort','fake.txt');
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
if ($CORE_LOCAL->get('scalePort')==="") $CORE_LOCAL->set('scalePort','');
if(isset($_REQUEST['SPORT'])) $CORE_LOCAL->set('scalePort',$_REQUEST['SPORT']);
printf("<input type=text name=SPORT value=\"%s\" />",$CORE_LOCAL->get('scalePort'));
confsave('scalePort',"'".$CORE_LOCAL->get('scalePort')."'");
?>
<br />
Path to the scanner scale. Common values are COM1 (windows) and /dev/ttyS0 (linux).
<br />
<b>Scanner/scale driver</b>:
<?php
if ($CORE_LOCAL->get('scaleDriver')==="") $CORE_LOCAL->set('scaleDriver','NewMagellan');
if(isset($_REQUEST['SDRIVER'])) $CORE_LOCAL->set('scaleDriver',$_REQUEST['SDRIVER']);
printf("<input type=text name=SDRIVER value=\"%s\" />",$CORE_LOCAL->get('scaleDriver'));
confsave('scaleDriver',"'".$CORE_LOCAL->get('scaleDriver')."'");
?>
<br />
The name of your scale driver. Known good values include "ssd" and "NewMagellan".
<hr />
<b>Alert Bar</b>:<br />
<?php
if ($CORE_LOCAL->get('alertBar')==="") $CORE_LOCAL->set('alertBar','Warning!');
if (isset($_REQUEST['ALERT'])) $CORE_LOCAL->set('alertBar',$_REQUEST['ALERT']);
printf("<input size=40 type=text name=ALERT value=\"%s\" />",$CORE_LOCAL->get('alertBar'));
confsave('alertBar',"'".$CORE_LOCAL->get('alertBar')."'");
?>
<br />
<b>Footer Modules</b> (left to right):<br />
<?php
$footer_mods = array();
// get current settings
$current_mods = $CORE_LOCAL->get("FooterModules");
// replace w/ form post if needed
// fill in defaults if missing
if (isset($_REQUEST['FOOTER_MODS'])) $current_mods = $_REQUEST['FOOTER_MODS'];
elseif(!is_array($current_mods) || count($current_mods) != 5){
	$current_mods = array(
	'SavedOrCouldHave',
	'TransPercentDiscount',
	'MemSales',
	'EveryoneSales',
	'MultiTotal'
	);
}
$dh = opendir($CORE_PATH.'lib/FooterBoxes/');
while(False !== ($f = readdir($dh))){
	if ($f == "." || $f == "..")
		continue;
	if (substr($f,-4) == ".php"){
		$footer_mods[] = rtrim($f,".php");
	}
}
for($i=0;$i<5;$i++){
	echo '<select name="FOOTER_MODS[]">';
	foreach($footer_mods as $fm){
		printf('<option %s>%s</option>',
			($current_mods[$i]==$fm?'selected':''),$fm);
	}
	echo '</select><br />';
}
$saveStr = "array(";
foreach($current_mods as $m)
	$saveStr .= "'".$m."',";
$saveStr = rtrim($saveStr,",").")";
confsave('FooterModules',$saveStr);
?>
<hr />
<b>Enable onscreen keys</b>: <select name=SCREENKEYS>
<?php
if ($CORE_LOCAL->get('touchscreen')==="") $CORE_LOCAL->set('touchscreen',False);
if(isset($_REQUEST['SCREENKEYS'])){
	$CORE_LOCAL->set('touchscreen',($_REQUEST['SCREENKEYS']==1)?True:False);
}
if ($CORE_LOCAL->get('touchscreen')){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0 >No</option>";
	confsave('touchscreen',True);
}
else {
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
	confsave('touchscreen',False);
}
?>
</select><br />
<b>Separate customer display</b>: <select name=CUSTDISPLAY>
<?php
if ($CORE_LOCAL->get('CustomerDisplay')==="") $CORE_LOCAL->set('CustomerDisplay','1');
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
if ($CORE_LOCAL->get('CCintegarte')==="") $CORE_LOCAL->set('CCintegrate','0');
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
if ($CORE_LOCAL->get('gcIntegarte')==="") $CORE_LOCAL->set('gcIntegrate','0');
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
if ($CORE_LOCAL->get('RegisteredPaycardClasses')==="") $CORE_LOCAL->set('RegisteredPaycardClasses',array());
if (isset($_REQUEST['PAY_MODS'])) $CORE_LOCAL->set('RegisteredPaycardClasses',$_REQUEST['PAY_MODS']);

$mods = array();
$dh = opendir($CORE_PATH.'cc-modules/');
while($dh && False !== ($f = readdir($dh))){
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
// this save is different than the lane version!
confsave('RegisteredPaycardClasses',$CORE_LOCAL->get('RegisteredPaycardClasses'));
?>
</select><br />
<b>Signature Required Limit</b>:
<?php
if (isset($_REQUEST['CCSigLimit'])) $CORE_LOCAL->set('CCSigLimit',$_REQUEST['CCSigLimit']);
if ($CORE_LOCAL->get('CCSigLimit')=="") $CORE_LOCAL->set('CCSigLimit',0.00);
printf(" \$<input size=4 type=text name=CCSigLimit value=\"%s\" />",$CORE_LOCAL->get('CCSigLimit'));
confsave('CCSigLimit',$CORE_LOCAL->get('CCSigLimit'));
?>
<br /><b>Signature Capture Device</b>:
<?php
if ($CORE_LOCAL->get('SigCapture')=="") $CORE_LOCAL->set('SigCapture','');
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
