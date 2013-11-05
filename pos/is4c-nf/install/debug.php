<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include(realpath(dirname(__FILE__).'/../ini.php'));
CoreState::loadParams();
include('InstallUtilities.php');
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
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Debug Settings</h2>
<b>Logs</b><br />
Default logs:
<ul>
	<li><i>php-errors.log</i> contains PHP errors, warnings, notices, etc depending on error reporting settings for PHP installation.</li>
	<li><i>queries.log</i> lists failed queries</li>
</ul>
<div class="alert"><?php InstallUtilities::checkWritable('../log/php-errors.log'); ?></div>
<div class="alert"><?php InstallUtilities::checkWritable('../log/queries.log'); ?></div>
Optional logs:
<ul>
	<li><i>core_local.log</i> lists changes to session/state values. Fills FAST.</li>
</ul>
<div class="alert"><?php InstallUtilities::checkWritable('../log/core_local.log','True'); ?></div>
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
InstallUtilities::paramSave('Debug_CoreLocal',$CORE_LOCAL->get("Debug_CoreLocal"));
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
InstallUtilities::paramSave('Debug_Redirects',$CORE_LOCAL->get("Debug_Redirects"));
?>
</select><br />
This option changes HTTP redirects into manual, clickable links. A stack
trace is also included. There are some javascript-based URL changes that
this won't catch, but your browser surely has a fancy javascript console
available for those. If not, find a better browser.
<hr />
<input type=submit value="Save Changes" />
</form>
</div> <!--	wrapper -->
</body>
</html>
