<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_OP_DB);

$batchID = 1;
if (isset($_GET['batchID']))
	$batchID = $_GET['batchID'];

/* use batch report in reports directory */
header('Location: '.$FANNIE_URL.'reports/BatchReport/BatchReport.php?batchID[]='.$batchID);
exit;

$batchInfoQ = "SELECT batchName,startDate,endDate FROM batches where batchID = $batchID";
$batchInfoR = $dbc->query($batchInfoQ);


if(isset($_GET['excel'])){
   header('Content-Type: application/ms-excel');
   header('Content-Disposition: attachment; filename="batchSales.xls"');
   
}else{

$page_title = "Batch Report";
$header = "Fannie :: Batch Report";
include("../../src/header.html");

}
$bStart = "";
$bEnd = "";
while($batchInfoW = $dbc->fetch_array($batchInfoR)){
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
}

$bnStart = strtotime($bStart);
$bnStart = date('Y-m-d',$bnStart);

$bnEnd = strtotime($bEnd);
$bnEnd = date('Y-m-d',$bnEnd);

$dlog = DTransactionsModel::selectDlog($bnStart,$bnEnd);
$sumTable = $FANNIE_ARCHIVE_DB.$dbc->sep().'sumUpcSalesByDay';

if(!isset($_GET['excel'])){
   echo "<p class=excel><a href=batchReport.php?batchID=$batchID&excel=1&startDate=$bnStart&endDate=$bnEnd>Click here for Excel version</a></p>";
}

$salesBatchQ ="select d.upc, b.description, sum(d.total) as sales, sum(quantity) as quantity
         FROM $sumTable as d left join batchMergeTable as b
         ON d.upc = b.upc
         WHERE d.tdate BETWEEN '$bStart' and '$bEnd' 
         AND b.batchID = $batchID 
         GROUP BY d.upc, b.description";

$salesBatchR= $dbc->query($salesBatchQ);

$i = 0;

echo "<table border=0 cellpadding=1 cellspacing=0 ><th>UPC<th>Description<th>$ Sales<th>Quantity";
while($salesBatchW = $dbc->fetch_array($salesBatchR)){
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

   echo "<tr bgcolor=$rColor><td width=120>$upc</td><td width=300>$desc</td><td width=50>$sales</td><td width=50 align=right>$qty</td><td>{$salesBatchW['total']}</td></tr>";
   $i++;
}
echo "</table>";

if (!isset($_GET['excel'])){
	include("../../src/footer.html");
}

?>
