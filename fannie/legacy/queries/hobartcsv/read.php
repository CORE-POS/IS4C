<?php

/*
this script reads a csv file from the scale (items.csv)
and updates the items in the scaleItems table to match
*/

if (!class_exists("SQLManager")) require_once("../../sql/SQLManager.php");
include('../../db.php');

$fp = fopen('items.csv','r');
$err = fopen('error.log','w');

$targets = array('PLU Number' => 0,
         'Price' => 0,
         'Item Description' => 0,
         'Item Type' => 0,
         'By Count' => 0,
         'Tare 01' => 0,
         'Shelf Life' => 0);

$line = fgets($fp); // first line - definitions

// regexing here is to handle quoted fields containing
// commas (i.e., ingrdient lists).  The preg_split
// mechanism produces some weirdness due to the fact that
// I'm capturing the whole entry and [nearly] doubles 
// the results, but it should still be accurate 
$expr = "/([^,\\\"]*?|\\\".*?\\\"),/";
//echo $expr."<br />";
$data = preg_split($expr,$line,-1,PREG_SPLIT_DELIM_CAPTURE);

for($i = 0; $i < count($data); $i++){
  if (array_key_exists($data[$i],$targets))
    $targets[$data[$i]] = $i;
}

// parse additional lines
echo "<table border=1>";
while($line = fgets($fp)){
  $data = preg_split($expr,$line,-1,PREG_SPLIT_DELIM_CAPTURE);
  echo "<tr>";
  $current_plu = '';
  foreach ($targets as $i){
    if ($i == $targets['PLU Number']){
    // make the plu 4 digit
    $temp = str_pad($data[$i],3,"0",STR_PAD_LEFT);
    $temp = str_pad($temp,4,"0",STR_PAD_RIGHT);
    // make the plu into a upc
    $temp = str_pad("002".$temp,13,"0",STR_PAD_RIGHT);
    $current_plu = $temp;
    echo "<td><a href=\"../productTestScale.php?upc=$temp\">$temp</a></td>";
    $checkQ = "select upc from products where upc='$temp'";
    $checkR = $sql->query($checkQ);
    if ($sql->num_rows($checkR) == 0){
          fputs($err,"UPC $temp not present in Products\n\n");      
      break;
    }
    $checkQ = "select plu from scaleItems where plu='$temp'";
    $checkR = $sql->query($checkQ);
    if ($sql->num_rows($checkR) == 0){
      $addQ = "insert into scaleItems (plu,exceptionprice,class) values ('$temp',0,NULL)";
      $addR = $sql->query($addQ);
    }
    }
    else {
      if ($i == $targets['Price']){
    $upQ = "update scaleItems set price=$data[$i] where plu='$current_plu'";
    $upR = $sql->query($upQ);
      }
      else if ($i == $targets['Item Description']){
    $temp = preg_replace("/<.*?>/","",$data[$i]); // trim out html
    $temp = preg_replace("/\'/","",$temp); // trim out apostrophes
    $upQ = "update scaleItems set itemdesc='$temp' where plu='$current_plu'";
    $upR = $sql->query($upQ);
      }
      else if ($i == $targets['Item Type']){
    $temp = 1;
    if ($data[$i] == 'Random Weight')
      $temp = 0;
    $upQ = "update scaleItems set weight=$temp where plu='$current_plu'";
    $upR = $sql->query($upQ);
      }
      else if ($i == $targets['By Count']){
    $upQ = "update scaleItems set bycount='$data[$i]' where plu='$current_plu'";
    $upR = $sql->query($upQ);
      }
      else if ($i == $targets['Tare 01']){
    $upQ = "update scaleItems set tare=$data[$i] where plu='$current_plu'";
    $upR = $sql->query($upQ);
      }
      else if ($i == $targets['Shelf Life']){
    $upQ = "update scaleItems set shelflife=$data[$i] where plu='$current_plu'";
    $upR = $sql->query($upQ);
      }
      echo "<td>$data[$i]</td>";   
    }
  }
  echo "</tr>";
}

