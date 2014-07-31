<!DOCTYPE html>
<html>
<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include('../ini.php');
include('InstallUtilities.php');
?>
<head>
<title>IT CORE Lane Installation: Receipt Configuration</title>
<link rel="stylesheet" href="../css/toggle-switch.css" type="text/css" />
<script type="text/javascript" src="../js/jquery.js"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">	
<h2>IT CORE Lane Installation: Receipt Configuration</h2>

<div class="alert"><?php InstallUtilities::checkWritable('../ini.php', False, 'PHP'); ?></div>
<div class="alert"><?php InstallUtilities::checkWritable('../ini-local.php', True, 'PHP'); ?></div>

<form action=receipt.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4>
<tr>
    <td colspan=2 class="tblHeader">
    <h3>Receipt Settings</h3>
    </td>
</tr>
<tr>
    <td style="width: 30%;"></td>
    <td><?php echo InstallUtilities::installCheckBoxField('print', 'Enable receipts', 0); ?></td>
</tr>
<tr>
    <td><b>Receipt Type</b>: </td>
    <td>
    <?php
    $receipts = array(
        2 => 'Modular',
        1 => 'Grouped (static, legacy)',
        0 => 'In Order (static, legacy)',
    );
    echo InstallUtilities::installSelectField('newReceipt', $receipts, 2);
    ?>
    <span class='noteTxt'>
    The Modular receipt uses the modules below to assemble the receipt's contents.
    The Grouped option groups items together in categories. The In Order option
    simply prints items in the order they were entered. The default set of modulars
    will group items in categories. The InOrder modules will print items in order.
    Legacy options may not be supported in the future.
    </span>
    </td>
</tr>
<tr>
	<td><b>Receipt Driver</b>:</td>
	<td>
    <?php
    $mods = AutoLoader::listModules('PrintHandler',True);
    echo InstallUtilities::installSelectField('ReceiptDriver', $mods, 'ESCPOSPrintHandler');
    ?>
	<span class="noteTxt"></span>
	</td>
</tr>
<tr>
    <td><b>Email Receipt Sender</b>:</td>
    <td><?php echo InstallUtilities::installTextField('emailReceiptFrom', ''); ?></td>
</tr>
<tr>
    <td colspan="2"><h3>PHP Receipt Modules</h3></td>
</tr>
<tr>
    <td><b>Data Fetch Mod</b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('DefaultReceiptDataFetch', true);
    sort($mods);
    echo InstallUtilities::installSelectField('RBFetchData', $mods, 'DefaultReceiptDataFetch');
    ?>
    </td>
</tr>
<tr>
    <td><b>Filtering Mod</b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('DefaultReceiptFilter',True);
    sort($mods);
    echo InstallUtilities::installSelectField('RBFilter', $mods, 'DefaultReceiptFilter');
    ?>
    </td>
</tr>
<tr>
    <td><b>Sorting Mod</b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('DefaultReceiptSort',True);
    sort($mods);
    echo InstallUtilities::installSelectField('RBSort', $mods, 'DefaultReceiptSort');
    ?>
    </td>
</tr>
<tr>
    <td><b>Tagging Mod</b>:</td>
    <td>
    <?php
    $mods = AutoLoader::listModules('DefaultReceiptTag',True);
    sort($mods);
    echo InstallUtilities::installSelectField('RBTag', $mods, 'DefaultReceiptTag');
    ?>
    </td>
</tr>
<tr><td colspan="2"><h3>Message Modules</h3></td></tr>
<tr><td colspan="3">
<p>Message Modules provide special blocks of text on the end
of the receipt &amp; special non-item receipt types.</p>
</td></tr>
<tr><td>&nbsp;</td><td>
<?php
if (isset($_REQUEST['RM_MODS'])){
	$mods = array();
	foreach($_REQUEST['RM_MODS'] as $m){
		if ($m != '') $mods[] = $m;
	}
	$CORE_LOCAL->set('ReceiptMessageMods', $mods);
}
if (!is_array($CORE_LOCAL->get('ReceiptMessageMods'))){
	$CORE_LOCAL->set('ReceiptMessageMods', array());
}
$available = AutoLoader::listModules('ReceiptMessage');
$current = $CORE_LOCAL->get('ReceiptMessageMods');
for($i=0;$i<=count($current);$i++){
	$c = isset($current[$i]) ? $current[$i] : '';
	echo '<select name="RM_MODS[]">';
	echo '<option value="">[None]</option>';
	foreach($available as $a)
		printf('<option %s>%s</option>',($a==$c?'selected':''),$a);
	echo '</select><br />';
}
InstallUtilities::paramSave('ReceiptMessageMods',$CORE_LOCAL->get('ReceiptMessageMods'));
?>
</td></tr>
<tr><td colspan=2 class="submitBtn">
<input type=submit name=esubmit value="Save Changes" />
</td></tr>
</table>
</form>
</div> <!--	wrapper -->
</body>
</html>
