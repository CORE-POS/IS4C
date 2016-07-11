<?php

include('../../../config.php');
require('dbconnect.php');
$mysql = dbconnect();

$itemID = 1;
$clearQ = $mysql->prepare("delete from ingredients where id >= ?");
$clearR = $mysql->execute($clearQ,array($itemID),$mydb);


if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');
$mssql = $sql;

$q = "select item,size,units,price from deliInventory where item <> ''  and item not like '----%' order by item";
$r = $mssql->query($q,$db);

echo "<table cellspacing=3 cellpadding=0 border=1>";
echo "<tr><th>Name</th><th>Pkg size</th><th>Pkg/case</th><th>Cost</th><th>Pkg amt</th><th>Pkg units</th><th>Case contents</th></tr>";
$prev_name = "";
while ($w = $mssql->fetchRow($r)){
    if ($w[0] == $prev_name || $w[3] == 0)
        continue;
    echo "<tr>";
    echo "<td>$w[0]</td>";
    echo "<td>$w[1]</td>";
    echo "<td>$w[2]</td>";
    echo "<td>$w[3]</td>";
    $unit = '&nbsp;';
    $amt = 0;
    if (is_numeric($w[1])){
        $unit = "each";
        $amt = $w[1];
    }
    else if ($u = unit_mapper($w[1])){
        $unit = $u;
        $amt = 1;
    }
    else if ($w[1] == "LT"){
        $unit = "fl oz";
        $amt = 33.81;
    }
    else {
        preg_match("/(\d*?\.\d+|\d+)(.*)/",$w[1],$matches);
        $unit = unit_mapper($matches[2]);
        $amt = $matches[1];
    }
    echo "<td>$amt</td>";
    echo "<td>$unit</td>";
    $case = trim($w[2]);
    if ($case == "#")
        $case = 1;
    $case = rtrim($case,"#");
    if ($case == "EA")
        $case = 1;
    if (is_numeric($case)){
        $total = $case*$amt;
        echo "<td>$total $unit</td>";
    }
    else {
        echo "<td>&nbsp;</td>";
    }
    echo "</tr>";
    
    $name = trim($w[0]);
    if ($unit == "lb" || $unit == "oz" || $unit == "each"){
        $insQ = $mysql->prepare("insert into ingredients values (?,?,?,?,0,'tsp',?)");
        echo $insQ."<br />";
        $insR = $mysql->execute($insQ,array($itemID, $name, $total, $unit, $w[3]),$mydb);
    }
    else {
        $insQ = $mysql->prepare("insert into ingredients values (?,?,?,?,0,'oz',?)");
        echo $insQ."<br />";
        $insR = $mysql->execute($insQ,array($itemID, $name, $total, $unit, $w[3]),$mydb);
    }
    $itemID++;

    $prev_name = $w[0];
}

function unit_mapper($input){
    $input = strtoupper(trim($input));
    if ($input == "EA"){
        return "each";
    }
    else if ($input == "GAL"){
        return "gallon";
    }
    else if ($input == "QT"){
        return "quart";
    }
    else if ($input == "#"){
        return "lb";
    }
    else if ($input == "PT"){
        return "pint";
    }
    else if ($input == "OZ"){
        return "oz";
    }
    else if ($input == "FL OZ"){
        return "fl oz";
    }
    return false;
}

