<?php

include('../../../config.php');
require($FANNIE_ROOT.'auth/login.php');
$admin = validateUserQuiet('apptracking');
if (!$admin){
    if (!validateUserQuiet('apptracking',0)){
        header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/ApplicationTracking/list.php");
        return;    
    }
}
refreshSession();

require('db.php');
$sql = db_connect();

?>

<html>
<head>
    <title>List of applicants</title>
<style type=text/css>
a {
    color: blue;
}
</style>
</head>
<body>

<?php

$positionQ = "select positionID,name from positions order by case when name='Any' then '' else name end";
$positionR = $sql->query($positionQ);
$positions = array();
$openings = array();
while($positionW = $sql->fetch_row($positionR)){
    $positions["$positionW[0]"] = array(False,$positionW[1]);
    $openings["$positionW[0]"] = array(False,$positionW[1]);
}

$deptQ = "select deptID,name from departments order by name";
$deptR = $sql->query($deptQ);
$depts = array();
while ($deptW = $sql->fetch_row($deptR))
    $depts["$deptW[0]"] = array(False,$deptW[1]);

$orderby = "app_date";
$search = "";
$search_args = array();
if (isset($_GET["simplesearch"]) && $_GET["simplesearch"] != ""){
    $terms = explode(" ",$_GET["simplesearch"]);
    $search = "WHERE ";
    foreach($terms as $t) {
        $search .= "(a.first_name like ? or a.last_name like ?) AND";
        $search_args[] = '%'.$t.'%';
        $search_args[] = '%'.$t.'%';
    }
    $search = substr($search,0,strlen($search)-3);
}
if (isset($_GET['advancedsearch'])){
    $search = "WHERE ";
    $search_args = array();
    if (isset($_GET['lname'])){
        $terms = explode(" ",$_GET["lname"]);
        $search .= "(";
        foreach($terms as $t) {
            $search .= "a.last_name like ? OR";
            $search_args[] = '%'.$t.'%';
        }
        $search = substr($search,0,strlen($search)-2);
        $search .= ") AND ";
    }
    if (isset($_GET['fname'])){
        $terms = explode(" ",$_GET["fname"]);
        $search .= "(";
        foreach($terms as $t){
            $search .= "a.first_name like ? OR ";
            $search_args[] = '%'.$t.'%';
        }
        $search = substr($search,0,strlen($search)-3);
        $search .= ") AND ";
    }
    if (isset($_GET["applied_for"])){
        $search .= "(";
        foreach($_GET['applied_for'] as $p) {
            $search .= "positions like ? OR positions like ? OR positions like ? OR positions = ? OR ";
            $search_args[] = '%,'.$p.',%';
            $search_args[] = $p.',%';
            $search_args[] = '%,'.$p;
            $search_args[] = $p;
        }
        $search = substr($search,0,strlen($search)-3);
        $search .= ") AND ";    
    }
    if (isset($_GET["sent_to"])){
        $search .= "(";
        foreach($_GET['sent_to'] as $p){
            $search .= "sent_to like ? OR sent_to like ? OR sent_to like ? OR sent_to = ? OR ";
            $search_args[] = '%,'.$p.',%';
            $search_args[] = $p.',%';
            $search_args[] = '%,'.$p;
            $search_args[] = $p;
        }
        $search = substr($search,0,strlen($search)-3);
        $search .= ") AND ";    
    }
    if ($search == "WHERE ") $search = "";
    else $search = substr($search,0,strlen($search)-4);

}

$query = $sql->prepare("SELECT * FROM applicants as a
      LEFT JOIN interview_status as i on a.appID=i.appID
      $search
      ORDER BY app_date");
$result = $sql->execute($query, $search_args);

echo "<form action=list.php method=get>";
echo "<input type=text name=simplesearch /> <input type=submit value=Search />";
echo " <a href=search.php>Advanced search</a>";
if ($admin){
    echo " <input type=submit value=\"Add new applicant\" ";
    echo "onclick=\"top.location='newApp.php?appID=-1'; return false;\" />";
}
echo "</form>";
echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Name</th><th>Application<br />date</th>";
echo "<th>Internal</th><th>Applied For</th>";
echo "<th>Forwarded to</th>";
echo "<th>Been<br />interviewed</th>";
echo "<th>Future interviews<br />scheduled</th>";
if ($admin) echo "<th>&nbsp;</td>";
echo "</tr>";
while($row = $sql->fetch_row($result)){
    echo "<tr>";
    echo "<td><a href=view.php?appID=".$row[0].">";
    echo $row['last_name'].", ".$row['first_name']."</a></td>";
    echo "<td>".$row['app_date']."</td>";
    echo "<td>".(($row['internal']==1)?'YES':'NO')."</td>";
    
    $str = "";
    foreach(explode(",",$row['positions']) as $p)
        $str .= $positions["$p"][1].", ";
    $str = substr($str,0,strlen($str)-2);
    echo "<td>".$str."</td>";

    $str = "";
    foreach(explode(",",$row['sent_to']) as $p)
        $str .= $depts["$p"][1].", ";
    $str = substr($str,0,strlen($str)-2);
    echo "<td>".$str."</td>";

    echo "<td>".(($row['pastInterviews'])==1?'YES':'NO')."</td>";
    echo "<td>".(($row['futureInterviews'])==1?'YES':'NO')."</td>";

    if ($admin){
        echo "<td><input type=submit value=Edit onclick=\"top.location='newApp.php?appID=$row[0]';\" /></td>";
    }

    echo "</tr>";
}
echo "</table>";

