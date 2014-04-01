<!DOCTYPE html>
<html>
<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('InstallUtilities.php');
?>
<head>
<title>IT CORE Lane Installation: Additional Configuration</title>
<link rel="stylesheet" href="../css/toggle-switch.css" type="text/css" />
<script type="text/javascript" src="../js/jquery.js"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">	
<h2>IT CORE Lane Installation: Additional Configuration (Extras)</h2>

<div class="alert"><?php InstallUtilities::checkWritable('../ini.php', False, 'PHP'); ?></div>
<div class="alert"><?php InstallUtilities::checkWritable('../ini-local.php', True, 'PHP'); ?></div>

<form action=extra_config.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr><td colspan=2 class="tblHeader"><h3>General Settings</h3></td></tr>
<tr><td style="width: 30%;">
</td><td>
<?php
if (isset($_REQUEST['BROWSER_ONLY'])) $CORE_LOCAL->set('browserOnly',1);
elseif (isset($_REQUEST['esubmit'])) $CORE_LOCAL->set('browserOnly',0);
else $CORE_LOCAL->set('browserOnly',0);
echo "<fieldset class='toggle'>\n<input type='checkbox' name='BROWSER_ONLY' id='browser'";
if ($CORE_LOCAL->get('browserOnly') == 1) echo " value='1' checked />";
else echo " value='0' />";
echo "\n<label for='browser' onclick=''>Browser only: </label>\n
	<span class='toggle-button'></span></fieldset>";
InstallUtilities::paramSave('browserOnly',$CORE_LOCAL->get('browserOnly'));
?>
<span class='noteTxt'>If Yes, the "exit" button on the login screen attempts to close the window.</span>
</td></tr><tr><td>
<b>Store</b>:</td><td>
<?php
if (isset($_REQUEST['STORE'])) $CORE_LOCAL->set('store',$_REQUEST['STORE']);
printf("<input type=text name=STORE value=\"%s\" />",$CORE_LOCAL->get('store'));
InstallUtilities::paramSave('store',$CORE_LOCAL->get('store'));
?>
<span class='noteTxt'>In theory, any hard-coded, store specific sequences should be blocked
off based on the store setting. Adherence to this principle is less than ideal.</span>
</td></tr><tr><td>
</td><td>
<?php
if(isset($_REQUEST['DISCOUNTS'])) $CORE_LOCAL->set('discountEnforced',1);
elseif(isset($_REQUEST['DISCOUNTS'])) $CORE_LOCAL->set('discountEnforced',0);
elseif ($CORE_LOCAL->get('discountEnforced')==='') $CORE_LOCAL->set('discountEnforced',0);
echo "<fieldset class='toggle'>\n<input type='checkbox' name='DISCOUNTS' id='discounts'";
if ($CORE_LOCAL->get("discountEnforced") == 1) echo " value='1' checked";
else echo " value='0'";
echo " />\n<label for='discounts' onclick=''>Discounts Enabled: </label>\n
	<span class='toggle-button'></span></fieldset>";
InstallUtilities::paramSave('discountEnforced',$CORE_LOCAL->get('discountEnforced'));
?>
<span class='noteTxt'>If yes, members get a percentage discount as specified in custdata.</span>
</td></tr><tr><td>
<label><b>Discount Module</b></label>
</td><td> 
<select name="DISCOUNTHANDLER">
<?php
if(isset($_REQUEST['DISCOUNTHANDLER'])) $CORE_LOCAL->set('DiscountModule',$_REQUEST['DISCOUNTHANDLER']);
elseif ($CORE_LOCAL->get('DiscountModule') === '') $CORE_LOCAL->set('DiscountModule','DiscountModule');
$mods = AutoLoader::listModules('DiscountModule',True);
foreach($mods as $m){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('DiscountModule')==$m ? 'selected' : ''),
		$m);
}
InstallUtilities::paramSave('DiscountModule',$CORE_LOCAL->get('DiscountModule'));
?>
</select>
<span class='noteTxt'>Calculates actual discount amount</span>
</td></tr><tr><td></td><td> 
<?php
if(isset($_REQUEST['RDISCOUNTS'])) $CORE_LOCAL->set('refundDiscountable',1);
elseif(isset($_REQUEST['esubmit'])) $CORE_LOCAL->set('refundDiscountable',0);
elseif($CORE_LOCAL->get('refundDiscountable')==='') $CORE_LOCAL->set('refundDiscountable',0);
echo "<fieldset class='toggle'>\n<input type='checkbox' name='RDISCOUNTS' id='rdiscounts'";
if ($CORE_LOCAL->get("refundDiscountable") == 1) echo " value='1' checked";
else echo " value='0'";
echo " />\n<label for='rdiscounts' onclick=''>Discounts on refunds: </label>\n
	<span class='toggle-button'></span></fieldset>";
