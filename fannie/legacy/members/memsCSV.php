<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

$query = "select c.cardno, c.lastname, c.firstname, m.street, 
      m.city, m.state, m.zip, m.phone,
      c.memType from custdata as c left join meminfo as m
      on c.cardno = m.card_no where c.type <> 'TERM' and 
      c.personnum = 1 order by convert(int,c.cardno)";
$result = $sql->query($query);

header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="memberData.csv"');

echo "MemberID,Name,Address1,Address2,City,State,Zip,Phone,TermsCode,TaxSchedule,Type\r\n";
while ($row = $sql->fetchRow($result)){
echo $row['cardno'].",";
echo "\"".$row['firstname']." ".$row['lastname']."\",";
if (strstr($row['street'],"\n") === False)
    echo "\"".$row['street']."\",\"\",";
else {
    $pts = explode("\n",$row['street']);
    echo "\"".$pts[0]."\",\"".$pts[1]."\",";
}
echo "\"".$row['city']."\",";
echo "\"".$row['state']."\",";
echo "\"".$row['zipcode']."\",";
echo "\"".$row['phone']."\",";
echo ",";
echo ",";
echo $row["memType"]."\r\n";
}

