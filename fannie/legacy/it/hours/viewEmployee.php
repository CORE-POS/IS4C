<?php
include('../../../config.php');

$empID = $_GET["id"];

require('db.php');
$sql = hours_dbconnect();

require($FANNIE_ROOT.'auth/login.php');
$name = checkLogin();
if (!$name){
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/viewEmployee.php?id=".$_GET["id"]);
    return;
}
if (!isset($empID) || empty($empID)) $empID = getUID($name);

if (!validateUserQuiet('view_all_hours')){
    /* see if logged in user has access to any
       department. if so, see if the selected employee
       is in that department
    */
    $sql = hours_dbconnect();
    $validated = false;
    $depts = array(10,11,12,13,20,21,30,40,41,50,60,998);
    $checkQ = $sql->prepare("select department from employees where empID=?");
    $checkR = $sql->execute($checkQ, array($empID));
    $checkW = $sql->fetch_row($checkR);
    if (validateUserQuiet('view_all_hours',$checkW['department'])){
        $validated = true;
    }

    /* no access permissions found, so only allow the
       logged in user to see themself
    */
    if (!$validated)
        $empID = getUID($name);
}

$sql = hours_dbconnect();
$deptQ = $sql->prepare("select department from employees where empID=?");
$deptR = $sql->execute($deptQ, array($empID));
$deptW = $sql->fetch_row($deptR);
if ($deptW['department'] >= 998){
    header("Location: {$FANNIE_URL}legacy/it/hours/viewEmployeeSalary.php?id=$empID");
    return;
}

echo "<html><head><title>View</title>";
echo "<style type=text/css>
#payperiods {
    margin-top: 50px;
}

#payperiods td {
    text-align: right;
}

#payperiods th {
    text-align: center;
}

#payperiods td.left {
    text-align: left;
}

#payperiods th.left {
    text-align: left;
}

#payperiods th.right {
    text-align: right;
}

tr.one td {
    background: #ffffcc;
}
tr.one th {
    background: #ffffcc;
    text-align: right;
}

tr.two td {
    background: #ffffff;
}
tr.two th {
    background: #ffffff;
    text-align: right;
}
a {
    color: blue;
}

#temptable th {
    text-align: left;
}
#temptable td {
    text-align: right;
    padding-left: 2em;
}

#temptable {
    font-size: 125%;
}

#newtable th{
    text-align: left;
}
#newtable td{
    text-align: right;
}

</style>";
echo "</head><body>";

echo "<h3>Employee Total Hours Worked and PTO Status</h3>";

$infoQ = $sql->prepare("select e.name,e.PTOLevel,p.dateStr,
    l.HoursWorked - h.totalHours as remaining,
    o.PTOremaining,o.totalPTO,h.totalHours,u.hours
    from employees as e left join PayPeriods as p on e.PTOCutoff = p.periodID
    left join hoursalltime as h on e.empID=h.empID
    left join PTOLevels as l on e.PTOLevel+1 = l.levelID
    left join pto as o on e.empID=o.empID
    left join uto as u on e.empID=u.empID
    where e.empID=?");
$infoR = $sql->execute($infoQ, array($empID));
$infoW = $sql->fetch_row($infoR);

$startdate = $infoW[2];
if (preg_match("/(\d+?.\d+?.\d+)$/",$startdate,$matches) > 0) 
    $startdate = $matches[1];
    if (preg_match("/(\d\d?).(\d\d?).(\d\d\d?\d?)/",$startdate,$matches) > 0){
        $month = $matches[1];
        $day = $matches[2];
        $year = $matches[3];

        $timestamp = mktime(0,0,0,$month,$day,$year);
        $startdate = strftime("%D",$timestamp+(24*60*60));
    }

echo "<h2>$infoW[0] [ <a href={$FANNIE_URL}auth/ui/loginform.php?logout=yes>Logout</a> ]</h2>";
echo "<table cellspacing=0 cellpadding=4 border=1 id=newtable>";
echo "<tr class=one><th>Consecutive Hours Worked</th><td>$infoW[6]</td></tr>";
echo "<tr class=two><th>Current PTO Level</th><td>$infoW[1]</td></tr>";
echo "<tr class=one><th>Starting PTO Allocation</th><td>$infoW[5]</td></tr>";
echo "<tr class=two><th>PTO Hours Remaining</th><td>$infoW[4]</td></tr>";
echo "<tr class=one><th>Effective Since</th><td>$startdate</td></tr>";
printf("<tr class=two><th>Hours to Next Level</th><td>%.2f</td></tr>",$infoW[3]);
echo "<tr class=one><th>UTO Hours Remaining</th><td>$infoW[7]</td></tr>";
echo "</tr></table>";

$periodsQ = $sql->prepare("select min(p.dateStr),sum(i.hours),sum(i.OTHours),sum(i.PTOHours),
    sum(i.UTOHours),sum(i.SecondRateHours),i.periodID
    from ImportedHoursData as i left join PayPeriods as p on i.periodID=p.periodID
    where i.empID=? group by i.periodID order by i.periodID desc");
$periodsR = $sql->execute($periodsQ, array($empID));
$sums = array(0,0,0,0,0,0);
$class = array("one","two");
$prev_hours = 0;
$c = 0;
echo "<table id=payperiods cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Pay Period</th><th>Reg. Hours</th><th>OT Hours</th><th>PTO Taken</th>";
echo "<th>UTO Taken</th><th>Alt. Position Hours</th><th>Total Hours*</th></tr>";
while ($row = $sql->fetch_row($periodsR)){
    if ($row[6] < 5){
        $prev_hours += $row[1];
        $sums[0] += $row[1];
        $sums[5] += $row[1];
        continue;
    }
    
    echo "<tr class=$class[$c]>";
    $total = $row[1]+$row[2]+$row[5];
    echo "<td class=left>$row[0]</td>";
    for ($i=1; $i<6;$i++) echo "<td>$row[$i]</td>";
    echo "<td>$total</td>";
    echo "</tr>";
    for ($i=1; $i<6;$i++)
        $sums[$i-1]+=$row[$i];
    $sums[5]+=$total;
    $c = ($c+1)%2;
}

echo "<tr class=$class[$c]><td class=left>Previous hours</td>";
echo "<td>$prev_hours</td>";
for ($i=0;$i<4;$i++) echo "<td>0</td>";
echo "<td>$prev_hours</td></tr>";
$c = ($c+1)%2;


echo "<tr class=$class[$c]>";
echo "<th class=left>Total</th>";
foreach ($sums as $s) echo "<th class=right>$s</th>";
echo "</tr>";
echo "</table>";
echo "<i>* Total does not include PTO & UTO hours taken. These hours do not count towards
consecutive hours worked.</i>";
echo "<p />";
echo "<div id=disclaimer>
<u>Please Note</u>: This web-base PTO Access Page is new. If you notice any problems,
please contact Colleen or Andy.
</div>";


echo "</body></html>";