InstallUtilities::paramSave('refundDiscountable',$CORE_LOCAL->get('refundDiscountable'));
?>
<span class='noteTxt'>If yes, percent discount is applied to refunds</span>
</td></tr><tr><td>
<b>Line Item Discount (member)</b>: </td><td>
<?php
if(isset($_REQUEST['LD_MEM'])) $CORE_LOCAL->set('LineItemDiscountMem',$_REQUEST['LD_MEM']);
printf("<input type=text name=LD_MEM value=\"%f\" />",$CORE_LOCAL->get('LineItemDiscountMem'));
InstallUtilities::paramSave('LineItemDiscountMem',$CORE_LOCAL->get('LineItemDiscountMem'));
?>
(percentage; 0.05 =&gt; 5%)
</td></tr><tr><td>
<b>Line Item Discount (non-member)</b>: </td><td>
<?php
if(isset($_REQUEST['LD_NONMEM'])) $CORE_LOCAL->set('LineItemDiscountNonMem',$_REQUEST['LD_NONMEM']);
printf("<input type=text name=LD_NONMEM value=\"%f\" />",$CORE_LOCAL->get('LineItemDiscountNonMem'));
InstallUtilities::paramSave('LineItemDiscountNonMem',$CORE_LOCAL->get('LineItemDiscountNonMem'));
?>
(percentage; 0.05 =&gt; 5%)
</td></tr>
<tr><td>
<b>Default Non-member #</b>: </td><td>
<?php
if(isset($_REQUEST['NONMEM'])) $CORE_LOCAL->set('defaultNonMem',$_REQUEST['NONMEM']);
printf("<input type=text name=NONMEM value=\"%s\" />",$CORE_LOCAL->get('defaultNonMem'));
InstallUtilities::paramSave('defaultNonMem',$CORE_LOCAL->get('defaultNonMem'));
?>
<span class='noteTxt'>Normally a single account number is used for most if not all non-member
transactions. Specify that account number here.</span>
</td></tr>
<tr><td>
<b>Default Non-member behavior</b>: </td><td>
<?php
if(isset($_REQUEST['NONMEMBEHAVIOR'])) $CORE_LOCAL->set('RestrictDefaultNonMem', $_REQUEST['NONMEMBEHAVIOR']);
if ($CORE_LOCAL->get('RestrictDefaultNonMem') === '') $CORE_LOCAL->set('RestrictDefaultNonMem', 0);
echo '<select name="NONMEMBEHAVIOR">';
if ($CORE_LOCAL->get('RestrictDefaultNonMem') == 0) {
    echo '<option value="0" selected>No different than other accounts</option>';
    echo '<option value="1" >Cannot override other accounts</option>';
} else {
    echo '<option value="0" >No different than other accounts</option>';
    echo '<option value="1" selected>Cannot override other accounts</option>';
}
echo '</selected>';
InstallUtilities::paramSave('RestrictDefaultNonMem',$CORE_LOCAL->get('RestrictDefaultNonMem'));
?>
<span class='noteTxt'>Normally a single account number is used for most if not all non-member
transactions. Specify that account number here.</span>
</td></tr>
<tr><td>
<b>Visiting Member #</b>: </td><td>
<?php
if(isset($_REQUEST['VISMEM'])) $CORE_LOCAL->set('visitingMem',$_REQUEST['VISMEM']);
printf("<input type=text name=VISMEM value=\"%s\" />",$CORE_LOCAL->get('visitingMem'));
InstallUtilities::paramSave('visitingMem',$CORE_LOCAL->get('visitingMem'));
?>
<span class='noteTxt'>This account provides members of other co-ops with member pricing
but no other benefits. Leave blank to disable.</span>
</td></tr>
<tr><td></td><td>
<?php
if (isset($_REQUEST['SHOW_NONMEM'])) $CORE_LOCAL->set('memlistNonMember',1);
elseif (isset($_REQUEST['esubmit'])) $CORE_LOCAL->set('memlistNonMember',0);
elseif ($CORE_LOCAL->get('memlistNonMember')==='') $CORE_LOCAL->set('memlistNonMember',0);
echo "<fieldset class='toggle'>\n<input type='checkbox' name='SHOW_NONMEM' id='shownonmem'";
if ($CORE_LOCAL->get("memlistNonMember") == 1) echo " value='1' checked";
else echo " value='0'";
echo " />\n<label for='shownonmem' onclick=''>Show non-member: </label>\n
	<span class='toggle-button'></span></fieldset>";
