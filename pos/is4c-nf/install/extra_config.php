<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::LoadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
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
<a href="debug.php">Debug</a>
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
<b>Line Item Discount (member)</b>: 
<?php
if(isset($_REQUEST['LD_MEM'])) $CORE_LOCAL->set('LineItemDiscountMem',$_REQUEST['LD_MEM']);
printf("<input type=text name=LD_MEM value=\"%f\" />",$CORE_LOCAL->get('LineItemDiscountMem'));
confsave('LineItemDiscountMem',"'".$CORE_LOCAL->get('LineItemDiscountMem')."'");
?>
(percentage; 0.05 =&gt; 5%)
<br />
<b>Line Item Discount (non-member)</b>: 
<?php
if(isset($_REQUEST['LD_NONMEM'])) $CORE_LOCAL->set('LineItemDiscountNonMem',$_REQUEST['LD_NONMEM']);
printf("<input type=text name=LD_NONMEM value=\"%f\" />",$CORE_LOCAL->get('LineItemDiscountNonMem'));
confsave('LineItemDiscountNonMem',"'".$CORE_LOCAL->get('LineItemDiscountNonMem')."'");
?>
(percentage; 0.05 =&gt; 5%)
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
<br />
<b>Bottle Return Department number</b>: 
<?php
if(isset($_REQUEST['BOTTLE_RET'])) $CORE_LOCAL->set('BottleReturnDept',$_REQUEST['BOTTLE_RET']);
printf("<input type=text name=BOTTLE_RET value=\"%s\" />",$CORE_LOCAL->get('BottleReturnDept'));
confsave('BottleReturnDept',"'".$CORE_LOCAL->get('BottleReturnDept')."'");
?>
<br />
Add a BOTTLE RETURN item to your products table with a normal_price of 0, IS4C will prompt for Bottle Return amt. and then make it a negative value.
<br />
<b>Lock Screen Timeout</b>:
<?php
if(isset($_REQUEST['TIMEOUT'])) $CORE_LOCAL->set('timeout',$_REQUEST['TIMEOUT']);
else $CORE_LOCAL->set('timeout',180000);
printf("<input type=text name=TIMEOUT value=\"%s\" />",$CORE_LOCAL->get('timeout'));
confsave('timeout',"'".$CORE_LOCAL->get('timeout')."'");
?>
<br />
Enter timeout in milliseconds. Default: 180000 (3 minutes)
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
		@$instance->SavePortConfiguration($CORE_LOCAL->get("scalePort"));
		@$abs_path = substr($_SERVER['PATH_TRANSLATED'],0,
				strlen($_SERVER['PATH_TRANSLATED'])-strlen('install/extra_config.php')-1);
		@$instance->SaveDirectoryConfiguration($abs_path);
	}
}
?>
<hr />
<b>Alert Bar</b>:<br />
<?php
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
$dh = opendir('../lib/FooterBoxes/');
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
<b>Modular Tenders</b>: <select name=MODTENDERS>
<?php
if(isset($_REQUEST['MODTENDERS'])) $CORE_LOCAL->set('ModularTenders',$_REQUEST['MODTENDERS']);
if ($CORE_LOCAL->get('ModularTenders')){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0 >No</option>";
}
else {
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('ModularTenders',"'".$CORE_LOCAL->get('ModularTenders')."'");
?>
</select><br />
<b>Tender Mapping</b>:<br />
Map custom tenders to IS4Cs expected tenders<br />
Tender Rpt. column: Include the checked tenders in the Tender Report (available via Mgrs. Menu [MG])<br />
<?php
$settings = $CORE_LOCAL->get("TenderMap");
if (!is_array($settings)) $settings = array();
if (isset($_REQUEST['TenderMapping'])){
	$saveStr = "array(";
	$settings = array();
	foreach($_REQUEST['TenderMapping'] as $tm){
		if($tm=="") continue;
		list($code,$mod) = explode(":",$tm);
		$settings[$code] = $mod;
		$saveStr .= "'".$code."'=>'".$mod."',";
	}
	$saveStr = rtrim($saveStr,",").")";
	confsave('TenderMap',$saveStr);
}
$mods = array();
$dh = opendir('../lib/Tenders/');
while(False !== ($f = readdir($dh))){
	if ($f == "." || $f == ".." || $f == "TenderModule.php")
		continue;
	if (substr($f,-4) == ".php")
		$mods[] = rtrim($f,".php");
}
//  Tender Report: Desired tenders column
$settings2 = $CORE_LOCAL->get("TRDesiredTenders");
if (!is_array($settings2)) $settings2 = array();
if (isset($_REQUEST['TR_LIST'])){
	$saveStr2 = "array(";
	$settings2 = array();
	foreach($_REQUEST['TR_LIST'] as $dt){
		if($dt=="") continue;
		list($code2,$name2) = explode(":",$dt);
		$settings2[$code2] = $name2;
		$saveStr2 .= "'".$code2."'=>'".$name2."',";
	}
	$saveStr2 = rtrim($saveStr2,",").")";
	confsave('TRDesiredTenders',$saveStr2);
} //end TR desired tenders
$db = Database::pDataConnect();
$res = $db->query("SELECT TenderCode, TenderName FROM tenders ORDER BY TenderName");
?>
<table cellspacing="0" cellpadding="4" border="1">
<?php
while($row = $db->fetch_row($res)){
	echo "<tr><th>Tender Name</th><th>Map To</th><th>Tender Rpt</th></tr>\n";
	printf('<tr><td>%s (%s)</td>',$row['TenderName'],$row['TenderCode']);
	echo '<td><select name="TenderMapping[]">';
	echo '<option value="">default</option>';
	foreach($mods as $m){
		printf('<option value="%s:%s" %s>%s</option>',
			$row['TenderCode'],$m,
			(isset($settings[$row['TenderCode']])&&$settings[$row['TenderCode']]==$m)?'selected':'',
			$m);	
	}
	echo '</select></td>';
	echo "<td><input type=checkbox name=\"TR_LIST[]\" ";
	echo 'value="'.$row['TenderCode'].':'.$row['TenderName'].'"';
	if (in_array($row['TenderCode'], $settings2)) echo " selected";
	echo "></td></tr>";
}
?>
</table>
<br />

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
