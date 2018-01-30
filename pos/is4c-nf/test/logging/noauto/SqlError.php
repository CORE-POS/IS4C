<?php

use COREPOS\pos\lib\Database;

include(__DIR__ . '/../../../lib/AutoLoader.php');

$dbc = Database::pDataConnect();
$dbc->query("SELECT 1 FROMM products LIMIT 1");

