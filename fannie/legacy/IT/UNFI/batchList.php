<?php
include('../../../config.php');

include($FANNIE_ROOT.'legacy/queries/funct1Mem.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET['delete'])){
	$batchID = $_GET['batchID'];
	$delQ1 = "delete from batchTest where batchID=$batchID";
	$delQ2 = "delete from batchListTest where batchID=$batchID";
	$delR1 = $sql->query($delQ1);
	$delR2 = $sql->query($delQ2);
}

$batchListQ= "SELECT b.batchID,b.batchName,b.startDate,b.endDate
          FROM batchTest as b 
          ORDER BY b.batchID DESC";

$batchListR = $sql->query($batchListQ);

?>
<html>
<head>
<link href="CalendarControl/CalendarControl.css"
      rel="stylesheet" type="text/css">
<script src="CalendarControl/CalendarControl.js"
        language="javascript"></script>
</head>
<body>
<link href="CalendarControl/CalendarControl.css"
      rel="stylesheet" type="text/css">
<script src="CalendarControl/CalendarControl.js"
        language="javascript"></script>

<?
echo "<table border=1 cellpadding=2 cellspacing=0>";
$i = 0;
echo "<th>Batch Name<th>Start Date";
while($batchListW = $sql->fetch_array($batchListR)){
   $start = $batchListW[2];
   $end = $batchListW[3];
   $imod = $i%2;
   if($imod==1){
      $bColor = '#ffffff';
   }else{
      $bColor = '#ffffcc';
   }
   echo "<tr bgcolor=$bColor><td><a href=display.php?batchID=$batchListW[0]>";
   echo "$batchListW[1]</a></td>";
   echo "<td>$batchListW[3]</td>";
   echo "<td><a href=batchList.php?delete=yes&batchID=$batchListW[0] onclick=\"return confirm('Delete batch $batchListW[1]');\">Delete</a></td>";
   $i++;
}
?>

