<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('SQLManager')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}

$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
$chkP = $dbc->prepare('SELECT upc FROM productUser WHERE photo=?');

$dh = opendir('done');
while( ($file = readdir($dh)) !== False) {
    if ($file[0] == '.') continue;
    $chk = $dbc->getValue($chkP, array($file));
    if ($chk === false) {
        echo "Orphan {$file}\n";
        unlink('done/' . $file);
    }
}
