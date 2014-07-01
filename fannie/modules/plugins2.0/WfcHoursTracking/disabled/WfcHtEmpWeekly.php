<?php
class WfcHtEmpWeekly {}
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

$empID = -1;
if (isset($_GET["empID"])) $empID = $_GET["empID"];
if (isset($_POST["empID"])){
    $empID = $_POST["empID"];
    $notes = str_replace("'","''",$_POST["notes"]);
    $checkP = $sql->prepare_statement("SELECT empID FROM EmpWeeklyNotes WHERE empID=?");
    $checkR = $sql->exec_statement($checkP, array($empID));
    if ($sql->num_rows($checkR) == 0){
        $ins = $sql->prepare_statement("INSERT INTO EmpWeeklyNotes VALUES (?,?)");
        $sql->exec_statement($ins, array($empID, $notes));
    }
    else{
        $up = $sql->prepare_statement("UPDATE EmpWeeklyNotes SET notes=? WHERE empID=?");
        $sql->exec_statement($up, array($notes, $empID));
    }
}


$limitQ = "SELECT weekStart FROM weeklyHours GROUP BY weekStart ORDER BY weekStart DESC";
$limitR = $sql->query($limitQ);
$limitDay = "";
$limit2 = "";
$weekHeaders = array();
$i = 0;
while($limitW = $sql->fetch_row($limitR)){
    array_unshift($weekHeaders,$limitW[0]);
    if($i==0) $limit2 = $limitW[0];
    if($i==12) $limitDay = $limitW[0];
    $i++;
}
if (isset($_GET["start"])) $limitDay = $_GET["start"];
if (isset($_POST["start"])) $limitDay = $_POST["start"];
if (isset($_GET["end"])) $limit2 = $_GET["end"];
if (isset($_POST["end"])) $limit2 = $_POST["end"];

$query = "SELECT e.name,e.adpID,d.name,n.notes,
    w.hours,w.weekStart,w.weekEnd
    FROM employees as e LEFT JOIN Departments
    AS d ON e.department = d.deptID LEFT JOIN
    EmpWeeklyNotes AS n on e.empID = n.empID
    LEFT JOIN weeklyHours AS w ON e.empID=w.empID
    WHERE datediff(w.weekStart,?) >= 0
    AND datediff(w.weekStart,?) <= 0
    AND e.empID=?
    ORDER BY w.weekStart";
$prep = $sql->prepare_statement($query);
$result = $sql->exec_statement($prep, array($limitDay, $limit2, $empID));

$name = "";
$adpID = "";
$dept = "";
$notes = "";
$hours = array();
$labels = array();
while($row = $sql->fetch_row($result)){
    $name =  $row[0];
    $adpID = $row[1];
    $dept = $row[2];
    $notes = $row[3];
    array_push($hours,$row[4]);
    $d1 = explode("-",$row[5]);
    $d2 = explode("-",$row[6]);
    $dstr = $d1[1]."/".$d1[2]."/".$d1[0];
    $dstr .= " - ";
    $dstr .= $d2[1]."/".$d2[2]."/".$d2[0];
    array_push($labels,$dstr);
}

?>

<html>
<head>
    <title>Weekly Hours Report</title>
</head>
<style type=text/css>
#adpID {
    float: left;
    width: 350px;
    font-size: 120%;
    font-weight: bold;
    margin-bottom: 10px;
}
#name {
    float: left;
    font-size: 120%;
    font-weight: bold;
    margin-bottom: 10px;
}
#notes {
    float: left;
    width: 350px;
    border: solid 1px #ffffff;
}
#history {
    float: left;
}
#dept {
    float: left;
    width: 350px;
    font-size: 110%;
    margin-top: 10px;
}
#avg {
    float: left;
    margin-top: 10px;
    font-size: 110%;
}
.one {
    background: #ffffcc;
}
.two {
    background: #ffffff;
}
.hourslabel {
    padding: 3px;
    padding-right: 30px;
    padding-left: 5px;
}
.hoursdata {

    padding-right: 5px;
}
a {
    color: blue;
}

</style>
<body>
<form method=post action=empWeekly.php>
<?php
echo "<input type=hidden name=empID value=$empID />";

echo "<div id=adpID>$adpID</div>";
echo "<div id=name>$name</div>";
echo "<div style=\"clear:left;\"></div>";
echo "<div id=notes>
    <b>Notes</b><p />
    <textarea rows=15 cols=30 name=notes>$notes</textarea>
    <p />
    <input type=submit value=\"Save Notes\" />
    </div>";
echo "<div id=history>";
echo "Starting from: <select name=start
    onchange=\"top.location='empWeekly.php?empID=$empID&end=$limit2&start='+this.value;\"
    >";
foreach($weekHeaders as $w){
    $temp = explode("-",$w);
    $dstr = $temp[1]."/".$temp[2]."/".substr($temp[0],2);
    if ($w == $limitDay)
        echo "<option selected value=$w>$dstr</option>";
    else
        echo "<option value=$w>$dstr</option>";
}
echo "</select><p />";
echo "Ending on: <select name=end
    onchange=\"top.location='empWeekly.php?empID=$empID&start=$limitDay&end='+this.value;\"
    >";
foreach($weekHeaders as $w){
    $temp = explode("-",$w);
    $dstr = $temp[1]."/".$temp[2]."/".substr($temp[0],2);
    if ($w == $limit2)
        echo "<option selected value=$w>$dstr</option>";
    else
        echo "<option value=$w>$dstr</option>";
}
echo "</select><p />";
for($i = 0; $i < count($hours); $i++){
    echo "<div class=\"".(($i%2==0)?"one":"two")."\">";
    echo "<span class=hourslabel>".$labels[$i]."</span>";
    printf("<span class=hoursdata>%.2f</span>",$hours[$i]);
    echo "</div>";
}
echo "</div>";
echo "<div style=\"clear:left;\"></div>";
echo "<div id=dept>Primary Department: $dept</div>";
printf("<div id=avg>Average hours: %.2f</div>",array_sum($hours)/count($hours));
echo "<div style=\"clear:left;\"></div>";
echo "<a href=weeklyReport.php>Back to Full Listing</a>";

?>

</form>
</body>
</html>
<?php
*/
