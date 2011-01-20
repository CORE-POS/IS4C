<?php

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

$query = "SELECT MAX(card_no) FROM meminfo WHERE card_no < 5500";
$result = $sql->query($query);
$row = $sql->fetch_array($result);

//$numStart = 6910;
$numBegin= $row[0] + 1;
$numEnd = $numBegin + 19;
echo $row[0] . "<br>";
echo $numEnd . "<br>";
$numName = $numStart . " NEW MEMBER";


FOR($numStart=$numBegin;$numStart<$numEnd+1;$numStart++){
	$query1 = "INSERT INTO mbrmastr VALUES($numStart,'','','','','DULUTH','MN','','',20,80,'00/00/0000',0,0,1,0,0,'','',0,3,'','','P',1,'',0,0,0,0,0,'',0,'00/00/0000',0,0,0,0,'00/00/0000','00/00/0000','','',0,0,0,1,1,0,0,'00/00/0000',0,0,0,0,0,0,0,1,'00/00/0000',0,'00/00/0000',0,0,0,0,0,0,'',0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,'',0,0,0,0,0,0,0,0,'00/00/0000','',0)";
	echo $query1 . "<br>";
	$result1 = $sql->query($query1);

	$query4 = "INSERT INTO meminfo VALUES ($numStart,'','','','','','','','','','','',0)";
	echo $query4 ."<br />";
	$sql->query($query4);

	$query2 = "INSERT INTO memNames VALUES('NEW STAFF','',$numStart,1,1,1,1,'$numStart.1.1')";
	echo $query2 . "<br>";
	$result2 = $sql->query($query2);

	$query3 = "INSERT INTO custdata VALUES($numStart,1,'NEW STAFF','',999.99,0,12,0,1,1,1,'REG',9,0,0,0,999,999,'$numName',1)";
	echo $query3;
	$result3 = $sql->query($query3);

}
?>
