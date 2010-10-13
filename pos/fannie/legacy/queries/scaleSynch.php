<?php
include('../../config.php');

include ('hobartcsv/parse.php');

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../db.php');

$q = "select * from scaleItems ";
$r = $sql->query($q);
$row = $sql->fetch_array($r);

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
while ($row = $sql->fetch_array($r)){
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

?>
