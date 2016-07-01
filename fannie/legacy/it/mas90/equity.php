<?php
include('../../../config.php');
$date = $_GET['date'];
if (!isset($_GET['date'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="equity'.$date.'.csv"');
}

require($FANNIE_ROOT.'src/SQLManager.php');
include('../../db.php');
$sql->query("use $FANNIE_TRANS_DB");

$SEP=",";
$Q = "";
$NL = "\r\n";

$query = "select num from lastMasInvoice";
$result = $sql->query($query);
$INV_NUM = (int)array_pop($sql->fetchRow($result));

$query = "select card_no,trans_num,
        total,
        case when department=991 then 'EQUITY B' else 'EQUITY A' END,
    year(tdate),month(tdate),day(tdate),
        case when department=991 then 'EQB' else 'EQA' END
        from dlog_15
        where ".$sql->datediff($sql->now(),'tdate')." = 1
        and department in (991,992)
        order by department,card_no";
if (isset($_GET['date'])){
    $query = "select card_no,trans_num,
        total,
        case when department=991 then 'EQUITY B' else 'EQUITY A' END,
        year(tdate),month(tdate),day(tdate),
        case when department=991 then 'EQB' else 'EQA' END
        from dlog_15
        where ".$sql->datediff("'$date'",'tdate')." = 0
        and department in (991,992)
        order by department,card_no";
}
$result = $sql->query($query);

while ($row = $sql->fetch_row($result)){
    if ($INV_NUM <= 5000000) $INV_NUM=5000000;;
    echo "H".$SEP;
    echo $INV_NUM.$SEP;
    echo $row[0].$SEP;
    echo $Q.$row[4]."-".$row[5]."-".$row[6].$Q.$SEP;
    echo $Q.$row[3]." ".$row[1].$Q.$SEP;
    echo $Q.$row[1].$Q.$NL;
    echo "L".$SEP;
    echo $INV_NUM.$SEP;
    echo $Q.$row[7].$Q.$SEP;
    echo trim($row[2]).$NL;
    $INV_NUM=($INV_NUM+1)%10000000;
}

$INV_NUM--;
$sql->query("update lastMasInvoice set num=$INV_NUM");

