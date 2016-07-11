<?php
include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');
if (!class_exists('WfcLib')) {
    require(dirname(__FILE__) . '/../../reports/Store-Specific/WFC/WfcLib.php');
}

$excel = FormLib::get('excel') !== '' ? true : false;
if ($excel) {
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="stockOverdueReport.xls"');
} else {
?>

<HTML>
<head>
<style type=text/css>
td {
    font-size: .9em;
}
td.left {
    padding-right: 4em;
    text-align: left;
}
td.right {
    padding-left: 4em;
    text-align: right;
}
td.center {
    padding-left: 2em;
    padding-right: 2em;
    text-align: center;
}
</style>
</head>
<BODY BGCOLOR = 'ffffff' ><font size=2> 
<?php
}

if ($excel === false) {
    echo "<br /><a href=stockOverdueReport.php?excel=yes>Click here for Excel version</a>";
}

$balanceQ = "SELECT s.memnum,s.payments,s.enddate,b.balance,
        c.lastname,c.firstname,m.street,
        m.city,m.state,m.zip
        FROM equity_live_balance as s left join
        custdata as c on s.memnum=c.cardno left join
        ar_live_balance as b on s.memnum=b.card_no
        left join meminfo as m on s.memnum=m.card_no
        WHERE c.personnum = 1 and c.type <> 'TERM'
        and s.payments < 100 and
        ".$sql->datediff('s.enddate',$sql->now())." < -60
        order by s.memnum";
$balanceR = $sql->query($balanceQ);
$balances = array();
while ($row = $sql->fetch_row($balanceR)){
    $temp = explode(" ",$row[2]);
    $datestr = $temp[0];
    $balances["$row[0]"] = array($row[4].", ".$row[5],str_replace("\n"," ",$row[6]),$row[7],$row[8],$row[9],$row[1],$datestr,$row[3]);
} 

echo WfcLib::tablify($balances,array(0,1,2,3,4,5,6,7,8),array("Account","Name","Address","City","State","Zip",
         "Current Stock Balance","Stock due date","Current AR Balance"),
         array(WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_LEFT,
         WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY,WfcLib::ALIGN_LEFT,WfcLib::ALIGN_RIGHT|WfcLib::TYPE_MONEY));

