<?php

/***
 * 
 * Test for invalid utf8 byte sequences in the database
 * Requires mbstring extension
 *
 * Usage: php chkmysql.php [table] [column]
 *
 * Connects, verifies the connection is correctly set to
 * utf8, and scans the specified column for invalid 
 * values.
 */

if (count($argv) !== 3) {
    echo "Usage: php chkmysql.php [table] [column]\n";
    exit;
}

$config = __DIR__ . '/../../fannie/config.php';
if (!file_exists($config)) {
    echo "Fannie config.php not found!\n";
    exit;
}

if (!function_exists('mb_detect_encoding')) {
    echo "mbstring extension must be installed & enabled\n";
    exit;
}

include($config);
include(__DIR__ . '/../../fannie/classlib2.0/FannieAPI.php');

$dbc = FannieDB::get($FANNIE_OP_DB);

echo "Setting charset to utf-8\n";
$res = $dbc->setCharSet('utf-8');
echo ($res) ? "[OK]\n" : "[FAIL]\n";
if (!$res) exit;

echo "Reading back settings\n";
$res = $dbc->query("SHOW VARIABLES LIKE 'character_set_%'");
while ($row = $dbc->fetchRow($res)) {
    if (in_array($row[0], array('character_set_client', 'character_set_connection', 'character_set_results'))) {
        echo strstr($row[1], 'utf') ? "[OK]\t" : "[FAIL]\t";
        echo $row[0] . "\t" . $row[1] . "\n";
        if (!strstr($row[1], 'utf')) exit;
    }
}

$res = $dbc->query("SELECT * FROM " . $dbc->identifierEscape($argv[1]));
$col = trim($argv[2]);
while ($row = $dbc->fetchRow($res)) {
    if (!array_key_exists($col, $row)) {
        echo "Table {$argv[1]} does not have column {$argv[2]}\n";
        exit;
    }
    $val = $row[$col];
    if (strlen($val) == 0) continue;

    $valid = mb_detect_encoding($val, 'UTF-8', true);
    if ($valid === false) {
        echo "INVALID SEQUENCE FOUND\n";
        print_r($row);
        echo "\n\n";
    }
}

