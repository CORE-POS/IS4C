<?php
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}

/* like read, but for expanded text */

$fp = fopen('expandedtext.csv','r');

$targets = array('Expanded Text Number' => 0,
         'Expanded Text' => 0);

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
    if ($i == $targets['Expanded Text Number']){
    // make the plu 4 digit
    $temp = str_pad($data[$i],3,"0",STR_PAD_LEFT);
    $temp = str_pad($temp,4,"0",STR_PAD_RIGHT);
    // make the plu into a upc
    $temp = str_pad("002".$temp,13,"0",STR_PAD_RIGHT);
    $current_plu = $temp;
    echo "<td><a href=\"../productTestScale.php?upc=$temp\">$temp</a></td>";
    }
    else {
      $temp = ltrim($data[$i],"\""); // lose quote
      $temp = rtrim($temp,"\""); // lose quote
      $temp = preg_replace("/\\r?<br.*?>/","\n",$temp); // breaks to newlines
      $temp = preg_replace("/\'/","",$temp); // lose apostrophes
      $temp = preg_replace("/<.*?>/","",$temp); // lose html tags
      $upQ = $sql->prepare_statement("update scaleItems set text=? where plu=?");   
      $upR = $sql->exec_statement($upQ,array($temp,$current_plu));
      echo "<td>$temp</td>";   
    }
  }
  echo "</tr>";
}


?>
