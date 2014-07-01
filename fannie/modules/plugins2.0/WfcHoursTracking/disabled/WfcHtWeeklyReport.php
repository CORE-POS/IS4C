<?php
class WfcHtWeeklyReport {}
/**
  No longer in use; not cleaned up to be plugin-safe.
  Kept for reference.

include(dirname(__FILE__).'/../../../config.php');
require($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('view_all_hours')){
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$_SERVER['PHP_SELF']}");
    return;
}

require(dirname(__FILE__).'/db.php');
$sql = hours_dbconnect();

$weekHeaders = array();
$empData = array();

$limitQ = "SELECT weekStart FROM weeklyHours GROUP BY weekStart ORDER BY weekStart DESC LIMIT 13";
$limitR = $sql->query($limitQ);
$limitDay = "";
while($limitW = $sql->fetch_row($limitR)){
    $temp = explode("-",$limitW[0]);
    array_unshift($weekHeaders,$temp[1]."/".$temp[2]);
    $limitDay = $limitW[0];
}

$query = $sql->prepare_statement("SELECT e.empID,e.name,e.adpID,f.status,w.hours
    FROM employees AS e LEFT JOIN fullTimeStatus AS f
    ON e.empID = f.empID LEFT JOIN weeklyHours AS w
    ON e.empID = w.empID
    WHERE datediff(w.weekStart,?) >= 0
    AND deleted = 0
    ORDER BY e.name,w.weekStart");
$result = $sql->exec_statement($query, array($limitDay));
while($row = $sql->fetch_row($result)){
    if (!isset($empData["$row[0]"]))
        $empData["$row[0]"] = array();
    $empData["$row[0]"]["name"] = $row[1];
    $empData["$row[0]"]["adpID"] = $row[2];
    $empData["$row[0]"]["status"] = $row[3];
    if (!isset($empData["$row[0]"]["hours"]))
        $empData["$row[0]"]["hours"] = array();
    array_push($empData["$row[0]"]["hours"],$row[4]);
}

?>
<html>
<head>
    <title>Weekly Hours Report</title>
<style type="text/css">
tr.post td {
    color: #ffffff;
    background-color: #cc0000;
}
tr.post a {
    color: #cccccc;
}
tr.pre td {
    color: #ffffff;
    background-color: #00cc00;
}
td.smaller {
    font-size: 85%;
}
th.smaller {
    font-size: 85%;
}

a {
    color: blue;
}

</style>

<?php

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr>";
echo "<th colspan=2>&nbsp;</th><th>Status</th><th>Avg.</th>";
foreach($weekHeaders as $w)
    echo "<th class=smaller>$w</th>";
echo "</tr>";

foreach($empData as $k=>$v){
    $empID = $k;
    $name = $v["name"];
    $adpID = $v["adpID"];
    $status = $v["status"];
    $hours = $v["hours"];
    $avg = array_sum($hours)/count($hours);

    if (substr($status,0,4) == "FULL" && $avg < 30.0)
        echo "<tr class=post>";
    elseif(substr($status,0,2) == "FT" && $avg < 30.0)
        echo "<tr class=post>";
    elseif(substr($status,0,2) == "PT" && $avg >= 30.0)
        echo "<tr class=pre>";
    else
        echo "<tr>";
    echo "<td>$adpID</td>";
    printf("<td><a href=empWeekly.php?empID=%s>%s</a></td>",$empID,$name);
    echo "<td>$status</td>";
    printf("<td>%.2f</td>",$avg);
    for($i=0; $i<count($weekHeaders)-count($hours);$i++)
        echo "<td class=smaller>&nbsp;</td>";
    foreach($hours as $h)
        printf("<td class=smaller>%.2f</td>",$h);
    echo "</tr>";
}
echo "</table>";

*/
?>
