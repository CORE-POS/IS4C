<?php

include('../../../config.php');
header('Location: '.$FANNIE_URL.'modules/plugins2.0/WfcHoursTracking/WfcHtListPage.php');
exit;
/*
$dept_restrict = "WHERE deleted=0 ";
$selected_dept = "";
if (isset($_GET["showdept"])){
	$selected_dept = $_GET["showdept"];
	if (!empty($selected_dept) && $selected_dept != -1)
		$dept_restrict = " WHERE deleted=0 AND department=$selected_dept ";
	else if ($selected_dept == -1)
		$dept_restrict = " WHERE deleted=1 ";
}

require($FANNIE_ROOT.'auth/login.php');
$ALL = validateUserQuiet('view_all_hours');
$dept_list = '';
if (!$ALL){
	$valid_depts = array(10,11,12,13,20,21,30,40,50,60,998);
	$validated = false;
	$dept_list = "(";
	$good_restrict = false;
	foreach ($valid_depts as $d){
		if (validateUserQuiet('view_all_hours',$d)){
			$validated = true;
			$dept_list .= $d.",";
			if (isset($_GET['showdept']) && $d == $_GET['showdept'])
				$good_restrict = true;
		}
	}
	if (!$validated){
		header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/hours/list.php");
		return;
	}
	else {
		$dept_list = substr($dept_list,0,strlen($dept_list)-1).")";
		if (!$good_restrict)
			$dept_restrict = " WHERE deleted=0 AND department IN $dept_list ";
	}
}

$edit = validateUserQuiet('edit_employees');

$sort = "e.name";
$dir = "asc";
if (isset($_GET["sort"]))
	$sort = $_GET["sort"];
if (isset($_GET["dir"]))
	$dir = $_GET["dir"];

$otherdir = "desc";
if ($dir == "desc") $otherdir="asc";

require('db.php');
$sql = hours_dbconnect();

if (isset($_GET["action"])) $_POST["action"] = $_GET["action"];

if (isset($_POST["action"])){
	switch ($_POST["action"]){
	case 'update':
		$name = $_POST["name"];
		$id = $_POST["id"];
		$adpid = $_POST["adpid"];
		$dept = $_POST["dept"];
		if (empty($adpid)) $adpid="NULL";
		if (empty($dept)) $dept="NULL";

		$upQ = "update employees set adpid=$adpid,name='$name',department=$dept where empID=$id";
		$upR = $sql->query($upQ);
		break;
	case 'delete':
		$id = $_GET["id"];
		$upQ = "update employees set deleted=1 where empID=$id";
		$upR = $sql->query($upQ);
		break;
	case 'undelete':
		$id = $_GET["id"];
		$upQ = "update employees set deleted=0 where empID=$id";
		$upR = $sql->query($upQ);
		break;
	}
}

$fetchQ = "select e.name,e.adpID,
	case when e.department>=998 then 'Salary' else e.PTOLevel end as PTOLevel,
	case when e.department>=998 then '&nbsp;' else h.totalHours end as totalHours,
	c.cusp,e.empID,
	case when s.totalTaken is null then p.ptoremaining else e.adpID-s.totalTaken end as ptoremaining,
	case when e.department>=998 then '&nbsp;' else u.hours end as hours
	from employees as e left join hoursalltime as h on e.empID=h.empID
	left join cusping as c on e.empID=c.empID
	left join pto as p on e.empID=p.empID
	left join uto as u on e.empID=u.empID
	left join salarypto_ytd s on e.empID=s.empID
	$dept_restrict
	order by $sort $dir";
$fetchR = $sql->query($fetchQ);

echo "<html><head><title>Employees</title>";
echo "<style type=text/css>
tr.pre td {
	color: #ffffff;
	background-color: #00cc00;
}
tr.post td {
	color: #ffffff;
	background-color: #cc0000;
}
tr.post a {
	color: #cccccc;
}
tr.earned td {
	color: #ffffff;
	background-color: #000000;
}
tr.earned a {
	color: #aaaaaa;
}
td {
	color: #000000;
	background-color: #ffffff;
}
a {
	color: #0000aa;
}
</style>";
echo "</head><body>";

if ($ALL){
	$deptsQ = "select name,deptID from Departments order by name";
	$deptsR = $sql->query($deptsQ);
	echo "Show Department: ";
	echo "<select onchange=\"top.location='/it/hours/list.php?showdept='+this.value;\">";
	echo "<option value=\"\">All</option>";
	while ($deptsW = $sql->fetch_row($deptsR)){
		if ($selected_dept == $deptsW[1])
			echo "<option value=$deptsW[1] selected>$deptsW[0]</option>";
		else
			echo "<option value=$deptsW[1]>$deptsW[0]</option>";
	}
	if ($selected_dept == -1)
		echo "<option selected value=\"-1\">DELETED</option>";
	else
		echo "<option value=\"-1\">DELETED</option>";
	echo "</select>";
}
else if (strlen($dept_list) > 4){
	$deptsQ = "select name,deptID from Departments WHERE deptID IN $dept_list order by name";
	$deptsR = $sql->query($deptsQ);
	echo "Show Department: ";
	echo "<select onchange=\"top.location='/it/hours/list.php?showdept='+this.value;\">";
	echo "<option value=\"\">All</option>";
	while ($deptsW = $sql->fetch_row($deptsR)){
		if ($selected_dept == $deptsW[1])
			echo "<option value=$deptsW[1] selected>$deptsW[0]</option>";
		else
			echo "<option value=$deptsW[1]>$deptsW[0]</option>";
	}
	echo "</select>";
}

echo "<table cellspacing=0 cellpadding=4 border=1><tr>";
if ($sort == "e.name")
	echo "<th><a href=list.php?sort=e.name&dir=$otherdir&showdept=$selected_dept>Name</a></th>";
else
	echo "<th><a href=list.php?sort=e.name&dir=asc&showdept=$selected_dept>Name</a></th>";
if ($sort == "e.adpid")
	echo "<th><a href=list.php?sort=e.adpid&dir=$otherdir&showdept=$selected_dept>ADP ID</a></th>";
else
	echo "<th><a href=list.php?sort=e.adpid&dir=asc&showdept=$selected_dept>ADP ID</a></th>";
if ($sort == "e.ptolevel")
	echo "<th><a href=list.php?sort=e.ptolevel&dir=$otherdir&showdept=$selected_dept>PTO Level</a></th>";
else
	echo "<th><a href=list.php?sort=e.ptolevel&dir=asc&showdept=$selected_dept>PTO Level</a></th>";
if ($sort == "p.ptoremaining")
	echo "<th><a href=list.php?sort=p.ptoremaining&dir=$otherdir&showdept=$selected_dept>Avail. PTO</a></th>";
else
	echo "<th><a href=list.php?sort=p.ptoremaining&dir=desc&showdept=$selected_dept>Avail. PTO</a></th>";
if ($sort == "u.hours")
	echo "<th><a href=list.php?sort=u.hours&dir=$otherdir&showdept=$selected_dept>Avail. UTO</a></th>";
else
	echo "<th><a href=list.php?sort=u.hours&dir=desc&showdept=$selected_dept>Avail. UTO</a></th>";
if ($sort == "u.hours")
	echo "<th><a href=list.php?sort=h.totalhours&dir=$otherdir&showdept=$selected_dept>Total Hours</a></th>";
else
	echo "<th><a href=list.php?sort=h.totalhours&dir=desc&showdept=$selected_dept>Total Hours</a></th>";
echo "</tr>";

while ($fetchW = $sql->fetch_row($fetchR)){
	if ($fetchW[4] == "PRE")
		echo "<tr class=\"pre\">";
	elseif ($fetchW[4] == "POST")
		echo "<tr class=\"post\">";
	elseif ($fetchW[4] == "!!!")
		echo "<tr class=\"earned\">";
	else
		echo "<tr>";
	echo "<td><a href=viewEmployee.php?id=$fetchW[5]>$fetchW[0]</a>";
	echo "</td>";
	echo "<td>$fetchW[1]</td>";
	echo "<td align=center>$fetchW[2]</td>";
	echo "<td align=right>".(is_numeric($fetchW[6])?sprintf("%.2f",$fetchW[6]):$fetchW[6])."</td>";
	echo "<td align=right>".(is_numeric($fetchW[7])?sprintf("%.2f",$fetchW[7]):$fetchW[7])."</td>";
	echo "<td align=right>".(is_numeric($fetchW[3])?sprintf("%.2f",$fetchW[3]):$fetchW[3])."</td>";
	if ($edit){
		echo "<td><a href=editEmployee.php?id=$fetchW[5]>Edit</a></td>";
		if ($selected_dept == "-1") echo "<td><a href=list.php?action=undelete&id=$fetchW[5]>Undelete</a></td>";
		else echo "<td><a href=list.php?action=delete&id=$fetchW[5]>Delete</a></td>";
	}
	echo "</tr>";
}

echo "</table>";
echo "</body></html>";

?>
*/
