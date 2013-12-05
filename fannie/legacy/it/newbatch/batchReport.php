<?php
// not linked anywhere?
/*
include('../../../config.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$batchID = 1;

$batchID = $_GET['batchID'];

$batchInfoQ = "SELECT batchName,convert(varchar,startDate,101) as startDate,
	convert(varchar,endDate,101) as endDate FROM batches where batchID = $batchID";
$batchInfoR = $sql->query($batchInfoQ);


if(isset($_GET['excel'])){
   header('Content-Type: application/ms-excel');
   header('Content-Disposition: attachment; filename="batchSales.xls"');
   
}else{
?>

<html>
<head><title>Sales Batch History</title>
<style>
<!--
@import url(<?php echo $FANNIE_ROOT; ?>src/style.css);
-->
</style>
</head>
<body>

<?php
}

while($batchInfoW = $sql->fetch_array($batchInfoR)){
   $bName = $batchInfoW['batchName'];
   if(isset($_GET['startDate'])){
      $bStart = $_GET['startDate']." 00:00:00";
   }else{
      $bStart = $batchInfoW['startDate'];
   }
   if(isset($_GET['endDate'])){
      $bEnd = $_GET['endDate']." 23:59:59";
   }else{
      $bEnd = $batchInfoW['endDate'];
   }

   echo "<h2>$bName</h2>";
   echo "<p><font color=black>From: </font> $bStart <font color=black>to: </font> $bEnd</p>";
   //echo "<p class=excel><a href=changeReportDate.php?batchID=$batchID>Click here to change date range</a>";
   //echo "&nbsp;<a href=forLisa.php?batchID=$batchID>Reset dates</a></p>";
}

$dlog = DTransactionsModel::selectDlog($bStart);
//echo $dlog;


$bnStart = strtotime($bStart);
$bnStart = date('Y-m-j',$bnStart);

$bnEnd = strtotime($bEnd);
$bnEnd = date('Y-m-j',$bnEnd);

if(!isset($_GET['excel'])){
   echo "<p class=excel><a href=batchReport.php?batchID=$batchID&excel=1&startDate=$bnStart&endDate=$bnEnd>Click here for Excel version</a></p>";
}

$salesBatchQ ="select d.upc, b.description, sum(d.total) as sales, sum(d.quantity) as quantity
         FROM $dlog as d left join batchMergeTable as b
         ON d.upc = b.upc
         WHERE d.tdate BETWEEN '$bStart' and '$bEnd' 
         AND b.batchID = $batchID 
         GROUP BY d.upc, b.description";
//echo $salesBatchQ;

$salesBatchR= $sql->query($salesBatchQ);

$i = 0;

echo "<table border=0 cellpadding=1 cellspacing=0 ><th>UPC<th>Description<th>$ Sales<th>Quantity";
while($salesBatchW = $sql->fetch_array($salesBatchR)){
   $upc = $salesBatchW['upc'];
   $desc = $salesBatchW['description'];
   $sales = $salesBatchW['sales'];
   $qty = $salesBatchW['quantity'];
   $imod = $i%2;
   
   if($imod==1){
      $rColor= '#ffffff';
   }else{
      $rColor= '#ffffcc';
   }

   echo "<tr bgcolor=$rColor><td width=120>$upc</td><td width=300>$desc</td><td width=50>$sales</td><td width=50 align=right>$qty</td></tr>";
   $i++;
}
?>
</body>
</html>
*/
