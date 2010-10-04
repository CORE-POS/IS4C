<?php
$path = "";
$found = False;
$uri = $_SERVER['REQUEST_URI'];
$tmp = explode("?",$uri);
foreach(explode("/",$_SERVER["REQUEST_URI"]) as $x){
	if (strpos($x,".php") === False
		&& strlen($x) != 0){
		$path .= "../";
	}
	if (!$found && stripos($x,"fannie") !== False){
		$found = True;
		$path = "";
	}
}
require($path.'src/mysql_connect.php');


$query = "SELECT * FROM departments WHERE dept_discount <> 0";
$result = $dbc->query($query);

echo "<td><font size='-1'>
	<p><input type='checkbox' value=1 name='allDepts' CHECKED><b>All Departments</b><br>";
while ($row = $dbc->fetch_row($result)) {
	echo "<input type='checkbox' name='dept[]' value='".$row['dept_no']."'>".ucwords(strtolower($row['dept_name']))."<br>";
}
echo "</p></font></td>";

?>
