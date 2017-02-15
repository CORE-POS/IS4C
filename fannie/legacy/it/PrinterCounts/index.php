<?php

if (isset($_GET['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="printerCounts.xls"');
}

include('../../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/Credentials/printcount.wfcdb.php.php');

$query = "SELECT name,type,subtype,num,modified FROM printerCounts
    ORDER BY name,type,subtype";
$result = $sql->query($query);
$data = array();
$modified = "";
while($row = $sql->fetch_row($result)){
    $modified = $row[4];
    if (!isset($data[$row[0]])) $data[$row[0]] = array();
    if (!isset($data[$row[0]][$row[1]])) $data[$row[0]][$row[1]] = array();
    $data[$row[0]][$row[1]][$row[2]] = $row[3];
}

echo "<span style=\"color:red;\">Updated: ".array_shift(explode(" ",$modified))."</span>";
echo "<table cellspacing=0 cellpadding=4 border=1>";
foreach($data as $k=>$v){
    echo "<tr>";
    $rows = 0;
    foreach($v as $cat=>$sub) $rows += count($sub);
    echo "<th rowspan=$rows>$k</th>";
    $count = 0;
    foreach($v as $cat=>$sub){
        if ($count++ > 0) echo "</tr><tr>";
        echo "<td rowspan=".count($sub)."><em>$cat</em></td>";
        $bcount = 0;
        foreach($sub as $label=>$num){
            if ($bcount++ > 0) echo "</tr><tr>";
            echo "<td>$label</td>";
            echo "<td>$num</td>";
            echo "</tr>";
        }
    }
    echo "<tr><td colspan=4 style=\"height:8px;font-size:0;background:#ccc;\">&nbsp;</td></tr>";
}
echo "</table>";