InstallUtilities::paramSave('memlistNonMember',$CORE_LOCAL->get('memlistNonMember'));
?>
<span class='noteTxt'>Display non-member acct. in member searches?</span>
</td></tr>
<tr><td></td><td>
<?php
if (isset($_REQUEST['NORMALIZE_MEM'])) $CORE_LOCAL->set('useMemTypeTable', 1);
elseif (isset($_REQUEST['esubmit'])) $CORE_LOCAL->set('useMemTypeTable', 0);
elseif ($CORE_LOCAL->get('useMemTypeTable')==='') $CORE_LOCAL->set('useMemTypeTable', 0);
echo "<fieldset class='toggle'>\n<input type='checkbox' name='NORMALIZE_MEM' id='normalize_mem'";
if ($CORE_LOCAL->get("useMemTypeTable") == 1) echo " value='1' checked";
else echo " value='0'";
echo " />\n<label for='normalize_mem' onclick=''>Use memtype table: </label>\n
	<span class='toggle-button'></span></fieldset>";
InstallUtilities::paramSave('useMemTypeTable',$CORE_LOCAL->get('useMemTypeTable'));
?>
<span class='noteTxt'>Use memtype table when applicable. This forces all memberships of a given
type to have the same discount, among other things.</span>
</td></tr>
<tr><td>
<b>Bottle Return Department number</b>: </td><td>
<?php
if(isset($_REQUEST['BOTTLE_RET'])) $CORE_LOCAL->set('BottleReturnDept',$_REQUEST['BOTTLE_RET']);
printf("<input type=text name=BOTTLE_RET value=\"%s\" />",$CORE_LOCAL->get('BottleReturnDept'));
InstallUtilities::paramSave('BottleReturnDept',$CORE_LOCAL->get('BottleReturnDept'));
?>
<span class='noteTxt'>Add a BOTTLE RETURN item to your products table with a normal_price of 0, IS4C will prompt for Bottle Return amt. and then make it a negative value.</span>
</td></tr>


<tr><td colspan=2 class="tblHeader">
<h3>Hardware Settings</h3></td></tr>

