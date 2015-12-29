<?php

include('../../config.php');
include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

if (isset($_GET['excel'])){
    header('Content-Type: application/ms-excel');
    header('Content-Disposition: attachment; filename="salesReport.xls"');
}

$dlog = DTransactionsModel::selectDlog('2006-07-01','2008-06-30');

echo "<table cellspacing=0 cellpadding=4 border=1>";
echo "<tr><th>Date</th><th>Bulk</th><th>Cool</th><th>Deli</th>
<th>Grocery</th><th>HBC</th><th>Produce</th>
<th>Marketing</th><th>Meat</th><th>Gen. Merch</th>
<th>Equity</th><th>BNK</th><th>N/B</th></tr>";

$query = "SELECT datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate),
    SUM(CASE WHEN d.superID=1 THEN total ELSE 0 END) as Blk,
    SUM(CASE WHEN d.superID=2 THEN total ELSE 0 END) as Cool,
    SUM(CASE WHEN d.superID=3 THEN total ELSE 0 END) as Deli,
    SUM(CASE WHEN d.superID=4 THEN total ELSE 0 END) as Grocery,
    SUM(CASE WHEN d.superID=5 THEN total ELSE 0 END) as HBC,
    SUM(CASE WHEN d.superID=6 THEN total ELSE 0 END) as Produce,
    SUM(CASE WHEN d.superID=7 THEN total ELSE 0 END) as Marketing,
    SUM(CASE WHEN d.superID=8 THEN total ELSE 0 END) as Meat,
    SUM(CASE WHEN d.superID=9 THEN total ELSE 0 END) as GenMerch,
    SUM(CASE WHEN t.department IN (991,992) THEN total ELSE 0 END) as Equity,
    SUM(CASE WHEN t.trans_type='T' AND t.trans_subtype IN ('CA','CK')
        THEN t.total ELSE 0 END) as BNK,
    SUM(CASE WHEN t.trans_type='T' AND t.trans_subtype NOT IN ('CA','CK')
        THEN t.total ELSE 0 END) as NBNK
    FROM $dlog AS t LEFT JOIN departments AS d
    ON t.department = d.dept_no
    LEFT JOIN MasterSuperDepts AS s ON d.dept_no=s.dept_ID
    GROUP BY datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate)
    ORDER BY datepart(yy,tdate),datepart(mm,tdate),datepart(dd,tdate)";
//echo $query;
$result = $sql->query($query);

while($row = $sql->fetch_row($result)){
    echo "<tr>";
    echo "<td>".$row[0]."-".$row[1]."-".$row[2]."</td>";
    for($i=3;$i<15;$i++)
        echo "<td>$row[$i]</td>";
    echo "</tr>";
}
echo "</table>";

