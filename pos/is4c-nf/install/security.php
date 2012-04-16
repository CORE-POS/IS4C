<?php
include('../ini.php');
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
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_config.php">Additional Configuration</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="scanning.php">Scanning Options</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Security
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_data.php">Sample Data</a>
<form action=security.php method=post>
<b>Cancel Transaction</b>: <select name=PRIV_CANCEL>
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
confsave('SecurityCancel',$CORE_LOCAL->get("SecurityCancel"));
?>
</select><br />
<b>Suspend/Resume</b>: <select name=PRIV_SR>
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
var_dump($CORE_LOCAL->get("SecuritySR"));
confsave('SecuritySR',$CORE_LOCAL->get("SecuritySR"));
?>
</select><br />
<b>Refund Item</b>: <select name=PRIV_REFUND>
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
confsave('SecurityRefund',$CORE_LOCAL->get("SecurityRefund"));
?>
</select><br />
<b>Void Limit</b>:
<?php
if (isset($_REQUEST['VOIDLIMIT'])) $CORE_LOCAL->set('VoidLimit',$_REQUEST['VOIDLIMIT']);
if ($CORE_LOCAL->get("VoidLimit")=="") $CORE_LOCAL->set("VoidLimit",0);
printf("<input type=text name=VOIDLIMIT value=\"%s\" />",$CORE_LOCAL->get('VoidLimit'));
confsave('VoidLimit',"'".$CORE_LOCAL->get('VoidLimit')."'");
?> (in dollars, per transaction. Zero for unlimited).
<br />
<hr />
<input type=submit value="Save Changes" />
</form>
</body>
</html>
