<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
include('../ini.php');
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
    <li><i>debug_lane.log</i> contains failed queries, PHP errors, warnings, notices, etc depending on error reporting settings for PHP installation.</li>
    <li><i>lane.log</i> contains informational logging</li>
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
<b>Disable DB Compatibility checks</b>:
<?php
echo InstallUtilities::installSelectField('NoCompat', array(1=>'Yes',0=>'No'), 0);
?>
<br />
By default CORE will often query the status of tables to verify whether newer columns
exist before attempting to use them. Disabling these checks may yield modest performance
gains but if database schemas are not up to date any resulting crashes will not be
graceful.
<hr />
<b>Log State Changes</b>: 
<?php
echo InstallUtilities::installSelectField('Debug_CoreLocal', array(1=>'Yes',0=>'No'), 0);
?>
<br />
See optional logs above.
<hr />
<b>Show Page Changes</b>: 
<?php
echo InstallUtilities::installSelectField('Debug_Redirects', array(1=>'Yes',0=>'No'), 0);
?>
<br />
This option changes HTTP redirects into manual, clickable links. A stack
trace is also included. There are some javascript-based URL changes that
this won't catch, but your browser surely has a fancy javascript console
available for those. If not, find a better browser.
<hr />
<b>Character Set</b>
<?php
echo InstallUtilities::installTextField('CoreCharSet', 'utf-8');
?>
<p>
Change the character set used to display pages. Common values are "utf-8" and "iso-8859-1".
This value is embedded in the content of pages but may be overriden by your web server.
</p/>
<b>Additional Character Set Information</b>
<?php 
$this_page = $_SERVER['REQUEST_URI'];
$test_page = str_replace('install/debug.php', 'test/phpinfo.php', $this_page);
$headers = get_headers('http://' . $_SERVER['HTTP_HOST'] . $test_page);
echo '<p><em>Headers sent by the web server for ' . $test_page . '</em>';
echo '<pre style="background-color:#ccc;">';
foreach ($headers as $header) {
    echo $header . "\n";
}
echo '</pre>';
?>
If these headers include a <em>charset</em> other than your desired charset your
webserver configuration needs to be adjusted.
</p>
<p><em>Character Set used by Database Connections</em><br />
Local connection:
<?php echo getCharset(Database::pDataConnect()); ?><br />
Server connection:
<?php echo getCharset(Database::mDataConnect()); ?><br />
<br />
To correctly display characters the database character set settings
should match the one used for serving webpages above. In MySQL you can
adjust this in the [mysql] section of the configuration file (my.cnf on
Linux, my.ini on Windows).
Note: "latin1" and "ISO-8859-1" are the same thing.
</p>
<hr />
<input type=submit value="Save Changes" />
</form>
</div> <!--    wrapper -->
</body>
</html>
<?php

function getCharset($dbc)
{
    $res = $dbc->query("SHOW VARIABLES LIKE '%char%'");
    $ret = '';
    while ($row = $dbc->fetchRow($res)) { 
        if ($row[0] === 'character_set_client' || $row[0] === 'character_set_connection' || $row[0] === 'character_set_results') {
            $ret .= $row[0] . ': ' . $row[1] . ', ';
        }
    }

    return $ret;
}
