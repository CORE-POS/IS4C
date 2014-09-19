<?php
include(dirname(__FILE__) . '/../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_TRANS_DB);

$header = "Credit Card Report (supplemental)";
$page_title = "Fannie : Integrated CC Report";
include($FANNIE_ROOT.'src/header.html');
?>
<style type=text/css>
.hilite {
    background: #ffffcc;
}
</style>
<?php

$date = date("Y-m-d",mktime(0,0,0,date("m"),date("d")-1,date("Y")));
if (isset($_GET['date']))
    $date = $_GET['date'];

echo "<form action=index.php method=get>
<b>Date</b>: <input type=text name=date /> <input type=submit value=Submit />
</form>";

echo "<h3>Integrated CC Report for $date</h3>";

$seconds = strtotime($date);
$start = date('Y-m-d 00:00:00',$seconds);
$end = date('Y-m-d 23:59:59',$seconds);
$query = $dbc->prepare_statement("SELECT q.datetime,q.laneno,q.cashierno,q.transno,q.amount,
    q.PAN, year(q.datetime),day(q.datetime),
    month(q.datetime),r.xResultMessage
    FROM efsnetRequest q LEFT JOIN efsnetResponse r
    on r.date=q.date and r.cashierNo=q.cashierNo and 
    r.transNo=q.transNo and r.laneNo=q.laneNo
    and r.transID=q.transID
    left join efsnetRequestMod m
    on m.date = q.date and m.cashierNo=q.cashierNo and
    m.transNo=q.transNo and m.laneNo=q.laneNo
    and m.transID=q.transID
    where q.datetime between ? AND ?
    and q.laneNo <> 99 and q.cashierNo <> 9999
    and m.transID is null
    order by q.datetime,q.laneNo,q.transNo,q.cashierNo");
$result = $dbc->exec_statement($query,array($start,$end));

echo "<table cellspacing=0 cellpadding=4 border=1>
<tr><th>Date &amp; Time</th><th>Card</th><th>Amount</th>
<th>Response</th><th>POS receipt</th></tr>";
$sum = 0;
$htable = array();
while($row = $dbc->fetch_row($result)){
    printf("<tr %s><td>%s</td><td>%s</td><td>%.2f</td>
        <td>%s</td>
        <td><a href=\"{$FANNIE_URL}admin/LookupReceipt/RenderReceiptPage.php?month=%d&year=%d&day=%d&receipt=%s\">
        POS receipt</td></tr>", 
        (isset($htable[$row[4]."+".$row[5]])||$row[9]=="")?"class=hilite":"",
        $row[0],$row[5],$row[4],$row[9],
        $row[8],$row[6],$row[7],($row[2]."-".$row[1]."-".$row[3]));
    if (strstr($row[9],"APPROVED") || $row[9] == "" || strstr($row[9],"PENDING")){
        $sum += $row[4];
        $htable[$row[4]."+".$row[5]] = 1;
    }
}
printf("<tr><th colspan=2>Total Approved</th><td>%.2f</td>",$sum);
echo "<td colspan=2>&nbsp;</td></tr>";
echo "</table>";

?>
