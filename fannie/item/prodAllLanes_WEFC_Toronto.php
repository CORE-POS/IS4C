<?php
/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    *  4Apr2013 Eric Lee No change for WEFC_Toronto. CORE does this differently now.
*/
function allLanes($upc){
  global $FANNIE_LANES, $FANNIE_ROOT;
  if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
  $ret = "";

  $queryItem = '';
  if(is_numeric($upc)){
    $upc = str_pad($upc,13,0,STR_PAD_LEFT);
    $queryItem = "SELECT * FROM products WHERE upc = '$upc'";
  }else{
    $queryItem = "SELECT * FROM products WHERE description LIKE '%$upc%' ORDER BY description";
  }

  for($i=0;$i<count($FANNIE_LANES);$i++){
    $f = $FANNIE_LANES[$i];
    $sql = new SQLManager($f['host'],$f['type'],$f['op'],$f['user'],$f['pw']);
    if ($sql === False){
    $ret .= "Can't connect to lane: ".($i+1)."<br />";
    continue;
    }
    $resultItem = $sql->query($queryItem);
    $num = $sql->num_rows($resultItem);

    if ($num == 0){
      $ret .= "Item <span style=\"color:red;\">$upc</span> not found on Lane ".($i+1)."<br />";
    }
    else if ($num > 1){
      $ret .= "Item <span style=\"color:red;\">$upc</span> found multiple times on Lane ".($i+1)."<br />";
      while ($rowItem = $sql->fetch_array($resultItem)){
    $ret .= "{$rowItem['upc']} {$rowItem['description']}<br />";
      }
    }
    else {
      $rowItem = $sql->fetch_array($resultItem);
      $ret .= "Item <span style=\"color:red;\">$upc</span> on Lane ".($i+1)."<br />";
      $ret .= "Price: {$rowItem['normal_price']}";
      if ($rowItem['special_price'] <> 0){
    $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;<span style=\"color:green;\">ON SALE: {$rowItem['special_price']}</span>";
      }
      $ret .= "<br />";
    }
    if ($i < count($FANNIE_LANES) - 1){
      $ret .= "<hr />";
    }
  }
  return $ret;
}
?>