<?php
// 28Jan14 EL There's an inch or so of whitespace at the bottom of this row.
// I don't know what causes it.
?>
<tr><td><b>Printer port</b>:
</td><td><?php
// If values entered on the form are being saved, set CORE_LOCAL
//  and flag type of port choice: "other" or not.
if (isset($_REQUEST['PPORT'])) {
    if ($_REQUEST['PPORT'] == 'other' &&
        isset($_REQUEST['otherpport']) &&
        $_REQUEST['otherpport'] != '') {
        $CORE_LOCAL->set('printerPort',trim($_REQUEST['otherpport']));
        $otherpport = True;
        $otherpportValue = trim($_REQUEST['otherpport']);
    } else {
        $CORE_LOCAL->set('printerPort',$_REQUEST['PPORT']);
        $otherpport = False;
        $otherpportValue = "";
    }
} else {
    $pport = $CORE_LOCAL->get('printerPort');
    if (isset($pport) && $pport !== False && $pport != "") {
        $pports = array("/dev/lp0","/dev/usb/lp0","LPT1:","fakereceipt.txt");
        if (in_array($pport,$pports)) {
            $otherpport = False;
            $otherpportValue = "";
        } else {
            $otherpport = True;
            $otherpportValue = "$pport";
        }
    } else {
        $otherpport = False;
        $otherpportValue = "";
    }
}
?>
<input type="radio" name=PPORT value="/dev/lp0" id="div-lp0"
    <?php if (!$otherpport && $CORE_LOCAL->get('printerPort')=="/dev/lp0")
            echo "checked";
    ?> /><label for="div-lp0">/dev/lp0 (*nix)</label><br />
<input type="radio" name=PPORT value="/dev/usb/lp0" id="div-usb-lp0"
    <?php if (!$otherpport && $CORE_LOCAL->get('printerPort')=="/dev/usb/lp0")
            echo "checked"; ?> /><label for="div-usb-lp0">/dev/usb/lp0 (*nix)</label><br />
<input type="radio" name=PPORT value="LPT1:" id="lpt1-"
    <?php if (!$otherpport && $CORE_LOCAL->get('printerPort')=="LPT1:")
                echo "checked"; ?> /><label for="lpt1-">LPT1: (windows)</label><br />
<input type="radio" name=PPORT value="fakereceipt.txt" id="fakercpt"
    <?php if (!$otherpport && $CORE_LOCAL->get('printerPort')=="fakereceipt.txt")
                echo "checked";
    ?> /><label for="fakercpt">fakereceipt.txt</label><br />
<input type="radio" name=PPORT value="other"
    <?php if ($otherpport)
                echo "checked";
?> /> <input type=text name="otherpport"
    value="<?php echo "$otherpportValue"; ?>"><br />
<span class='noteTxt' style="top:-110px;"> <?php printf("<p>Current value: <span class='pre'>%s</span></p>",$CORE_LOCAL->get('printerPort')); ?>
Path to the printer. Select from common values, or enter a custom path.
Some ubuntu distros might put your USB printer at /dev/usblp0</span>
</td></tr>
<?php
// Write to database.
InstallUtilities::paramSave('printerPort',$CORE_LOCAL->get('printerPort'));
?>

<tr><td></td><td>
<?php
if (isset($_REQUEST['FRANK'])) $CORE_LOCAL->set('enableFranking',1);
elseif ($CORE_LOCAL->get('enableFranking')==='') $CORE_LOCAL->set('enableFranking',0);
echo "<fieldset class='toggle'>\n<input type='checkbox' name='FRANK' id='enableFranking'";
if ($CORE_LOCAL->get("enableFranking") == 1) echo " value='1' checked";
else echo " value='0'";
echo " />\n<label for='enableFranking' onclick=''>Enable Check Franking: </label>\n
	<span class='toggle-button'></span></fieldset>";
InstallUtilities::paramSave('enableFranking',$CORE_LOCAL->get("enableFranking"));
?>
</select></td></tr>
<tr><td>
<b>Drawer Behavior Module</b>:</td><td>
<?php
$kmods = AutoLoader::listModules('Kicker',True);
if(isset($_REQUEST['kickerModule'])) $CORE_LOCAL->set('kickerModule',$_REQUEST['kickerModule']);
if ($CORE_LOCAL->get('kickerModule')=='') $CORE_LOCAL->set('kickerModule','Kicker');
echo '<select name="kickerModule">';
foreach($kmods as $k){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('kickerModule')==$k?'selected':''),
		$k);
}
echo '</select>';
InstallUtilities::paramSave('kickerModule',$CORE_LOCAL->get('kickerModule'));
?>
</td></tr><tr><td></td><td>
<?php
if (isset($_REQUEST['DDM'])) $CORE_LOCAL->set('dualDrawerMode',1);
elseif (isset($_REQUEST['esubmit'])) $CORE_LOCAL->set('dualDrawerMode',0);
elseif ($CORE_LOCAL->get('dualDrawerMode')==='') $CORE_LOCAL->set('dualDrawerMode',0);
echo "<fieldset class='toggle'>\n<input type='checkbox' name='DDM' id='dualDrawerMode'";
if ($CORE_LOCAL->get("dualDrawerMode") == 1) echo " value='1' checked";
else echo " value='0'";
echo " />\n<label for='dualDrawerMode' onclick=''>Dual Drawer Mode: </label>\n
	<span class='toggle-button'></span></fieldset>";
