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
    $validated = false;
    $sql = hours_dbconnect();
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

echo "<h3>Salary Employee PTO Status</h3>";

$sql = hours_dbconnect();
$infoQ = $sql->prepare("select e.name,e.adpID,
    s.totalTaken as daysTaken
    from employees as e left join
    salarypto_ytd as s on e.empID=s.empID
    where e.empID=?");
$infoR = $sql->execute($infoQ, array($empID));
$infoW = $sql->fetch_row($infoR);

echo "<h2>$infoW[0] [ <a href={$FANNIE_URL}auth/ui/loginform.php?logout=yes>Logout</a> ]</h2>";
echo "<table cellspacing=0 cellpadding=4 border=1 id=newtable>";
echo "<tr class=one><th>PTO Allocation</th><td>$infoW[1]</td></tr>";
echo "<tr class=two><th>PTO Taken, YTD</th><td>$infoW[2]</td></tr>";
echo "<tr class=one><th>PTO Remaining</th><td>".($infoW[1]-$infoW[2])."</td></tr>";
echo "</tr></table>";

$periodsQ = $sql->prepare("select daysUsed,month(dstamp),year(dstamp) 
        from salaryHours where empID=? order by dstamp DESC");
$periodsR = $sql->execute($periodsQ, array($empID));
$class = array("one","two");
$c = 0;
echo "<table id=payperiods cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Month</th><th>PTO Taken</th></tr>";
while ($row = $sql->fetch_row($periodsR)){
    echo "<tr class=\"$class[$c]\">";
    $dstr = date("F Y",mktime(0,0,0,$row[1],1,$row[2]));
    echo "<td>$dstr</td>";
    echo "<td>$row[0]</td>";
    echo "</tr>";    
    $c = ($c+1)%2;
}

echo "</table>";
echo "<div id=disclaimer>
<u>Please Note</u>: This web-base PTO Access Page is new. If you notice any problems,
please contact Colleen or Andy.
</div>";


echo "</body></html>";

