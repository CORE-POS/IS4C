<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

$memTypesQ = "select memtype,memDesc from memtype order by memtype";
$memTypesR = $sql->query($memTypesQ);

$selected = 0;
if (isset($_GET['type']))
    $selected = $_GET['type'];

if (!isset($_GET['excel'])){
    echo "<form method=get action=limits.php>";
    echo "<select name=type>";
    while ($memTypesW = $sql->fetchRow($memTypesR)){
        if ($memTypesW[0] == $selected)
            echo "<option value=$memTypesW[0] selected>$memTypesW[0] $memTypesW[1]</option>";
        else
            echo "<option value=$memTypesW[0]>$memTypesW[0] $memTypesW[1]</option>";
    }
    echo "</select>";
    echo " Excel <input type=checkbox name=excel />";
    echo " <input type=submit value=Submit />";
    echo "</form>";
}
else {
    header("Content-Disposition: inline; filename=memLimits.xls");
    header("Content-Description: PHP3 Generated Data");
    header("Content-type: application/vnd.ms-excel; name='excel'");
}

if (isset($_GET['type'])){
    $type = $_GET['type'];
    
    $memQ = $sql->prepare("select c.cardno,c.ChargeLimit from custdata as c
         left outer join suspensions as s on c.cardno = s.cardno
             where c.memType=? and c.personnum=1 and 
         c.lastname not like 'NEW %' and s.cardno is NULL 
         order by c.cardno");
    $memR = $sql->execute($memQ, array($type));

    echo "<table cellspacing=3 cellpadding=0 border=1>";
    echo "<tr><th>Member #</th><th>Charge limit</th></tr>";
    while ($memW = $sql->fetchRow($memR)){
        echo "<tr>";
        if (!isset($_GET['excel']))
            echo "<td><a href=memGen.php?memID=$memW[0]>$memW[0]</a></td>";
        else
            echo "<td>$memW[0]</td>";
        echo "<td>$memW[1]</td>";
        echo "</tr>";
    }
    echo "</table>";
}

