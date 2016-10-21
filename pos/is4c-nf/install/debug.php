<?php
use COREPOS\pos\install\conf\Conf;
use COREPOS\pos\install\conf\FormFactory;
use COREPOS\pos\install\InstallUtilities;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;

include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
CoreState::loadParams();
$form = new FormFactory(InstallUtilities::dbOrFail(CoreLocal::get('pDatabase')));
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
<h2><?php echo _('IT CORE Lane Installation: Debug Settings'); ?></h2>
<b><?php echo _('Logs'); ?></b><br />
<?php echo _('Default logs:'); ?>
<ul>
    <li><i>debug_lane.log</i> <?php echo _('contains failed queries, PHP errors, warnings, notices, etc depending on error reporting settings for PHP installation.'); ?></li>
    <li><i>lane.log</i> <?php echo _('contains informational logging'); ?></li>
</ul>
<div class="alert"><?php Conf::checkWritable('../log/debug_lane.log'); ?></div>
<div class="alert"><?php Conf::checkWritable('../log/lane.log'); ?></div>
<?php echo _('Optional logs:'); ?>
<ul>
    <li><i>core_local.log</i> <?php echo _('lists changes to session/state values. Fills FAST.'); ?></li>
</ul>
<div class="alert"><?php Conf::checkWritable('../log/core_local.log','True'); ?></div>
<hr />
<form action=debug.php method=post>
<b><?php echo _('Disable DB Compatibility checks'); ?></b>:
<?php
echo $form->selectField('NoCompat', array(1=>_('Yes'),0=>_('No')), 0);
?>
<br />
<?php echo _('
By default CORE will often query the status of tables to verify whether newer columns
exist before attempting to use them. Disabling these checks may yield modest performance
gains but if database schemas are not up to date any resulting crashes will not be
graceful.'); ?>
<hr />
<b><?php echo _('Log State Changes'); ?></b>: 
<?php
echo $form->selectField('Debug_CoreLocal', array(1=>_('Yes'),0=>_('No')), 0);
?>
<br />
<?php echo _('See optional logs above.'); ?>
<hr />
<b><?php echo _('Show Page Changes'); ?></b>: 
<?php
echo $form->selectField('Debug_Redirects', array(1=>_('Yes'),0=>_('No')), 0);
?>
<br />
<?php echo _('
This option changes HTTP redirects into manual, clickable links. A stack
trace is also included. There are some javascript-based URL changes that
this won\'t catch, but your browser surely has a fancy javascript console
available for those. If not, find a better browser.'); ?>
<hr />
<b><?php echo _('Show AJAX errors'); ?></b>
<?php
echo $form->selectField('Debug_JS', array(1=>_('Yes'),0=>_('No')), 0);
?>
<br />
<?php echo _('This option will write information along the bottom of the screen when
an AJAX request dies with a fatal error.'); ?>
<hr />
<b><?php echo _('Character Set'); ?></b>
<?php
echo $form->textField('CoreCharSet', 'utf-8');
?>
<p>
<?php echo _('Change the character set used to display pages. Common values are "utf-8" and "iso-8859-1".
This value is embedded in the content of pages but may be overriden by your web server.'); ?>
</p/>
<b><?php echo _('Additional Character Set Information'); ?></b>
<?php 
$this_page = $_SERVER['REQUEST_URI'];
$test_page = str_replace('install/debug.php', 'test/phpinfo.php', $this_page);
$headers = get_headers('http://' . $_SERVER['HTTP_HOST'] . $test_page);
echo '<p><em>' . _('Headers sent by the web server for ') . $test_page . '</em>';
echo '<pre style="background-color:#ccc;">';
foreach ($headers as $header) {
    echo $header . "\n";
}
echo '</pre>';
?>
<?php echo _('If these headers include a <em>charset</em> other than your desired charset your
webserver configuration needs to be adjusted.'); ?>
</p>
<p><em><?php echo _('Character Set used by Database Connections'); ?></em><br />
<?php echo _('Local connection'); ?>:
<?php echo getCharset(Database::pDataConnect()); ?><br />
<?php echo _('Server connection'); ?>:
<?php echo getCharset(Database::mDataConnect()); ?><br />
<br />
<?php echo _('
To correctly display characters the database character set settings
should match the one used for serving webpages above. In MySQL you can
adjust this in the [mysql] section of the configuration file (my.cnf on
Linux, my.ini on Windows).'); ?>
<?php echo _('Note: "latin1" and "ISO-8859-1" are the same thing.'); ?>
</p>
<hr />
<input type=submit value="<?php echo _('Save Changes'); ?>" />
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
