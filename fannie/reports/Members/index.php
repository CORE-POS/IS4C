<?php

include(dirname(__FILE__) . '/../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

$where = "(c.CardNo < 5000 or c.CardNo > 5999)
    and (c.CardNo < 9000 or c.CardNo > 9100)
    and c.memType <> 2";
$type = isset($_REQUEST['type'])?$_REQUEST['type']:'Regular';
switch($type){
case 'Regular':
    break;
case 'Business':
    $where = "c.memtype = 2";
    break;
case 'Staff Members':
    $where = "c.memtype = 3";
    break;
case 'Staff NonMembers':
    $where = "c.memtype = 9";
    break;
case '#5000s':
    $where = "c.cardno BETWEEN 5000 AND 5999";
    break;
}

if (isset($_REQUEST['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="member report.xls"');
}
else {
    echo "<form action=index.php method=get><select name=type>";
    $types = array('Regular','Business','#5000s','Staff Members','Staff NonMembers');
    foreach($types as $t){
        printf("<option %s>%s</option>",
            ($t==$type?'selected':''),$t);
    }
    echo "</select>";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<input type=submit name=submit value=Submit />";
    echo "&nbsp;&nbsp;&nbsp;&nbsp;";
    echo "<input type=checkbox name=excel /> Excel";
    echo "</form><hr />";
}

$trans = $FANNIE_TRANS_DB;
if ($FANNIE_SERVER_DBMS=='MSSQL') $trans .= ".dbo";
$q = $dbc->prepare_statement("SELECT c.CardNo,
        month(CASE WHEN m.start_date IS NULL then n.startdate ELSE m.start_date END),
        day(CASE WHEN m.start_date IS NULL then n.startdate ELSE m.start_date END),
        year(CASE WHEN m.start_date IS NULL then n.startdate ELSE m.start_date END),
        month(m.end_date),day(m.end_date),year(m.end_date),
        c.FirstName,c.LastName,
        CASE WHEN s.type = 'I' THEN 1 ELSE 0 END AS isInactive,
        CASE WHEN r.textStr IS NULL THEN s.reason ELSE r.textStr END as reason,
        CASE WHEN n.payments IS NULL THEN 0 ELSE n.payments END as equity
    FROM custdata AS c LEFT JOIN memDates AS m
    ON m.card_no = c.CardNo 
    LEFT JOIN {$trans}.equity_live_balance AS n
    ON m.card_no=n.memnum LEFT JOIN suspensions AS s
    ON m.card_no=s.cardno LEFT JOIN reasoncodes AS r
    ON s.reasonCode & r.mask <> 0
    WHERE c.Type <> 'TERM' AND $where
    AND c.personNum=1
    ORDER BY c.CardNo");
$r = $dbc->exec_statement($q);
echo "<table cellspacing=0 cellpadding=4 border=1>
    <tr><th>#</th><th>First Name</th><th>Last Name</th>
    <th>Start</th><th>End</th><th>Equity</th>
    <th>Inactive</th></tr>";
$saveW = array(-1);
while($w = $dbc->fetch_row($r)){
    if ($w[0] != $saveW[0]){
        printRow($saveW);
        $saveW = $w;
    }
    else {
        $saveW['reason'] .= ", ".$w['reason'];
    }
    if ($saveW[3] < 1900 || ((int)$saveW[3]) == 0)
        $saveW['startdate'] = isset($_REQUEST['excel']) ? '' : '&nbsp;';
    else
        $saveW['startdate'] = sprintf("%d/%d/%d",$saveW[1],$saveW[2],$saveW[3]);
    if ($saveW[6] == 1900 || ((int)$saveW[6]) == 0)
        $saveW['enddate'] = isset($_REQUEST['excel']) ? '' : '&nbsp;';
    else
        $saveW['enddate'] = sprintf("%d/%d/%d",$saveW[4],$saveW[5],$saveW[6]);
}
printRow($saveW);
echo "</table>";

function printRow($arr){
    global $_REQUEST;
    $ph = isset($_REQUEST['excel'])?'':"&nbsp;";
    if (count($arr) <= 1) return;
    printf("<tr><td>%d</td><td>%s</td><td>%s</td>
        <td>%s</td><td>%s</td>
        <td>%.2f</td><td>%s</td></tr>",
        $arr[0],$arr['FirstName'],$arr['LastName'],
        $arr['startdate'],
        $arr['enddate'],
        $arr['equity'],
        ($arr['isInactive']==1?'INACTIVE - '.$arr['reason']:$ph)
    );
}

?>
