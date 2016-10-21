<html>
<head><title>IT Projects</title></head>

<body>

<?php

include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

require($FANNIE_ROOT.'auth/login.php');

$q = array();
$q[0] = "select projDesc, ITName, reqestDate, projID, priority from projects where status = 1 order by priority";
$q[1] = "select projDesc, NULL, reqestDate, projID, priority from projects where status = 0 order by priority";
$q[2] = "select projDesc, ITName, reqestDate, projID, priority, completeDate from projects where status = 2
         order by completeDate desc";
$n = array("In progress","Pending","Complete");


if (validateUserQuiet('projects')){
  echo "<a href=newproject.php>New project</a><p />";
}
else {
  echo "<a href={$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/projects/index.php>Login</a> to add projects<p />";  
}
echo "<a href=report.php>Report</a><p />";

for ($i = 0; $i < 3; $i++){
  echo "<h3>$n[$i]</h3>";
  echo "<table border=1 cellspacing=2 cellpadding=2><tr>";
  echo "<th>Project name</th><th>IT contact</th><th>Request date</th><th align=center>Priority</th>";
  if ($i == 2)
     echo "<th>Complete date</th>";
  echo "</tr>";
  $r = $sql->query($q[$i]);
  while ($row = $sql->fetchRow($r)){
    echo "<tr>";
    echo "<td><a href=project.php?projID={$row[3]}>$row[0]</a></td><td>$row[1]</td><td>$row[2]</td><td align=center>$row[4]</td>";
    if ($i == 2)
      echo "<td>$row[5]</td>";
    echo "</tr>";
  }
  echo "</table>";
}


?>

</body>
</html>
