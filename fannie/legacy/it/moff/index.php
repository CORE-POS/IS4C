<?php
include('../../../config.php');
if (isset($_GET['excel'])){
    header("Content-Disposition: inline; filename=moffResults.xls");
      header("Content-Description: PHP3 Generated Data");
      header("Content-type: application/vnd.ms-excel; name='excel'");
   }
?>

<html>
<head>
    <title>MOFF Results</title>
</head>
<body>

<?php

    if (!isset($_GET['excel'])){
        echo "<a href=index.php?excel=yes>Excel</a><p />";
    }

    if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
    include('../../db.php');

    $date = '2011-08-06';
    $args = array($date, 21, 22);
    $dlog = DTransactionsModel::selectDtrans($date);
    //$dlog = str_replace("dlog","transarchive",$dlog);
    //$dlog = "trans_archive.dbo.transArchive201008";

    $query = $sql->prepare("SELECT DISTINCT t.upc, min(t.description), SUM(t.quantity),SUM(t.total),d.dept_name,d.salesCode,u.likeCode
              FROM $dlog as t 
              LEFT JOIN departments as d on d.dept_no = t.department
              LEFT JOIN upcLike as u on t.upc=u.upc
              WHERE t.trans_type in ('I','D') and t.upc <> 'DISCOUNT' and
              datediff(dd,?,t.datetime) = 0 AND t.register_no
                          in ( ?, ? )
              and trans_Status <> 'X'
                          and emp_no <> 9999
              GROUP BY t.upc, d.dept_name,d.salesCode,u.likeCode
              ORDER BY t.upc");

    //echo $query;
    $result = $sql->execute($query, $args);

    echo "<table border=1>\n"; //create table
    echo "<tr>";
    echo "<td>UPC</td>";
    echo "<td>Description</td>";
    echo "<td>Qty</td>";
    echo "<td>Sales</td>";
    echo "<td>Department</td>";
    echo "<td>pCode</td>";
    echo "<td>LikeCode</td>";
    echo "</tr>\n";//create table header
    $qtysum = 0;
    $salesum = 0;
    while ($myrow = $sql->fetchRow($result)){
        printf("<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%s</td><td>%s</td><td>%s</td><td>%d</td></tr>\n",$myrow[0], $myrow[1],$myrow[2], $myrow[3], $myrow[4], $myrow[5],$myrow[6]);
        $qtysum += $myrow[2];
        $salesum += $myrow[3];
    }
    echo "<tr><td>Totals</td><td>&nbsp;</td><td>$qtysum</td><td>$salesum</td></tr>";
    echo "</table>";
    
?>
</body>
</html>