InstallUtilities::paramSave('dualDrawerMode',$CORE_LOCAL->get("dualDrawerMode"));
?>
</select></td></tr><tr><td>
<b>Scanner/scale port</b>:</td><td>
<?php
if(isset($_REQUEST['SPORT'])) $CORE_LOCAL->set('scalePort',$_REQUEST['SPORT']);
printf("<input type=text name=SPORT value=\"%s\" />",$CORE_LOCAL->get('scalePort'));
InstallUtilities::paramSave('scalePort',$CORE_LOCAL->get('scalePort'));
?>
</td></tr><tr><td colspan=2>
<p>Path to the scanner scale. Common values are COM1 (windows) and /dev/ttyS0 (linux).</p>
</td></tr><tr><td>
<b>Scanner/scale driver</b>:</td><td>
<?php
if(isset($_REQUEST['SDRIVER'])) $CORE_LOCAL->set('scaleDriver',$_REQUEST['SDRIVER']);
printf("<input type=text name=SDRIVER value=\"%s\" />",$CORE_LOCAL->get('scaleDriver'));
InstallUtilities::paramSave('scaleDriver',$CORE_LOCAL->get('scaleDriver'));
?>
</td></tr><tr><td colspan=2>
<p>The name of your scale driver. Known good values include "ssd" and "NewMagellan".</p>
<?php
// try to initialize scale driver
if ($CORE_LOCAL->get("scaleDriver") != ""){
	$classname = $CORE_LOCAL->get("scaleDriver");
	if (!file_exists('../scale-drivers/php-wrappers/'.$classname.'.php'))
		echo "<br /><i>Warning: PHP driver file not found</i>";
	else {
		if (!class_exists($classname))
			include('../scale-drivers/php-wrappers/'.$classname.'.php');
		$instance = new $classname();
		@$instance->SavePortConfiguration($CORE_LOCAL->get("scalePort"));
		@$abs_path = substr($_SERVER['SCRIPT_FILENAME'],0,
				strlen($_SERVER['SCRIPT_FILENAME'])-strlen('install/extra_config.php')-1);
		@$instance->SaveDirectoryConfiguration($abs_path);
	}
}
?>
</td></tr>

<tr><td colspan=2 class="tblHeader">
<h3>Display Settings</h3></td></tr><tr><td>
<tr><td>
<b>Screen Height</b>:</td><td>
<?php
if(isset($_REQUEST['VLINES'])) $CORE_LOCAL->set('screenLines',$_REQUEST['VLINES']);
elseif ($CORE_LOCAL->get('screenLines')==='') $CORE_LOCAL->set('screenLines', 11);
echo '<select name="VLINES">';
for($i=9; $i<20; $i++) {
    printf('<option %s value="%d">%d items</option>',
        ($i == $CORE_LOCAL->get('screenLines') ? 'selected' : ''), $i, $i+1);
}
echo '</select>';
InstallUtilities::paramSave('screenLines', $CORE_LOCAL->get('screenLines'));
?>
<span class='noteTxt'>Number of items to display at once</span>
</td></tr>
<tr><td>
<b>Alert Bar</b>:</td><td>
<?php
if (isset($_REQUEST['ALERT'])) $CORE_LOCAL->set('alertBar',$_REQUEST['ALERT']);
printf("<input size=40 type=text name=ALERT value=\"%s\" />",$CORE_LOCAL->get('alertBar'));
InstallUtilities::paramSave('alertBar',$CORE_LOCAL->get('alertBar'));
?>
</td></tr>
<tr><td>
</td><td>
<?php
if (isset($_REQUEST['LOCKSCREEN'])) $CORE_LOCAL->set('lockScreen',1);
elseif (isset($_REQUEST['esubmit'])) $CORE_LOCAL->set('lockScreen',0);
elseif ($CORE_LOCAL->get('lockScreen')==='') $CORE_LOCAL->set('lockScreen',0);
echo "<fieldset class='toggle'>\n<input type='checkbox' name='LOCKSCREEN' id='lockscreen'";
if ($CORE_LOCAL->get("lockScreen") == 1) echo " value='1' checked";
else echo " value='0'";
echo " />\n<label for='lockscreen' onclick=''>Lock screen on idle: </label>\n
	<span class='toggle-button'></span></fieldset>";
