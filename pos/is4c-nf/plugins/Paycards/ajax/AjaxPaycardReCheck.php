<?php
/**
 * Attempt to recover from a javascript error when 
 * communicating with driver.
 *
 * Checks MagellanLog table for response value, waits
 * two seconds, and checks the table on more time
 * before giving up and returning to the main screen.
 *
 */

use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\Database;

if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}

$mdc = new MercuryDC();
$ref = $mdc->refnum(CoreLocal::get('paycard_id'));
$current = isset($_REQUEST['current']) ? $_REQUEST['current'] : '../gui/PaycardEmvPage.php';

$dbc = Database::tDataConnect();
$prep = $dbc->prepare('SELECT entry FROM MagellanLog WHERE tdate > CURDATE() AND entryKey=?');
$entry = $prep->getValue(array($ref));

if (strpos($entry, '<?xml') !== false) {
    header('Location: ' . $current . '?retry=' . $ref);
    return;
}

sleep(2);
$entry = $prep->getValue(array($ref));

if (strpos($entry, '<?xml') !== false) {
    header('Location: ' . $current . '?retry=' . $ref);
    return;
}

header('Location: ' . MiscLib::baseURL() . 'gui-modules/pos2.php');

