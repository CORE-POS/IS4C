<?php

include ('hobartcsv/parse.php');

include('../db.php');

$q = "select * from scaleItems ";
/*
WHERE plu in ('0023611000000','0023816000000','0023958000000','0023202000000','0023201000000','0023007000000','0025135000000','0026511000000','0027005000000','0024401000000','0023212000000','0025121000000','0025111000000','0025109000000','0025114000000','0025112000000','0026670000000','0026720000000','0023031000000','0023037000000','0026523000000','0026470000000','0026522000000','0026522000000','0024310000000','0026690000000','0026750000000','0023326000000','0023812000000','0026030000000','0023033000000','0026817000000','0026580000000','0023431000000','0023433000000','0026110000000','0023505000000','0026970000000','0023511000000','0023513000000','0023512000000','0023516000000','0024213000000','0024206000000','0024205000000','0024215000000','0024204000000','0024216000000','0024201000000','0024212000000','0024203000000','0024207000000','0026220000000','0024202000000','0024208000000','0024211000000','0024214000000','0024209000000','0024681000000','0023715000000','0021438000000','0026270000000','0023711000000','0025113000000','0025108000000','0026740000000','0026700000000','0026710000000','0023504000000','0025118000000','0026660000000','0024007000000','0024003000000','0024005000000','0026560000000','0023815000000','0026210000000','0023802000000','0023801000000','0026230000000','0023001000000','0026621000000','0024006000000','0026490000000','0026830000000','0024311000000','0026230000000','0023001000000','0023804000000','0026140000000','0023041000000','0024404000000','0024403000000','0026250000000','0026020000000','0024408000000','0023807000000','0024406000000','0026650000000','0026520000000','0023214000000')";
*/
$r = $sql->query($q);
$row = $sql->fetchRow($r);

$plu = array();
$price = array();
$itemdesc = array();
$exceptionprice = array();
$weight = array();
$bycount = array();
$tare = array();
$shelflife = array();
$text = array();
$graphics = array();
$label = array();

$count = 0;
while ($row = $sql->fetchRow($r)){
  if ($row[1] == 0)
    continue;
  $plu[$count] = substr($row[0],3,4);
  $price[$count] = rtrim($row[1],' ');
  $itemdesc[$count] = $row[2];
  $exceptionprice[$count] = rtrim($row[3],' ');
  if ($row[4] == 0){
    $weight[$count] = "Random Weight";
  }
  else {
    $weight[$count] = "Fixed Weight";
  }
  $bycount[$count] = $row[5];
  $tare[$count] = rtrim($row[6],' ');
  $shelflife[$count] = $row[7];
  $text[$count] = $row[8];
  $label[$count] = $row[10];
  if ($row[11] == 0)
    $graphics[$count] = false;
  else
    $graphics[$count] = $row[11];
  $count++;
}

echo "Items sent to the scale: <br />";
echo "<table cellspacing=2 cellpadding=2 border=1>";
echo "<tr>";
echo "<td>upc</td><td>price</td><td>description</td><td>exception price</td><td>weight</td><td>by count</td><td>tare</td><td>shelf life</td><td>text</td>";
echo "</tr>";
for ($i = 0; $i < count($plu); $i++){
  echo "<tr>";
  echo "<td>$plu[$i]</td>";
  echo "<td>$price[$i]</td>";
  echo "<td>$itemdesc[$i]</td>";
  echo "<td>$exceptionprice[$i]</td>";
  echo "<td>$weight[$i]</td>";
  echo "<td>$bycount[$i]</td>";
  echo "<td>$tare[$i]</td>";
  echo "<td>$shelflife[$i]</td>";
  echo "<td>$text[$i]</td>";
  echo "</tr>";
  //parseitem('ChangeOneItem',$plu[$i],$itemdesc[$i],$tare[$i],$shelflife[$i],$price[$i],$bycount[$i],$weight[$i],$exceptionprice[$i],$text[$i],$label[$i],$graphics[$i]);
}
echo "</table>";

if (isset($_GET['asnew'])){
  parseitem('WriteOneItem',$plu,$itemdesc,$tare,$shelflife,$price,$bycount,$weight,$exceptionprice,$text,$label,$graphics);
}
else {
  parseitem('ChangeOneItem',$plu,$itemdesc,$tare,$shelflife,$price,$bycount,$weight,$exceptionprice,$text,$label,$graphics);
}

