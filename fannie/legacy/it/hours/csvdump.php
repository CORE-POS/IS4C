<?php
include('../../../config.php');

$dept_restrict = "WHERE deleted=0 ";
require($FANNIE_ROOT.'auth/login.php');
$ALL = validateUserQuiet('view_all_hours');
if (!$ALL){
	$valid_depts = array(11,12,13,20,30,40,50,60);
	$validated = false;
	foreach ($valid_depts as $d){
		if (validateUserQuiet('view_all_hours',$d,$d)){
			$validated = true;
			$dept_restrict = " WHERE deleted=0 AND department=$d ";
			break;
		}
	}
	if (!$validated){
		header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/list.php");
		return;
	}
}

header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="csvdump.csv"');

$sort = "e.name";
$dir = "asc";
$otherdir = "desc";

require('db.php');
$sql = hours_dbconnect();

$fetchQ = "select e.name,e.adpID,e.PTOLevel,
	h.totalHours,c.cusp,e.empID,
	p.ptoremaining,u.hours
	from employees as e left join hoursalltime as h on e.empID=h.empID
	left join cusping as c on e.empID=c.empID
	left join pto as p on e.empID=p.empID
	left join uto as u on e.empID=u.empID
	$dept_restrict
	order by $sort $dir";
$fetchR = $sql->query($fetchQ);

while ($fetchW = $sql->fetch_row($fetchR)){
	echo "\"$fetchW[0]\",";
	echo "$fetchW[1],";
	echo "$fetchW[2],";
	echo "$fetchW[6],";
	echo "$fetchW[7],";
	echo "$fetchW[3]\r\n";
}

?>
