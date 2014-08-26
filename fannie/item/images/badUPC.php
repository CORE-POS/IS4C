<?php
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$p1 = $dbc->prepare_statement("SELECT upc FROM productUser where upc=?");
$p2 = $dbc->prepare_statement("SELECT upc FROM products WHERE upc=?");
$dh = opendir('new');
while( ($file = readdir($dh)) !== False){

    $exts = explode('.',$file);
    $e = strtolower(array_pop($exts));
    if ($e != "png" && $e != "gif" && $e != "jpg" && $e != "jpeg")
        continue;

    $u = array_pop($exts);
    if (!is_numeric($u)) continue;

    $upc = str_pad($u,13,'0',STR_PAD_LEFT);

    $r1 = $dbc->exec_statement($p1,array($upc));
    if ($dbc->num_rows($r1) > 0)
        continue;

    $r2 = $dbc->exec_statement($p2,array($upc));
    if ($dbc->num_rows($r2) > 0)
        continue;

    echo "<b>UPC:</b> $u<br />";
    echo "<a href=new/$file><img src=new/$u.thumb.$e /></a>";
    echo "<hr />";
}
?>
