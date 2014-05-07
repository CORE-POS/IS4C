<?php
if (!class_exists('AutoLoader')) include(dirname(__FILE__).'/../lib/AutoLoader.php');
AutoLoader::loadMap();

echo 'Testing PHP error logging<br />';
echo 'Divide by zero should be logged in ' . realpath(dirname(__FILE__) . '/../log/') . '/php-errors.log';
$log_php_error = 1 / 0;

echo '<hr />';

echo 'Testing SQL error logging<br />';
echo 'Invalid query should be logged in ' . realpath(dirname(__FILE__) . '/../log/') . '/queries.log';
$db = Database::pDataConnect();
$log_db_error = $db->query('SELECT non_existing_column FROM non_existing_table');
