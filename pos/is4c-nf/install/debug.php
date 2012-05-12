<?php
include('../ini.php');
include('util.php');
?>
<html>
<head>
<title>Debug Settings</title>
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
<a href="security.php">Security</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Debug
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="extra_data.php">Sample Data</a>
<hr />
<b>Logs</b><br />
<?php 
if (!is_writable('../log')) {
	echo '<span style="color:red;">Log directory ('.realpath('../log').') is not writable</span>';	
}
else {
	echo '<span style="color:green;">Log directory ('.realpath('../log').') is writable</span>';	
}
?>
<br />
Default logs:
<ul>
	<li><i>php-errors.log</i> contains PHP errors, warnings, notices, etc depending on error reporting settings for PHP installation.</li>
	<li><i>queries.log</i> lists failed queries</li>
</ul>
Optional logs:
<ul>
	<li><i>core_local.log</i> lists changes to session/state values. Fills FAST.</li>
</ul>
<hr />
<form action=debug.php method=post>
<b>Log State Changes</b>: <select name=DEBUG_STATE>
<?php
if(isset($_REQUEST['DEBUG_STATE'])) $CORE_LOCAL->set('Debug_CoreLocal',$_REQUEST['DEBUG_STATE']);
if ($CORE_LOCAL->get("Debug_CoreLocal") == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0>No</option>";
}
else {
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('Debug_CoreLocal',"'".$CORE_LOCAL->get("Debug_CoreLocal")."'");
?>
</select><br />
See optional logs above.
<hr />
<b>Show Page Changes</b>: <select name=DEBUG_REDIRECT>
<?php
if(isset($_REQUEST['DEBUG_REDIRECT'])) $CORE_LOCAL->set('Debug_Redirects',$_REQUEST['DEBUG_REDIRECT']);
if ($CORE_LOCAL->get("Debug_Redirects") == 1){
	echo "<option value=1 selected>Yes</option>";
	echo "<option value=0>No</option>";
}
else {
	echo "<option value=1 >Yes</option>";
	echo "<option value=0 selected>No</option>";
}
confsave('Debug_Redirects',"'".$CORE_LOCAL->get("Debug_Redirects")."'");
?>
</select><br />
This option changes HTTP redirects into manual, clickable links. A stack
trace is also included. There are some javascript-based URL changes that
this won't catch, but your browser surely has a fancy javascript console
available for those. If not, find a better browser.
<hr />
<input type=submit value="Save Changes" />
</form>
</body>
</html>
