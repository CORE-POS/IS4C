<!DOCTYPE html>
<html>
<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::LoadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('util.php');
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

<div class="alert"><?php check_writeable('../ini.php'); ?></div>
<div class="alert"><?php check_writeable('../ini-local.php'); ?></div>

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
confsave('print',$CORE_LOCAL->get("print"));
?>
</td></tr><tr><td>
<b>Use new receipt</b>: </td><td><select name=NEWRECEIPT>
<?php
if (isset($_REQUEST['NEWRECEIPT'])) $CORE_LOCAL->set('newReceipt',$_REQUEST['NEWRECEIPT'],True);
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
confsave('newReceipt',$CORE_LOCAL->get("newReceipt"));
?>
</select>
<span class='noteTxt'>The new receipt groups items by category; the old one just lists
them in order.</span></td></tr><tr><td>
<b>Email Receipt Sender</b>:</td><td>
<?php
if(isset($_REQUEST['emailReceiptFrom'])) $CORE_LOCAL->set('emailReceiptFrom',$_REQUEST['emailReceiptFrom'],True);
printf("<input type=text name=emailReceiptFrom value=\"%s\" />",$CORE_LOCAL->get('emailReceiptFrom'));
confsave('emailReceiptFrom',"'".$CORE_LOCAL->get('emailReceiptFrom')."'");
?>
</td></tr>
<tr><td colspan="2"><h3>PHP Receipt Modules</h3></td></tr>
<tr><td><b>Data Fetch Mod</b>:</td>
<td><select name="RBFETCHDATA">
<?php
if(isset($_REQUEST['RBFETCHDATA'])) $CORE_LOCAL->set('RBFetchData',$_REQUEST['RBFETCHDATA'],True);
if($CORE_LOCAL->get('RBFetchData')=='') $CORE_LOCAL->set('RBFetchData','DefaultReceiptDataFetch',True);
$mods = AutoLoader::ListModules('DefaultReceiptDataFetch',True);
sort($mods);
foreach($mods as $mod){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('RBFetchData') == $mod ? 'selected' : ''),
		$mod
	);
}
confsave('RBFetchData',"'".$CORE_LOCAL->get('RBFetchData')."'");
?>
</select></td></tr>
<tr><td><b>Filtering Mod</b>:</td>
<td><select name="RBFILTER">
<?php
if(isset($_REQUEST['RBFILTER'])) $CORE_LOCAL->set('RBFilter',$_REQUEST['RBFILTER'],True);
if($CORE_LOCAL->get('RBFilter')=='') $CORE_LOCAL->set('RBFilter','DefaultReceiptFilter',True);
$mods = AutoLoader::ListModules('DefaultReceiptFilter',True);
sort($mods);
foreach($mods as $mod){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('RBFilter') == $mod ? 'selected' : ''),
		$mod
	);
}
confsave('RBFilter',"'".$CORE_LOCAL->get('RBFilter')."'");
?>
</select></td></tr>
<tr><td><b>Sorting Mod</b>:</td>
<td><select name="RBSORT">
<?php
if(isset($_REQUEST['RBSORT'])) $CORE_LOCAL->set('RBSort',$_REQUEST['RBSORT'],True);
if($CORE_LOCAL->get('RBSort')=='') $CORE_LOCAL->set('RBSort','DefaultReceiptSort',True);
$mods = AutoLoader::ListModules('DefaultReceiptSort',True);
sort($mods);
foreach($mods as $mod){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('RBSort') == $mod ? 'selected' : ''),
		$mod
	);
}
confsave('RBSort',"'".$CORE_LOCAL->get('RBSort')."'");
?>
</select></td></tr>
<tr><td><b>Tagging Mod</b>:</td>
<td><select name="RBTAG">
<?php
if(isset($_REQUEST['RBTAG'])) $CORE_LOCAL->set('RBTag',$_REQUEST['RBTAG'],True);
if($CORE_LOCAL->get('RBTag')=='') $CORE_LOCAL->set('RBTag','DefaultReceiptTag',True);
$mods = AutoLoader::ListModules('DefaultReceiptTag',True);
sort($mods);
foreach($mods as $mod){
	printf('<option %s>%s</option>',
		($CORE_LOCAL->get('RBTag') == $mod ? 'selected' : ''),
		$mod
	);
}
confsave('RBTag',"'".$CORE_LOCAL->get('RBTag')."'");
?>
</select></td></tr>
<tr><td style="width: 30%;">
<tr><td colspan=2 class="submitBtn">
<input type=submit name=esubmit value="Save Changes" />
</td></tr>
</table>
</form>
</div> <!--	wrapper -->
</body>
</html>
