<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::LoadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
include('util.php');
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
if(isset($_REQUEST['PRIV_CANCEL'])) $CORE_LOCAL->set('SecurityCancel',$_REQUEST['PRIV_CANCEL'],True);
if ($CORE_LOCAL->get("SecurityCancel")=="") $CORE_LOCAL->set("SecurityCancel",20,True);
if ($CORE_LOCAL->get("SecurityCancel") == 30){
	echo "<option value=30 selected>Admin only</option>";
	echo "<option value=20>All</option>";
}
else {
	echo "<option value=30 >Admin only</option>";
	echo "<option value=20 selected>All</option>";
}
confsave('SecurityCancel',$CORE_LOCAL->get("SecurityCancel"));
?>
</select></td></tr><tr><td>
<b>Suspend/Resume</b>: </td><td><select name=PRIV_SR>
<?php
if(isset($_REQUEST['PRIV_SR'])) $CORE_LOCAL->set('SecuritySR',$_REQUEST['PRIV_SR'],True);
if ($CORE_LOCAL->get("SecuritySR")=="") $CORE_LOCAL->set("SecuritySR",20,True);
if ($CORE_LOCAL->get("SecuritySR") == 30){
	echo "<option value=30 selected>Admin only</option>";
	echo "<option value=20>All</option>";
}
else {
	echo "<option value=30 >Admin only</option>";
	echo "<option value=20 selected>All</option>";
}
confsave('SecuritySR',$CORE_LOCAL->get("SecuritySR"));
?>
</select></td></tr><tr><td>
<b>Refund Item</b>: </td><td><select name=PRIV_REFUND>
<?php
if(isset($_REQUEST['PRIV_REFUND'])) $CORE_LOCAL->set('SecurityRefund',$_REQUEST['PRIV_REFUND'],True);
if ($CORE_LOCAL->get("SecurityRefund")=="") $CORE_LOCAL->set("SecurityRefund",20,True);
if ($CORE_LOCAL->get("SecurityRefund") == 30){
	echo "<option value=30 selected>Admin only</option>";
	echo "<option value=20>All</option>";
}
else {
	echo "<option value=30 >Admin only</option>";
	echo "<option value=20 selected>All</option>";
}
confsave('SecurityRefund',$CORE_LOCAL->get("SecurityRefund"));
?>
</select></td></tr><tr><td>
<b>Void Limit</b>:</td><td>
<?php
if (isset($_REQUEST['VOIDLIMIT'])) $CORE_LOCAL->set('VoidLimit',$_REQUEST['VOIDLIMIT'],True);
if ($CORE_LOCAL->get("VoidLimit")=="") $CORE_LOCAL->set("VoidLimit",0,True);
printf("<input type=text name=VOIDLIMIT value=\"%s\" />",$CORE_LOCAL->get('VoidLimit'));
confsave('VoidLimit',"'".$CORE_LOCAL->get('VoidLimit')."'");
?> (in dollars, per transaction. Zero for unlimited).
</td></tr><tr><td colspan=2>
<hr />
<input type=submit name=secsubmit value="Save Changes" />
</td></tr></table>
</form>
</div> <!--	wrapper -->
</body>
</html>
