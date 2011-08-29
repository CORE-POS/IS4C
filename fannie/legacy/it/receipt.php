<?php

include('../../config.php');

$MAX = 66;
$SIZE = 36;

include($FANNIE_ROOT.'src/SQLManager.php');
include('../db.php');

if (isset($_REQUEST['submit'])){
  $hcount = $_REQUEST['headercount'];
  $hs = array();
  $j = 0;
  for ($i = 0; $i < $hcount; $i++){
    if (!isset($_REQUEST["delheader$i"]))
      $hs[$j++] = $_REQUEST["header$i"]; 
  }
  if (!empty($_REQUEST['newheader']))
    $hs[$j] = $_REQUEST['newheader'];

  $fcount = $_REQUEST['footercount'];
  $fs = array();
  $j = 0;
  for ($i = 0; $i < $fcount; $i++){
    if (!isset($_REQUEST["delfooter$i"]))
      $fs[$j++] = $_REQUEST["footer$i"]; 
  }
  if (!empty($_REQUEST['newfooter']))
    $fs[$j] = $_REQUEST['newfooter'];

  $clearQ = "truncate table receipt";
  $clearR = $sql->query($clearQ);

  for ($i = 0; $i < count($hs); $i++){
    $q = "insert into receipt values ('$hs[$i]',$i,'header')";
    $r = $sql->query($q);
  }

  for ($i = 0; $i < count($fs); $i++){
    $q = "insert into receipt values ('$fs[$i]',$i,'footer')";
    $r = $sql->query($q);
  }

  foreach ($FANNIE_LANES as $lane){
    $sql->add_connection($lane['host'],$lane['type'],$lane['op'],$lane['user'],$lane['pw']);
    $ins = "INSERT INTO customReceipt";
    $sel = "SELECT * FROM receipt";
    $sql->query("TRUNCATE TABLE customReceipt",$lane['op']);
    $sql->transfer($FANNIE_OP_DB,$sel,$lane['op'],$ins);
  }
}

$headerQ = "select text from receipt where type='header' order by seq";
$headerR = $sql->query($headerQ);
$headers = array();
$headercount = 0;
while($row = $sql->fetch_array($headerR)){
  $headers[$headercount++] = $row[0];
}

$footerQ = "select text from receipt where type='footer' order by seq";
$footerR = $sql->query($footerQ);
$footers = array();
$footercount = 0;
while($row = $sql->fetch_array($footerR)){
  $footers[$footercount++] = $row[0];
}

echo "<form method=post action={$_SERVER['PHP_SELF']}>";
echo "<b>Current Headers</b>";
echo "<table cellspacing=2 cellpadding=2>";
echo "<tr><th>Text</th><th>Delete</th></tr>";
for ($i = 0; $i < $headercount; $i++){
  echo "<tr>";
  echo "<td><input type=text maxlength=$MAX size=$SIZE name=header$i value=\"{$headers[$i]}\" /></td>";
  echo "<td><input type=checkbox name=delheader$i /></td>";
  echo "</tr>";
}
echo "<input type=hidden name=headercount value=$headercount />";
echo "<tr><td><input type=text maxlength=$MAX size=$SIZE name=newheader /></td>";
echo "<td>NEW</td></tr>";
echo "</table><p />";

echo "<b>Current Footers</b>";
echo "<table cellspacing=2 cellpadding=2>";
echo "<tr><th>Text</th><th>Delete</th></tr>";
for ($i = 0; $i < $footercount; $i++){
  echo "<tr>";
  echo "<td><input type=text maxlength=$MAX size=$SIZE name=footer$i value=\"{$footers[$i]}\" /></td>";
  echo "<td><input type=checkbox name=delfooter$i /></td>";
  echo "</tr>";
}
echo "<input type=hidden value=$footercount name=footercount />";
echo "<tr><td><input type=text maxlength=$MAX size=$SIZE name=newfooter /></td>";
echo "<td>NEW</td></tr>";
echo "</table><p />";
echo "<input type=submit name=submit value=Update />";

?>


