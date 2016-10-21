<?php
header('Content-Type: application/ms-excel');
header('Content-Disposition: attachment; filename="memberData.csv"');

include('../../../config.php');
require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');

$SEP=",";
$Q = "\"";
$NL = "\r\n";

$query = "select m.card_no,
      concat(c.FirstName,' ',c.LastName),
      m.street,
      '',
      m.city,
      m.state,
      m.zip,
      m.phone,
      case when c.memType = 2 then '07'
      when m.card_no <= 4999 then '14'
      when m.card_no > 4999 and m.card_no <= 5499 then '22'
      when m.card_no > 5499 and m.card_no <= 5999 then '99'
      else '14' end as payTermCode
      from meminfo as m left join custdata as c
      on m.card_no=c.CardNo where c.personNum = 1
      and c.Type <> 'TERM'"; 
$result = $sql->query($query);

while ($row = $sql->fetch_row($result)){
    echo $row[0].$SEP;
    echo $Q.$row[1].$Q.$SEP;
    if (strstr($row[2],"\n") === False)
        echo $Q.$row[2].$Q.$SEP.$Q.$Q.$SEP;
    else{
        $pts = explode("\n",$row[2]);
        echo $Q.$pts[0].$Q.$SEP;
        echo $Q.$pts[1].$Q.$SEP;
    }
    echo $Q.$row[3].$Q.$SEP;
    echo $Q.$row[4].$Q.$SEP;
    echo $Q.$row[5].$Q.$SEP;
    echo $Q.$row[6].$Q.$SEP;
    echo $Q.$row[7].$Q.$SEP;
    echo $row[8].$NL;
}