InstallUtilities::paramSave('lockScreen',$CORE_LOCAL->get('lockScreen'));
?>
</td></tr>
<tr><td>
<b>Lock Screen Timeout</b>:</td><td>
<?php
if(isset($_REQUEST['TIMEOUT'])) $CORE_LOCAL->set('timeout',$_REQUEST['TIMEOUT']);
elseif ($CORE_LOCAL->get('timeout')==='') $CORE_LOCAL->set('timeout',180000);
printf("<input type=text name=TIMEOUT value=\"%s\" />",$CORE_LOCAL->get('timeout'));
InstallUtilities::paramSave('timeout',$CORE_LOCAL->get('timeout'));
?>
<span class='noteTxt'>Enter timeout in milliseconds. Default: 180000 (3 minutes)</span>
</td></tr>
<tr><td>
<b>Footer Modules</b> (left to right):</td><td>
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
$footer_mods = AutoLoader::listModules('FooterBox');
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
InstallUtilities::paramSave('FooterModules',$current_mods);
?>
</td></tr>
<tr><td>
<b>Notifier Modules</b>:</td><td>
<?php
// get current settings
$current_mods = $CORE_LOCAL->get("notifiers");
// replace w/ form post if needed
// fill in defaults if missing
if (isset($_REQUEST['NOTIFIERS'])) $current_mods = $_REQUEST['NOTIFIERS'];
elseif(!is_array($current_mods)) {
	$current_mods = array();
}
$notifiers = AutoLoader::listModules('Notifier');
echo '<select name="NOTIFIERS[]" size="5" multiple>';
foreach($notifiers as $nm){
    printf('<option %s>%s</option>',
        (in_array($nm, $current_mods)?'selected':''),$nm);
}
echo '</select><br />';
InstallUtilities::paramSave('Notifiers',$current_mods);
?>
<span class='noteTxt'>Notifiers are displayed on the right below the scale weight</span>
</td></tr>
<tr><td>
<b>Enable onscreen keys</b>:</td><td> <select name=SCREENKEYS>
<?php
if(isset($_REQUEST['SCREENKEYS'])){
	$CORE_LOCAL->set('touchscreen',($_REQUEST['SCREENKEYS']==1)?True:False,True);
}
if ($CORE_LOCAL->get('touchscreen')){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0 >No</option>";
	InstallUtilities::paramSave('touchscreen','True');
}
else {
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
	InstallUtilities::paramSave('touchscreen','False');
}
?>
</select></td></tr><tr><td>
<b>Separate customer display</b>:</td><td> <select name=CUSTDISPLAY>
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
InstallUtilities::paramSave('CustomerDisplay',$CORE_LOCAL->get('CustomerDisplay'));
?>
</select></td></tr><tr><td colspan=2>
<p>Touchscreen keys and menus really don't need to appear on
the customer-facing display. Experimental feature where one
window always shows the item listing. Very alpha.</p>
</td></tr>


