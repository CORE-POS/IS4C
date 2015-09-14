<?php

include('../../../config.php');
require('db.php');
$sql = db_connect();

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

?>

<html>
<head>
    <title>Advanced Search</title>
</head>
<body>
<form action=list.php method=get>

<table cellspacing=0 cellpadding=4 border=1>
<tr>
    <th>First Name</th>
    <td><input type=text name=fname value="<?php echo $fname?>" /></td>
    <th>Last Name</th>
    <td><input type=text name=lname value="<?php echo $lname?>"/></td>
</tr>
<tr>
    <th valign=top>Positions applied for</th>
    <td>
    <?php foreach ($positions as $k=>$v) { 
        echo "<input type=checkbox name=applied_for[] value=$k ";
        if ($v[0]) echo "checked ";
        echo "/> $v[1]<br />"; 
    } ?>
    </td>
    <th valign=top>Forwarded to</th>
    <td>
    <?php foreach ($depts as $k=>$v) { 
        echo "<input type=checkbox name=sent_to[] value=$k ";
        if ($v[0]) echo "checked ";
        echo "/> $v[1]<br />"; 
    } ?>
    </td>
</tr>
</table>
<b>Sort by: <select name=orderby><option value="a.app_date">Application date</option><option value="a.last_name">Name</option>
&nbsp;&nbsp;&nbsp;<input type=submit value=Search />
<input type=hidden name=advancedsearch value=1 />
</form>
</body>
</html>
