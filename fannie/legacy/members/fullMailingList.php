<?php

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

$query = "select c.CardNo, c.LastName, c.FirstName, m.street, m.city, m.state, m.zip
	  from custdata as c left join meminfo as m on c.cardno=m.card_no
	  where c.personNum = 1 and c.memType in (1,3)
	  AND c.Type='PC'
	  AND LastName <> 'NEW MEMBER'
	  order by ".$sql->convert('c.CardNo','INT');
$NL = "<br />";
if (!isset($_GET["excel"])){
	echo "<a href=fullMailingList.php?excel=yes>Save to Excel</a>".$NL;
}
else {
	$NL = "\n";	
	header("Content-Disposition: inline; filename=FullMailingList.csv");
	header("Content-type: application/vnd.ms-excel; name='excel'");
}
echo "Mem#,Lastname,Firstname,Address1,Address2,City,State,Zip".$NL;
$result = $sql->query($query);
while ($row = $sql->fetch_array($result)){
	echo $row[0].",";
	echo "\"".$row[1]."\",";
	echo "\"".$row[2]."\",";
	if (strstr($row[3],"\n") === False){
		echo "\"".$row[3]."\",\"\",";
	}
	else {
		$pts = explode("\n",$row[3]);
		echo "\"".$pts[0]."\",\"".$pts[1]."\",";
	}
	echo "\"".$row[4]."\",";
	echo "\"".$row[5]."\",";
	echo "\"".$row[6]."\"".$NL;
}
?>
