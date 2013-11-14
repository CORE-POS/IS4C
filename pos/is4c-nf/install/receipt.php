<!DOCTYPE html>
<html>
<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
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
<tr><td colspan=2 class="tblHeader">
<h3>Receipt Settings</h3></td></tr>
<tr><td style="width: 30%;">
</td><td>
<?php
if (isset($_REQUEST['PRINT'])) $CORE_LOCAL->set('print',1,True);
elseif (isset($_REQUEST['esubmit'])) $CORE_LOCAL->set('print',0,True);
elseif ($CORE_LOCAL->get('print')==='') $CORE_LOCAL->set('print',0,True);
echo "<fieldset class='toggle'>\n<input type='checkbox' name='PRINT' id='printing'";
if ($CORE_LOCAL->get("print") == 1) echo " checked";
echo " />\n<label for='printing' onclick=''>Enable receipts: </label>\n
	<span class='toggle-button'></span></fieldset>";
InstallUtilities::paramSave('print',$CORE_LOCAL->get("print"));
?>
</td></tr><tr><td>
<b>Use new receipt</b>: </td><td><select name=NEWRECEIPT>
<?php
if (isset($_REQUEST['NEWRECEIPT'])) $CORE_LOCAL->set('newReceipt',$_REQUEST['NEWRECEIPT']);
if ($CORE_LOCAL->get("newReceipt") == 2){
	echo "<option value=2 selected>PHP (even newer)</option>";
	echo "<option value=1>Yes</option>";
	echo "<option value=0>No</option>";
}
elseif ($CORE_LOCAL->get("newReceipt") == 1){
	echo "<option value=2>PHP (even newer)</option>";
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0>No</option>";
}
else {
	echo "<option value=2>PHP (even newer)</option>";
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
}
InstallUtilities::paramSave('newReceipt',$CORE_LOCAL->get("newReceipt"));
?>
</select>
<span class='noteTxt'>The new receipt groups items by category; the old one just lists
them in order.</span></td></tr>
<tr>
	<td><b>Receipt Driver</b>:</td>
	<td>
	<select name="ReceiptDriver">
<?php
if (isset($_REQUEST['ReceiptDriver'])) $CORE_LOCAL->set('ReceiptDriver',$_REQUEST['ReceiptDriver']);
elseif($CORE_LOCAL->get('ReceiptDriver') === '') $CORE_LOCAL->set('ReceiptDriver','ESCPOSPrintHandler');
$mods = AutoLoader::listModules('PrintHandler',True);
foreach($mods as $m){
	printf('<option %s>%s</option>',
		($m==$CORE_LOCAL->get('ReceiptDriver')?'selected':''),
		$m);
}
InstallUtilities::paramSave('ReceiptDriver',$CORE_LOCAL->get("ReceiptDriver"));
?>
	</select>
	<span class="noteTxt"></span>
	</td>
</tr>
<tr><td>
<b>Email Receipt Sender</b>:</td><td>
<?php
if(isset($_REQUEST['emailReceiptFrom'])) $CORE_LOCAL->set('emailReceiptFrom',$_REQUEST['emailReceiptFrom']);
printf("<input type=text name=emailReceiptFrom value=\"%s\" />",$CORE_LOCAL->get('emailReceiptFrom'));
InstallUtilities::paramSave('emailReceiptFrom',$CORE_LOCAL->get('emailReceiptFrom'));
?>
</td></tr>
<tr><td colspan="2"><h3>PHP Receipt Modules</h3></td></tr>
<tr><td><b>Data Fetch Mod</b>:</td>
<td><select name="RBFETCHDATA">
<?php
if(isset($_REQUEST['RBFETCHDATA'])) $CORE_LOCAL->set('RBFetchData',$_REQUEST['RBFETCHDATA']);
if($CORE_LOCAL->get('RBFetchData')=='') $CORE_LOCAL->set('RBFetchData','DefaultReceiptDataFetch');
$mods = AutoLoader::listModules('DefaultReceiptDataFetch',True);
sort($mods);
foreach($mods as $mod){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('RBFetchData') == $mod ? 'selected' : ''),
		$mod
	);
}
InstallUtilities::paramSave('RBFetchData',$CORE_LOCAL->get('RBFetchData'));
?>
</select></td></tr>
<tr><td><b>Filtering Mod</b>:</td>
<td><select name="RBFILTER">
<?php
if(isset($_REQUEST['RBFILTER'])) $CORE_LOCAL->set('RBFilter',$_REQUEST['RBFILTER']);
if($CORE_LOCAL->get('RBFilter')=='') $CORE_LOCAL->set('RBFilter','DefaultReceiptFilter');
$mods = AutoLoader::listModules('DefaultReceiptFilter',True);
sort($mods);
foreach($mods as $mod){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('RBFilter') == $mod ? 'selected' : ''),
		$mod
	);
}
InstallUtilities::paramSave('RBFilter',$CORE_LOCAL->get('RBFilter'));
?>
</select></td></tr>
<tr><td><b>Sorting Mod</b>:</td>
<td><select name="RBSORT">
<?php
if(isset($_REQUEST['RBSORT'])) $CORE_LOCAL->set('RBSort',$_REQUEST['RBSORT']);
if($CORE_LOCAL->get('RBSort')=='') $CORE_LOCAL->set('RBSort','DefaultReceiptSort');
$mods = AutoLoader::listModules('DefaultReceiptSort',True);
sort($mods);
foreach($mods as $mod){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('RBSort') == $mod ? 'selected' : ''),
		$mod
	);
}
InstallUtilities::paramSave('RBSort',$CORE_LOCAL->get('RBSort'));
?>
</select></td></tr>
<tr><td><b>Tagging Mod</b>:</td>
<td><select name="RBTAG">
<?php
if(isset($_REQUEST['RBTAG'])) $CORE_LOCAL->set('RBTag',$_REQUEST['RBTAG']);
if($CORE_LOCAL->get('RBTag')=='') $CORE_LOCAL->set('RBTag','DefaultReceiptTag');
$mods = AutoLoader::listModules('DefaultReceiptTag',True);
sort($mods);
foreach($mods as $mod){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('RBTag') == $mod ? 'selected' : ''),
		$mod
	);
}
InstallUtilities::paramSave('RBTag',$CORE_LOCAL->get('RBTag'));
?>
</select></td></tr>
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
