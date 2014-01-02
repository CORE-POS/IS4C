<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
CoreState::loadParams();
include('InstallUtilities.php');
?>
<html>
<head>
<title>Security configuration options</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Security</h2>
<form action=security.php method=post>
<table id="install" border=0 cellspacing=0 cellpadding=4><tr><td>
<b>Cancel Transaction</b>: </td><td><select name=PRIV_CANCEL>
<?php
if(isset($_REQUEST['PRIV_CANCEL'])) $CORE_LOCAL->set('SecurityCancel',$_REQUEST['PRIV_CANCEL']);
if ($CORE_LOCAL->get("SecurityCancel")=="") $CORE_LOCAL->set("SecurityCancel",20);
if ($CORE_LOCAL->get("SecurityCancel") == 30){
	echo "<option value=30 selected>Admin only</option>";
	echo "<option value=20>All</option>";
}
else {
	echo "<option value=30 >Admin only</option>";
	echo "<option value=20 selected>All</option>";
}
InstallUtilities::paramSave('SecurityCancel',$CORE_LOCAL->get("SecurityCancel"));
?>
</select></td></tr>
<tr><td>
<b>Suspend/Resume</b>: </td><td><select name=PRIV_SR>
<?php
if(isset($_REQUEST['PRIV_SR'])) $CORE_LOCAL->set('SecuritySR',$_REQUEST['PRIV_SR']);
if ($CORE_LOCAL->get("SecuritySR")=="") $CORE_LOCAL->set("SecuritySR",20);
if ($CORE_LOCAL->get("SecuritySR") == 30){
	echo "<option value=30 selected>Admin only</option>";
	echo "<option value=20>All</option>";
}
else {
	echo "<option value=30 >Admin only</option>";
	echo "<option value=20 selected>All</option>";
}
InstallUtilities::paramSave('SecuritySR',$CORE_LOCAL->get("SecuritySR"));
?>
</select></td></tr>
<tr><td>
<b>Print Tender Report</b>: </td><td><select name=PRIV_TR>
<?php
if(isset($_REQUEST['PRIV_TR'])) $CORE_LOCAL->set('SecurityTR',$_REQUEST['PRIV_TR']);
if ($CORE_LOCAL->get("SecurityTR")=="") $CORE_LOCAL->set("SecurityTR",20);
if ($CORE_LOCAL->get("SecurityTR") == 30){
	echo "<option value=30 selected>Admin only</option>";
	echo "<option value=20>All</option>";
}
else {
	echo "<option value=30 >Admin only</option>";
	echo "<option value=20 selected>All</option>";
}
InstallUtilities::paramSave('SecurityTR',$CORE_LOCAL->get("SecurityTR"));
?>
</select></td></tr>
<tr><td>
<b>Refund Item</b>: </td><td><select name=PRIV_REFUND>
<?php
if(isset($_REQUEST['PRIV_REFUND'])) $CORE_LOCAL->set('SecurityRefund',$_REQUEST['PRIV_REFUND']);
if ($CORE_LOCAL->get("SecurityRefund")=="") $CORE_LOCAL->set("SecurityRefund",20);
if ($CORE_LOCAL->get("SecurityRefund") == 30){
	echo "<option value=30 selected>Admin only</option>";
	echo "<option value=20>All</option>";
}
else {
	echo "<option value=30 >Admin only</option>";
	echo "<option value=20 selected>All</option>";
}
InstallUtilities::paramSave('SecurityRefund',$CORE_LOCAL->get("SecurityRefund"));
?>
</select></td></tr>
<tr><td>
<b>Line Item Discount</b>: </td><td><select name=LI_DISCOUNT>
<?php
if(isset($_REQUEST['LI_DISCOUNT'])) $CORE_LOCAL->set('SecurityLineItemDiscount',$_REQUEST['LI_DISCOUNT']);
if ($CORE_LOCAL->get("SecurityLineItemDiscount")=="") $CORE_LOCAL->set("SecurityLineItemDiscount",20);
if ($CORE_LOCAL->get("SecurityLineItemDiscount") == 30){
	echo "<option value=30 selected>Admin only</option>";
	echo "<option value=20>All</option>";
}
else {
	echo "<option value=30 >Admin only</option>";
	echo "<option value=20 selected>All</option>";
}
InstallUtilities::paramSave('SecurityLineItemDiscount',$CORE_LOCAL->get("SecurityLineItemDiscount"));
?>
</select></td></tr>
<tr><td>
<b>Void Limit</b>:</td><td>
<?php
if (isset($_REQUEST['VOIDLIMIT'])) $CORE_LOCAL->set('VoidLimit',$_REQUEST['VOIDLIMIT']);
if ($CORE_LOCAL->get("VoidLimit")=="") $CORE_LOCAL->set("VoidLimit",0);
printf("<input type=text name=VOIDLIMIT value=\"%s\" />",$CORE_LOCAL->get('VoidLimit'));
InstallUtilities::paramSave('VoidLimit',$CORE_LOCAL->get('VoidLimit'));
?> (in dollars, per transaction. Zero for unlimited).
</td></tr><tr><td colspan=2>
<hr />
<input type=submit name=secsubmit value="Save Changes" />
</td></tr></table>
</form>
</div> <!--	wrapper -->
</body>
</html>
