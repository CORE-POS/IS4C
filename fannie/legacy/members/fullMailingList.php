<?php

include('../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

$query = "select c.CardNo, c.LastName, c.FirstName, m.street, m.city, m.state, m.zip, e.payments
      from custdata as c 
        left join meminfo as m on c.CardNo=m.card_no
        left join is4c_trans.equity_history_sum as e ON c.CardNo=e.card_no
      where c.personNum = 1
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
echo "Mem#,Lastname,Firstname,Address1,Address2,City,State,Zip,Equity Bal.".$NL;
$result = $sql->query($query);
while ($row = $sql->fetchRow($result)){
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
    echo "\"".$row[6]."\",";
    echo "\"".$row[7]."\"".$NL;
}

