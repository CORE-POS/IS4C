<?php

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

$query = "select c.cardno, c.lastname, c.firstname, m.street, m.city, m.state, m.zip
	  from custdata as c left join meminfo as m on c.cardno=m.card_no
	  LEFT JOIN memDates AS d ON c.cardno=d.card_no
	  where c.personnum = 1 and c.memtype in (1,3)
	  and (datediff(dd,getdate(),d.end_date) > 0 or d.end_date = '' or d.end_date is NULL)
	  and m.street <> ''
	  order by convert(int,c.cardno)";
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
