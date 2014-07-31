<!DOCTYPE html>
<html>
<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include('../ini.php');
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
<tr>
    <td colspan=2 class="tblHeader"><h3>General Settings</h3></td>
</tr>
<tr>
    <td style="width: 30%;"><b>Organization</b>:</td>
    <td>
    <?php echo InstallUtilities::installTextField('store', ''); ?>
    <span class='noteTxt'>In theory, any hard-coded, organization specific sequences should be blocked
    off based on the organization setting. Adherence to this principle is less than ideal.</span>
    </td>
</tr>
<tr>
    <td></td>
    <td>
    <?php echo InstallUtilities::installCheckBoxField('discountEnforced', 'Discounts Enabled', 0); ?>
    <span class='noteTxt'>If yes, members get a percentage discount as specified in custdata.</span>
    </td>
</tr>
<tr>
    <td><label><b>Discount Module</b></label></td>
    <td> 
    <?php
    $mods = AutoLoader::listModules('DiscountModule',True);
    echo InstallUtilities::installSelectField('DiscountModule', $mods, 'DiscountModule');
    ?>
    <span class='noteTxt'>Calculates actual discount amount</span>
    </td>
</tr>
<tr>
    <td></td>
    <td> 
    <?php echo InstallUtilities::installCheckBoxField('refundDiscountable', 'Discounts on refunds', 0); ?>
    <span class='noteTxt'>If yes, percent discount is applied to refunds</span>
    </td>
</tr>
<tr>
    <td><b>Line Item Discount (member)</b>: </td>
    <td>
    <?php echo InstallUtilities::installTextField('LineItemDiscountMem', 0); ?>
    <span class='noteTxt'>(percentage; 0.05 =&gt; 5%)</span>
    </td>
</tr>
<tr>
    <td><b>Line Item Discount (non-member)</b>: </td>
    <td>
    <?php echo InstallUtilities::installTextField('LineItemDiscountNonMem', 0); ?>
    <span class='noteTxt'>(percentage; 0.05 =&gt; 5%)</span>
    </td>
</tr>
<tr>
    <td><b>Default Non-member #</b>: </td>
    <td>
    <?php echo InstallUtilities::installTextField('defaultNonMem', 99999); ?>
    <span class='noteTxt'>Normally a single account number is used for most if not all non-member
    transactions. Specify that account number here.</span>
    </td>
</tr>
<tr>
    <td><b>Default Non-member behavior</b>: </td><td>
    <?php
    $behavior = array('1' => 'Cannot override other accounts', '0' => 'No different than other accounts');
    echo InstallUtilities::installSelectField('RestrictDefaultNonMem', $behavior, 0);
    ?>
    </td>
</tr>
<tr>
    <td><b>Visiting Member #</b>: </td>
    <td><?php echo InstallUtilities::installTextField('visitingMem', ''); ?>
    <span class='noteTxt'>This account provides members of other co-ops with member pricing
    but no other benefits. Leave blank to disable.</span>
    </td>
</tr>
<tr>
    <td></td>
    <td>
    <?php echo InstallUtilities::installCheckBoxField('memlistNonMember', 'Show non-member', 0); ?>
    <span class='noteTxt'>Display non-member acct. in member searches?</span>
    </td>
</tr>
<tr>
    <td></td>
    <td>
    <?php echo InstallUtilities::installCheckBoxField('useMemTypeTable', 'Use memtype table', 0); ?>
    <span class='noteTxt'>Use memtype table when applicable. This forces all memberships of a given
    type to have the same discount, among other things.</span>
    </td>
</tr>
<tr>
    <td><b>Bottle Return Department number</b>: </td>
    <td><?php echo InstallUtilities::installTextField('BottleReturnDept', ''); ?>
    <span class='noteTxt'>Add a BOTTLE RETURN item to your products table with a normal_price of 0, 
    CORE will prompt for Bottle Return amt. and then make it a negative value.</span>
    </td>
</tr>
<tr>
    <td colspan=2 class="tblHeader">
    <h3>Hardware Settings</h3>
    </td>
</tr>
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

<tr>
    <td></td>
    <td>
    <?php echo InstallUtilities::installCheckBoxField('enableFranking', 'Enable Check Franking', 0); ?>
    </td>
</tr>
<tr>
    <td><b>Drawer Behavior Module</b>:</td>
    <td>
    <?php
    $kmods = AutoLoader::listModules('Kicker',True);
    echo InstallUtilities::installSelectField('kickerModule', $kmods, 'Kicker');
    ?>
    </td>
</tr>
<tr>
    <td></td>
    <td>
    <?php echo InstallUtilities::installCheckBoxField('dualDrawerMode', 'Dual Drawer Mode', 0); ?>
    </td>
