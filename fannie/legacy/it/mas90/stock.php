<?php
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="dailyAR.csv"');

include('../../../config.php');
require($FANNIE_ROOT'src/SQLManager.php');
include('../../db.php');

$SEP=",";
$Q = "\"";
$NL = "\r\n";

$query = "select card_no,sum(total) from dlog_15 where department in (991,992)
    and ".$sql->datediff($sql->>now(),'tdate')." = 1
    group by card_no";
$result = $sql->query($query);

while($row = $sql->fetch_row($result)){
    echo "00-".str_pad($row[0],7,"0",STR_PAD_LEFT).$SEP;
    echo $row[1].$NL;
}

