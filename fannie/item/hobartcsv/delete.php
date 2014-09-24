<?php

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);
$page_title = "Fannie : Scale Delete";
$header = "Scale Delete";
include($FANNIE_ROOT."src/header.html");


if (isset($_GET['plu'])){
    include('parse.php');

    $plu = $_REQUEST['plu'];
    if (strlen($plu) <= 4)
        $plu = str_pad($plu,4,'0',STR_PAD_LEFT);
    else{
        $plu = str_pad($plu,13,'0',STR_PAD_LEFT);
        $plu = substr($plu,3,4);
    }

    $query = $dbc->prepare_statement("DELETE FROM scaleItems WHERE plu=?");
    $result = $dbc->exec_statement($query,array('002'.$plu.'000000'));

    deleteitem($plu);
    echo "Item delete requested<br />";
    echo "<a href=delete.php>Delete another</a>";

}
else {
    echo "<p>Delete item from scale but retain in POS</p>";
    echo "<form action=delete.php method=get>";
    echo "PLU/UPC: <input id=plu type=text name=plu />";
    echo "<input type=submit value=Delete />";
    echo "</form>";
    echo "<script type=\"text/javascript\">
        \$('#plu').focus();
        </script>";
}

include($FANNIE_ROOT."src/footer.html");

?>
