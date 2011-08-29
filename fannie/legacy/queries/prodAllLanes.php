<?php
function allLanes($upc){
  global $FANNIE_ROOT;
  if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");

  include('../lanedefs.php');

  $queryItem = '';
  if(is_numeric($upc)){
    $upc = str_pad($upc,13,0,STR_PAD_LEFT);
    $queryItem = "SELECT * FROM products WHERE upc = '$upc'";
  }else{
    $queryItem = "SELECT * FROM products WHERE description LIKE '%$upc%' ORDER BY description";
  }

  for ($i = 0; $i < count($lanes); $i++){
    $currentLane = $lanes[$i];
    $sql = new SQLManager($currentLane,$types[$i],$dbs[$i],$users[$i],$pws[$i]);
	//continue;
    $resultItem = $sql->query($queryItem);
    $num = $sql->num_rows($resultItem);

    if ($num == 0){
      echo "Item <font color='red'>$upc</font> not found on Lane ".($i+1)."<br />";
    }
    else if ($num > 1){
      echo "Item <font color='red'>$upc</font> found multiple times on Lane ".($i+1)."<br />";
      while ($rowItem = $sql->fetch_array($resultItem)){
	echo "{$rowItem['upc']} {$rowItem['description']}<br />";
      }
    }
    else {
      $rowItem = $sql->fetch_array($resultItem);
      echo "Item <font color='red'>$upc</font> on Lane ".($i+1)."<br />";
      echo "Price: {$rowItem['normal_price']}";
      if ($rowItem['special_price'] <> 0){
	echo "&nbsp;&nbsp;&nbsp;&nbsp;<font color=green>ON SALE: {$rowItem['special_price']}</font>";
      }
      echo "<br />";
    }
    if ($i < count($lanes) - 1){
      echo "<hr />";
    }
  }
}
?>