</tr>
<tr>
    <td><b>Scanner/scale port</b>:</td>
    <td><?php echo InstallUtilities::installTextField('scalePort', ''); ?></td>
</tr>
<tr>
    <td colspan=2>
    <p>Path to the scanner scale. Common values are COM1 (windows) and /dev/ttyS0 (linux).</p>
    </td>
</tr>
<tr>
    <td><b>Scanner/scale driver</b>:</td>
    <td><?php echo InstallUtilities::installSelectField('scaleDriver', array('NewMagellan', 'ssd'), 'NewMagellan'); ?></td>
</tr>
<tr>
    <td colspan=2>
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
    </td>
</tr>
<tr>
    <td colspan=2 class="tblHeader">
    <h3>Display Settings</h3>
    </td>
</tr>
<tr>
    <td><b>Screen Height</b>:</td>
    <td><?php echo InstallUtilities::installSelectField('screenLines', range(9, 19), 11); ?>
    <span class='noteTxt'>Number of items to display at once</span>
    </td>
</tr>
<tr>
    <td><b>Alert Bar</b>:</td>
    <td><?php echo InstallUtilities::installTextField('alertBar', ''); ?></td>
</tr>
<tr>
    <td></td>
    <td><?php echo InstallUtilities::installCheckBoxField('lockScreen', 'Lock screen on idle', 0); ?></td>
</tr>
<tr>
    <td><b>Lock Screen Timeout</b>:</td>
    <td><?php echo InstallUtilities::installTextField('timeout', 180000); ?>
    <span class='noteTxt'>Enter timeout in milliseconds. Default: 180000 (3 minutes)</span>
    </td>
</tr>
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
<tr>
    <td><b>Notifier Modules</b>:</td>
    <td>
    <?php
    // get current settings
    $notifiers = AutoLoader::listModules('Notifier');
    echo InstallUtilities::installSelectField('Notifiers', 
        $notifiers, 
        array(), 
        InstallUtilities::EITHER_SETTING, 
        true, 
        array('size'=>5,'multiple'=>'multiple')
    );
    ?>
    <span class='noteTxt'>Notifiers are displayed on the right below the scale weight</span>
    </td>
</tr>
<tr>
    <td><b>Enable onscreen keys</b>:</td>
    <td><?php echo InstallUtilities::installSelectField('touchscreen', array(true=>'Yes', false=>'No'), false); ?></td>
</tr>
<tr>
    <td><b>Separate customer display</b>:</td>
    <td><?php echo InstallUtilities::installSelectField('CustomerDisplay', array(1=>'Yes', 0=>'No'), 0); ?></td>
</tr>
<tr>
    <td colspan=2>
    <p>Touchscreen keys and menus really don't need to appear on
    the customer-facing display. Experimental feature where one
    window always shows the item listing. Very alpha.</p>
    </td>
</tr>
<tr>
    <td colspan=2 class="tblHeader"><h3>Subtotal Settings</h3></td>
</tr>
<!-- Normal/default Yes/True -->
<tr>
    <td><b>Member ID trigger subtotal</b>:</td>
    <td><?php echo InstallUtilities::installSelectField('member_subtotal', array(true=>'Yes', false=>'No'), true); ?></td>
</tr>
<tr>
    <td><b>Subtotal Actions</b></td>
    <td rowspan="2">
    <?php
    $mods = AutoLoader::listModules('TotalAction');
    echo InstallUtilities::installSelectField('TotalActions',
        $mods,
        array(),
        InstallUtilities::EITHER_SETTING,
        true,
        array('multiple'=>'multiple', 'size'=>5)
    );
    ?>
    </td>
</tr>
<tr>
    <td>These are additional bits of functionality that
    will occur whenever a transaction is subtotalled.</td>
</tr>
<tr>
    <td colspan=2 class="tblHeader"><h3>Tender Settings</h3></td>
</tr>
<tr>
    <td><b>Allow members to write checks over purchase amount</b>: </td>
    <td><?php echo InstallUtilities::installSelectField('cashOverLimit', array(1=>'Yes',0=>'No'), 0); ?></td>
</tr>
<tr>
    <td><b>Check over limit</b>:</td>
    <td>$<?php echo InstallUtilities::installTextField('dollarOver', 0); ?></td>
</tr>
<tr>
    <td><b>EBT Total Default</b>: </td>
    <td>
    <?php 
    $ebtOpts = array(1 => 'Cash Side', 0 => 'Food Side');
    echo InstallUtilities::installselectField('fntlDefault', $ebtOpts, 1);
    ?>
    </td>
</tr>
<tr>
    <td><b>Tender Report</b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('TenderReport');
    sort($mods);
    echo InstallUtilities::installSelectField('TenderReportMod', $mods, 'DefaultTenderReport');
    ?>
    </td>
</tr>
<tr><td>
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
