<?php

include('../../../config.php');
require($FANNIE_ROOT.'auth/login.php');
require('db.php');
$sql = db_connect();

$appID = -1;
if (isset($_GET['appID'])) $appID = $_GET['appID'];

$username = validateUserQuiet('apptracking',0);
if (!$username){
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/ApplicationTracking/view.php?appID=$appID");
    return;
}
refreshSession();

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

$today = date("Y-m-d");
$fname = "";
$lname = "";
$pc_date = date("Y-m-d");
$internal = 0;
$bestpractices = 0;
$referral = "";
$hired = 0;
$ERRORS = "";

$dataQ = $sql->prepare("SELECT * FROM applicants WHERE appID=?");
$dataR = $sql->execute($dataQ, array($appID));
if ($sql->num_rows($dataR) == 0 && $appID != -1)
    $ERRORS .= "Warning: No data found for applicant #$appID<br />";
else if ($appID != -1){
    $dataW = $sql->fetch_row($dataR);
    $fname = $dataW[1];
    $lname = $dataW[2];
    $today = $dataW[3];
    $pc_date = $dataW[4];
    $internal = $dataW[5];
    $bestpractices = $dataW[6];
    foreach(explode(",",$dataW[7]) as $i) $positions["$i"][0] = True;
    foreach(explode(",",$dataW[8]) as $i) $openings["$i"][0] = True;
    foreach(explode(",",$dataW[9]) as $i) $depts["$i"][0] = True;
    $referral = $dataW[10];
    $hired = $dataW[11];
}

?>

<html>
<head>
    <title>View applicant</title>
<style type=text/css>
a {
    color: blue;
}

.comment {
    background: #ffff66;
}

.interview1 {
    background: #ffffcc;
}

.interview2 {
    background: #ffcc66;
}

</style>
</head>
<body>
<?php echo $ERRORS ?>
<input type=submit value="Add a comment" onclick="top.location='comment.php?id=-1:<?php echo $appID ?>';" /> 
<input type=submit value="Schedule an interview" onclick="top.location='set_interview.php?appID=<?php echo $appID?>'" /> 
<input type=submit value="Go back to the list of applicants" onclick="top.location='list.php';" />
<table cellspacing=0 cellpadding=4 border=1>
<tr>
    <th class=comment>Application Date</th>
    <td colspan=3><?php echo $today ?></td>
</tr>
<tr>
    <th class=interview2>First Name</th>
    <td><?php echo $fname?></td>
    <th class=interview2>Last Name</th>
    <td><?php echo $lname?></td>
</tr>
<tr>
    <th valign=top class=comment>Positions applied for</th>
    <td>
    <?php foreach ($positions as $k=>$v) { 
        if ($v[0]) echo "$v[1]<br />";
    } ?>
    </td>
    <th valign=top class=comment>Forwarded to<br />department managers</th>
    <td>
    <?php foreach ($depts as $k=>$v) { 
        if ($v[0]) echo "$v[1]<br />";
    } ?>
    </td>
</tr>
<tr>
    <th class=interview2>Internal</th>
    <td><?php echo ($internal==1)?'YES':'NO'?></td>
    <th class=interview2>Hired</th>
    <td><?php echo ($hired==1)?'YES':'NO'?></td>
</tr>
</table>
<h3> Interview History &amp; Comments </h3>
<?php

$interviewQ = $sql->prepare("select scheduled,username,sent_regret,took_place,interviewID
        from interviews where appID=? order by scheduled");
$interviewR = $sql->execute($interviewQ, array($appID));

$interviews = array();
while($interviewW = $sql->fetch_row($interviewR)){
    $dateparts = explode("-",$interviewW['scheduled']);
    $jd = gregoriantojd($dateparts[1],$dateparts[2],$dateparts[0]);
    array_push($interviews,array($jd,$interviewW['scheduled'],$interviewW['username'],$interviewW['sent_regret'],
                    $interviewW['took_place'],$interviewW['interviewID']));
}

$notesQ = $sql->prepare("select noteID,appID,note_text,note_date,username from notes
        where appID=? order by note_date");
$notesR = $sql->execute($notesQ, array($appID));

$notes = array();
while($notesW = $sql->fetch_row($notesR)){
    $dateparts = explode('-',$notesW['note_date']);
    $jd = gregoriantojd($dateparts[1],$dateparts[2],$dateparts[0]);
    array_push($notes,array($jd,$notesW['noteID'],$notesW['appID'],$notesW['username'],
                $notesW['note_date'],$notesW['note_text']));
}

echo "<table cellpadding=4 cellspacing=0 border=1>";
while(!empty($interviews) or !empty($notes)){
    if (empty($notes) or (!empty($interviews) and $interviews[0][0] <= $notes[0][0])){
        $i = array_shift($interviews);
        if (count($interviews) % 2 == 0)
            echo "<tr class=interview1>";
        else
            echo "<tr class=interview2>";
        echo "<th>Interview Scheduled</th>";
        echo "<td>".$i[1]."</td>";
        echo "<td>".$i[2]."</td>";
        if ($i[4] == 0){
            if ($username == $i[2]){
                echo "<td>";
                echo "<input type=submit value=\"This interview has taken place\" ";
                echo "onclick=\"if(confirm('Click OK if the interview has taken place'))top.location='set_interview.php?action=fin&id=$i[5]';\" />";
                echo " ";
                echo "<input type=submit value=\"Cancel interview\" ";
                echo "onclick=\"if(confirm('Click OK to remove this scheduled interview'))top.location='set_interview.php?action=del&id=$i[5];'\" />";
                echo "</td>";
            }        
            else
                echo "<td>PENDING</td>";
            echo "<td>&nbsp;</td>";
        }
        else {
            echo "<td>COMPLETE</td>";
            if ($i[3] == 0){
                if ($username == $i[2]){
                    echo "<td>";
                    echo "<input type=submit value=\"Regret notice sent\" ";
                    echo "onclick=\"if(confirm('Click OK if you sent a regret notice'))top.location='set_interview.php?action=reg&id=$i[5];'\" />";
                    echo "</td>";
                }
                else
                    echo "<td>No regret notice</td>";
            }
            else {
                echo "<td>Regret notice sent</td>";
            }
            
        }
        echo "</tr>";
    }
    else {
        $n = array_shift($notes);
        echo "<tr>";
        echo "<th class=comment>Comment</th>";
        echo "<td rowspan=4 colspan=4 valign=top>$n[5]</td>";
        echo "</tr>";
        echo "<tr><td>$n[4]</td></tr>";
        echo "<tr><td>$n[3]</td></tr>";
        if ($n[3] == $username){
            echo "<tr><td>";
            echo "<input type=submit value=\"Edit your comment\" ";
            echo "onclick=\"top.location='comment.php?id=$n[1]:$n[2]';\" />";
            echo "</td></tr>";
        }
        else
            echo "<tr><td>&nbsp;</td></tr>";
    }
}

