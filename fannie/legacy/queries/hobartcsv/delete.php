<?php

include('ini.php');

if (isset($_GET['scale'])){
    include('parse.php');

    if (!class_exists("SQLManager")) require_once("../../sql/SQLManager.php");
    include('../../db.php');

    $fetchQ = "select plu from scaleItems where plu='0027985000000'";
    $fetchR = $sql->query($fetchQ);
    $plus = array();
    $i = 0;
    while ($fetchW = $sql->fetchRow($fetchR)){
        preg_match("/002(\d\d\d\d)0/",$fetchW[0],$matches);
        $plu = $matches[1];
        if (!empty($plu)){
            $plus[$i] = $plu;
            $i++;
        }
    }
    deleteitem($plus,$_GET['scale']);
    echo "Item delete requested";
}
else {
    echo "<form action=delete.php method=get>";
    echo "<select name=scale>";
    $i = 0;
    foreach($scale_ips as $s){
        echo "<option value=$i>$s</option>";
        $i++;
    }
    echo "</select>";
    echo "<input type=submit value=Delete />";
    echo "</form>";
}