<tr><td colspan=2 class="tblHeader"><h3>Subtotal Settings</h3></td></tr>
<!-- Normal/default Yes/True -->
<tr><td><b>Member ID trigger subtotal</b>:</td><td>
<?php
// Get the value from the latest submit, if it existed, into the core_local array ...
if (array_key_exists('MEMBER_SUBTOTAL', $_REQUEST)) {
	$CORE_LOCAL->set('member_subtotal',($_REQUEST['MEMBER_SUBTOTAL']==1)?True:False);
} else if ($CORE_LOCAL->get('member_subtotal') === '') {
    $CORE_LOCAL->set('member_subtotal', true);
}
echo "<select name='MEMBER_SUBTOTAL'>";
// Display current, or default, value.
if ($CORE_LOCAL->get('member_subtotal')){
	echo "<option value='1' selected>Yes</option>";
	echo "<option value='0' >No</option>";
	// Save current, or default, value.  After submit, this will be the new value.
	InstallUtilities::paramSave('member_subtotal', 'True');
} else {
	echo "<option value='1' >Yes</option>";
	echo "<option value='0' selected>No</option>";
	// Save current, or default, value.  After submit, this will be the new value.
	InstallUtilities::paramSave('member_subtotal', 'False');
}
?>
</td></tr>
<tr><td><b>Subtotal Actions</b></td>
<td rowspan="2"><select name="TotalActions[]" size="10" multiple>
<?php
$mods = AutoLoader::listModules('TotalAction');
$current = $CORE_LOCAL->get('TotalActions');
if (isset($_REQUEST['TotalActions'])) {
    $current = $_REQUEST['TotalActions'];
}
if (!is_array($current)) {
    $current = array();
}
foreach($mods as $mod) {
    printf('<option %s>%s</option>',
        (in_array($mod, $current) ? 'selected' : ''),
        $mod);
}
InstallUtilities::paramSave('TotalActions', $current);
?>
</select></td></tr>
<tr><td>These are additional bits of functionality that
will occur whenever a transaction is subtotalled.</td></tr>


<tr><td colspan=2 class="tblHeader"><h3>Tender Settings</h3></td></tr>
<tr><td>
<b>Allow members to write checks over purchase amount</b>: </td><td><select name=OVER>
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
InstallUtilities::paramSave('cashOverLimit',$CORE_LOCAL->get('cashOverLimit'));
?>
</select></td></tr>
<tr><td>
<b>Check over limit</b>:</td><td>$
<?php
if(isset($_REQUEST['OVER_LIMIT'])) $CORE_LOCAL->set('dollarOver',$_REQUEST['OVER_LIMIT']);
printf("<input type=text size=4 name=OVER_LIMIT value=\"%s\" />",$CORE_LOCAL->get('dollarOver'));
InstallUtilities::paramSave('dollarOver',$CORE_LOCAL->get('dollarOver'));
?>
</td></tr>
<tr><td>
<b>EBT Total Default</b>: </td><td><select name=FNTL_DEFAULT>
<?php
if(isset($_REQUEST['FNTL_DEFAULT'])) $CORE_LOCAL->set('fntlDefault',$_REQUEST['FNTL_DEFAULT']);
if ($CORE_LOCAL->get('fntlDefault') === '') $CORE_LOCAL->set('fntlDefault', 1);
if ($CORE_LOCAL->get("fntlDefault") == 1) {
	echo "<option value=1 selected>Cash Side</option>";
	echo "<option value=0 >Food Side</option>";
} else {
	echo "<option value=1 >Cash Side</option>";
	echo "<option value=0 selected>Food Side</option>";
}
InstallUtilities::paramSave('fntlDefault', $CORE_LOCAL->get('fntlDefault'));
?>
</select></td></tr>
<tr><td>
<b>Tender Report</b>:</td>
<td><select name="TENDERREPORTMOD">
<?php
if(isset($_REQUEST['TENDERREPORTMOD'])) $CORE_LOCAL->set('TenderReportMod',$_REQUEST['TENDERREPORTMOD']);
if($CORE_LOCAL->get('TenderReportMod')=='') $CORE_LOCAL->set('TenderReportMod','DefaultTenderReport');
$mods = AutoLoader::listModules('TenderReport');
sort($mods);
foreach($mods as $mod){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('TenderReportMod') == $mod ? 'selected' : ''),
		$mod
	);
}
InstallUtilities::paramSave('TenderReportMod',$CORE_LOCAL->get('TenderReportMod'));
?>
</select></td></tr><tr><td>
<b>Tender Mapping</b>:<br />
<p>Map custom tenders to IS4Cs expected tenders Tender Rpt. column: Include the checked tenders 
	in the Tender Report (available via Mgrs. Menu [MG])</p></td><td>
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
	InstallUtilities::paramSave('TenderMap',$settings);
}
$mods = AutoLoader::listModules('TenderModule');
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
		$saveStr2 .= "'".$code2."'=>'".addslashes($name2)."',";
	}
	$saveStr2 = rtrim($saveStr2,",").")";
	InstallUtilities::paramSave('TRDesiredTenders',$settings2);
} //end TR desired tenders
$db = Database::pDataConnect();
$res = $db->query("SELECT TenderCode, TenderName FROM tenders ORDER BY TenderName");
?>
<table cellspacing="0" cellpadding="4" border="1">
<?php
echo "<thead><tr><th>Tender Name</th><th>Map To</th><th>Tender Rpt</th></tr></thead><tbody>\n";
while($row = $db->fetch_row($res)){
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
	if (array_key_exists($row['TenderCode'], $settings2)) echo " CHECKED";
	echo "></td></tr></tbody>";
}
?>
</table>

