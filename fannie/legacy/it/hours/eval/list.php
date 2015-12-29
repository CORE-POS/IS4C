<?php
include('../../../../config.php');

require($FANNIE_ROOT.'auth/login.php');
include('../db.php');

$name = checkLogin();
$perm = validateUserQuiet('evals');
if ($name === false && $perm === false){
    header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/eval/list.php");
    return;
}
else if ($perm === false){
    echo "Error";
    return;
}

$db = hours_dbconnect();

$order = 'name';
if (isset($_REQUEST['o'])) {
    switch($_REQUEST['o']) {
        case 'adpID':
            $order = 'adpID';
            break;
        case 'hireDate':
            $order = 'hireDate';
            break;
        case 'evalDate':
            $order = 'evalDate';
            break;
        default:
            $order = 'name';
            break;
    }
}
if ($order != 'name') $order .= ", name";

$clause = "";
$args = array();
if (isset($_REQUEST['eM']) && is_numeric($_REQUEST['eM']) && is_numeric($_REQUEST['eY'])){
    $clause = ' AND YEAR(nextEval) = ? AND MONTH(nextEval) = ? ';
    $args[] = $_REQUEST['eY'];
    $args[] = $_REQUEST['eM'];
}

$q = $db->prepare("SELECT e.empID,name,adpID,i.nextEval,
    DATE_FORMAT(i.hireDate,'%m/%d/%Y') as hireDate,
    t.title FROM employees as e
    left join evalInfo as i on e.empID=i.empID 
    left join EvalTypes as t ON i.nextTypeID=t.id
    WHERE deleted=0 $clause order by $order");
$r = $db->execute($q, $args);
echo '<style type="text/css">a{color:blue;}</style>';
echo '<form action=list.php method=get>';
echo 'Filter by next eval: <select name=eM>';
echo '<option value="">Month...</option>';
for($i=1;$i<=12;$i++){
    printf('<option value=%d>%s</option>',$i,
        date("F",mktime(0,0,0,$i,1,2000)));
}
echo '</select>';
echo ' <input type=text size=4 name=eY value="'.date("Y").'" />';
echo ' <input type=submit value=Filter /></form>';
echo '<b>Reports</b>: ';
echo '<a href="report.php">Most Recent Eval</a>';
echo '<p />';
echo "<table cellpadding=4 cellspacing=0 border=1>";
echo '<tr>
    <th><a href="list.php?o=name">Name</a></th>
    <th><a href="list.php?o=adpID">ADP ID</a></th>
    <th><a href="list.php?o=hireDate">Hire Date</a></th>
    <th colspan="2"><a href="list.php?o=nextEval">Next Eval</a></th>
</tr>';
while($w = $db->fetch_row($r)){
    $next = "&nbsp;";
    $tmp = explode("-",$w[3]);
    if (is_array($tmp) && count($tmp) == 3){
        $next = $tmp[1]."/".$tmp[0];
    }
    printf("<tr><td><a href=view.php?id=%d>%s</a></td>
        <td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>",
        $w[0],$w[1],$w[2],
        (!empty($w[4])?$w[4]:'&nbsp;'),$next,
        (!empty($w[5])?$w[5]:'&nbsp;'));
}
echo "</table>";

