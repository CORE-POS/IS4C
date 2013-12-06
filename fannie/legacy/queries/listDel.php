<?php

include('../../config.php');
include($FANNIE_ROOT.'auth/login.php');
$uid = 1005;
$user = validateUserQuiet('delete_items');
if (!$user){
  header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/queries/listDel.php?upc={$_GET['upc']}&description={$_GET['description']}");
  return;
}
$uid = getUID($user);
?>
<html>
<body bgcolor=#dd0000 text=#ccccff>
<?php
include('../db.php');
if (isset($_POST['Yes'])){
  $upc = $_POST['upc'];
  $gatherQ = '';
  $args = array();
  if (isset($_POST['description'])){
    $desc = base64_decode($_POST['description']);
    $sql->prepare($gatherQ = "select upc,description,normal_price,department,tax,foodstamp,scale,modified,qttyenforced,discount,inuse from products where upc=? and description=?");
    $args = array($upc, $desc);
  }
  else {
    $gatherQ = $sql->prepare("select upc,description,normal_price,department,tax,foodstamp,scale,modified,qttyenforced,discount,inuse from products where upc=?");
    $args = array($upc);
  }
  $gatherR = $sql->execute($gatherQ, $args);
  $gatherRow = $sql->fetch_row($gatherR);

  $query = '';
  $args = array();
  if (isset($_POST['description'])){
    $desc = base64_decode($_POST['description']);
    $query = $sql->prepare("delete from products where upc = ? and description=?");
    $args = array($upc, $desc);
    //echo $query;
    //return;
  }
  else {
    $query = $sql->prepare("delete from products where upc = ?");
    $args = array($upc);
  }
  //echo $query;
  $result = $sql->execute($query, $args);

  $extraQ = $sql->prepare("delete from prodExtra where upc=?");
  $extraR = $sql->execute($extraQ, array($upc));

  if (isset($_POST["scale_delete"]) && $_POST["scale_delete"] == "on"){
	$plu = substr($upc,3,4);
	include("hobartcsv/parse.php");
	deleteitem($plu);
  }

  echo "<script language=JavaScript>";
  // reloading opener works in Firefox, not in Safari (loses POST data)
  //echo "opener.location.reload();";
  echo "close();";
  echo "</script>";
 
}
else if (isset($_POST['No'])){
  echo "<script language=JavaScript>";
  echo "close();";
  echo "</script>";
}
else {
  $upc = $_GET['upc'];
  echo "Are you sure you want to delete item $upc";
  
  if (isset($_GET['description'])){
    $d = base64_decode($_GET['description']);
    echo " ($d)";
    $q = $sql->prepare("select normal_price,special_price,
          case when tax = 1 then 'Reg' else case when tax = 2 then 'Deli' else 'NoTax' end end as t,
          case when foodstamp = 1 then 'Yes' else 'No' end as fs,
          case when scale = 1 then 'Yes' else 'No' end as s
          from products where upc=? and description=?");
    $r = $sql->execute($q, array($upc, $d));
    $row = $sql->fetch_row($r);
    echo "<table cellspacing=2 cellpadding=2>";
    echo "<tr><th>Normal price</th><th>Special price</th><th>Tax</th><th>Foodstamp</th><th>Scale</th></tr>";
    echo "<tr><td>$row[0]</td><td>$row[1]</td><td>$row[2]</td><td>$row[3]</td><td>$row[4]</td></tr></table>";
  }

  echo "<p />";

  echo "<form action=listDel.php method=post>";
  echo "<input type=submit name=Yes value=Yes> ";
  echo "<input type=submit name=No value=No>";
  echo "<input type=hidden name=upc value=$upc>";
  if (isset($_GET['description'])){
    $desc = $_GET['description'];
    echo "<input type=hidden name=description value=$desc>";
  }
  if (substr($upc,0,3) == "002"){
	echo "<input type=checkbox name=scale_delete />";
	echo "Delete from the scales, too";
  }

  echo "</form>";
}
?>

</body>
</html>