</td></tr><tr><td colspan=2 class="tblHeader">
<h3>Integrated Card Processing</h3>
<p><i>Integrated card processing configuration is included for the sake
of completeness. The modules themselves require individual configuration,
too</i></p></td></tr><tr><td>
<b>Integrated Credit Cards</b>: </td><td><select name=INT_CC>
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
InstallUtilities::paramSave('CCintegrate',$CORE_LOCAL->get('CCintegrate'));
?>
</select></td></tr><tr><td>
<b>Integrated Gift Cards</b>: </td><td><select name=INT_GC>
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
InstallUtilities::paramSave('gcIntegrate',$CORE_LOCAL->get('gcIntegrate'));
?>
</select></td></tr><tr><td>
<b>Enabled paycard modules</b>:</td><td>
<select multiple size=10 name=PAY_MODS[]>
<?php
if (isset($_REQUEST['PAY_MODS'])) $CORE_LOCAL->set('RegisteredPaycardClasses',$_REQUEST['PAY_MODS'],True);

$mods = array();
$dh = opendir('../plugins/Paycards/');
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
InstallUtilities::paramSave('RegisteredPaycardClasses',$CORE_LOCAL->get('RegisteredPaycardClasses'));
?>
</select></td></tr><tr><td>
<b>Signature Required Limit</b>:</td><td>
<?php
if (isset($_REQUEST['CCSigLimit'])) $CORE_LOCAL->set('CCSigLimit',$_REQUEST['CCSigLimit']);
if ($CORE_LOCAL->get('CCSigLimit')=="") $CORE_LOCAL->set('CCSigLimit',0.00);
printf(" \$<input size=4 type=text name=CCSigLimit value=\"%s\" />",$CORE_LOCAL->get('CCSigLimit'));
InstallUtilities::paramSave('CCSigLimit',$CORE_LOCAL->get('CCSigLimit'));
?>
</td></tr>
<!--
<tr><td colspan=2 class="tblHeader">
<h3>Various</h3>
<p>This group was started in order to handle variations as options rather than per-coop code variations.</p>
<h4 style="margin: 0.25em 0.0em 0.25em 0.0em;">Related to transactions:</h4></td></tr>
-->

<tr><td colspan=2 class="submitBtn">
<input type=submit name=esubmit value="Save Changes" />
</td></tr>
</table>
</form>
</div> <!--	wrapper -->
</body>
</html>
