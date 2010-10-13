<?php

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

require($FANNIE_ROOT.'auth/login.php');

if (!validateUserQuiet('memgen')){
  echo "<a href=\"{$FANNIE_ROOT}auth/ui/loginform.php?redirect={$FANNIE_ROOT}legacy/members/genNums.php\">Log in</a>";
  echo " to generate new member numbers";
  return;
}

$query = "SELECT MAX(card_no) FROM meminfo";
$result = $sql->query($query);
$row = $sql->fetch_array($result);

//$numStart = 6910;
$numBegin= $row[0] + 1;
$numEnd = $numBegin + 39;
//echo $row[0] . "<br>";
echo $numBegin . ' Starting Number <br>';
echo $numEnd . " Ending Number <br>";
$numName = $numStart . " NEW MEMBER";

FOR($numStart=$numBegin;$numStart<$numEnd+1;$numStart++){
	$query1 = "INSERT INTO mbrmastr VALUES($numStart,'','','','','DULUTH','MN','','',20,80,'00/00/0000',0,0,1,20,0,'','',0,1,'','','P',1,'',0,0,0,0,0,'',0,'00/00/0000',0,0,0,0,'00/00/0000','00/00/0000','','',0,0,0,1,1,0,0,'00/00/0000',0,0,0,0,0,0,0,1,'00/00/0000',0,'00/00/0000',0,0,0,0,0,0,'',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,'',0,0,0,0,0,0,0,0,'00/00/0000','',0)";
	echo $query1 . "<br>";
	$result1 = $sql->query($query1);

	$query4 = "INSERT INTO meminfo VALUES ($numStart,'','','','','','','','','','','',0)";
	echo $query4 ."<br />";
	$sql->query($query4);

	$query2 = "INSERT INTO memNames VALUES('NEW MEMBER','',$numStart,1,1,1,1,'$numStart.1.1')";
	echo $query2 . "<br>";
	$result2 = $sql->query($query2);

	$query3 = "INSERT INTO custdata VALUES($numStart,1,'NEW MEMBER','',999.99,0,0,0.00,1,1,1,'PC',1,0,0,0,999,1,'$numName',1)";
	echo $query3;
	$result3 = $sql->query($query3);

	$query4 = "INSERT INTO memDates VALUES($numStart,NULL,NULL)";
	echo $query4;
	$result4 = $sql->query($query4);
}
?>
