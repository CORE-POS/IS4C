<?php
include('../../../../config.php');

require($FANNIE_ROOT.'auth/login.php');
include('../db.php');

$name = checkLogin();
$perm = validateUserQuiet('evals');
if ($name === false && $perm === false){
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/eval/report.php");
    return;
}
else if ($perm === false){
    echo "Error";
    return;
}

if (isset($_REQUEST['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="lastEvalReport.xls"');
}
else {
    echo '<a href="report.php?excel=yes">Save as Excel</a>';
}

$db = hours_dbconnect();

$q = "SELECT s.month,s.year,t.title,s.evalScore,e.name,e.empID,e.adpID
    FROM employees as e left join evalScores as s ON s.empID=e.empID
    LEFT JOIN EvalTypes as t ON s.evalType=t.id    
    WHERE deleted=0 ORDER BY e.name,s.year desc, s.month desc";
$r = $db->query($q);
echo "<table cellpadding=4 cellspacing=0 border=1>";
echo '<tr>
</tr>';
$lastEID = -1;
while($w = $db->fetch_row($r)){
    if ($w['empID'] == $lastEID) continue;
    else $lastEID = $w['empID'];
    $date = '&nbsp;';
    if (!empty($w['month']) && !empty($w['year']))
        $date = date("F Y",mktime(0,0,0,$w['month'],1,$w['year']));
    $score = '&nbsp;';
    if (!empty($w['evalScore'])){
        $score = str_pad($w['evalScore'],3,'0');
        $score = substr($score,0,strlen($score)-2).".".substr($score,-2);
    }
    printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
        $w['name'],$w['adpID'],$date,(!empty($w['title'])?$w['title']:'&nbsp;'),$score);
}
echo "</table>";

