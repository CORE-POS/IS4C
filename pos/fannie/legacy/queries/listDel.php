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
if (!class_exists("SQLManager")) require_once($FANNIE_URL."sql/SQLManager.php");
include('../db.php');
if (isset($_POST['Yes'])){
  $upc = $_POST['upc'];
  $gatherQ = '';
  if (isset($_POST['description'])){
    $desc = base64_decode($_POST['description']);
    $gatherQ = "select upc,description,normal_price,department,tax,foodstamp,scale,modified,qttyenforced,discount,inuse from products where upc='$upc' and description='$desc'";
  }
  else {
    $gatherQ = "select upc,description,normal_price,department,tax,foodstamp,scale,modified,qttyenforced,discount,inuse from products where upc='$upc'";
  }
  $gatherR = $sql->query($gatherQ);
  $gatherRow = $sql->fetch_row($gatherR);

  // log the deletion in prodUpdate.  I'm not bothering to pull the like code
  // from upcLike since it's an extra query
  // 1005 == modified by, since this isn't set up yet
  $prodUpQ = "insert into prodUpdate values (
              '$gatherRow[0]',
              '$gatherRow[1]',
               $gatherRow[2],
               $gatherRow[3], 
               $gatherRow[4], 
               $gatherRow[5], 
               $gatherRow[6],
               0,
               getdate(),
               $uid,
               $gatherRow[8],
               $gatherRow[9],
               $gatherRow[10],
               2
               )";
  $prodUpR = $sql->query($prodUpQ);

  $query = '';
  if (isset($_POST['description'])){
    $desc = base64_decode($_POST['description']);
    $query = "delete from products where upc = '$upc' and description='$desc'";
    //echo $query;
    //return;
  }
  else {
    $query = "delete from products where upc = '$upc'";
  }
  //echo $query;
  $result = $sql->query($query);

  $extraQ = "delete from prodExtra where upc='$upc'";
  $extraR = $sql->query($extraQ);

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
    $q = "select normal_price,special_price,
          case when tax = 1 then 'Reg' else case when tax = 2 then 'Deli' else 'NoTax' end end as t,
          case when foodstamp = 1 then 'Yes' else 'No' end as fs,
          case when scale = 1 then 'Yes' else 'No' end as s
          from products where upc='$upc' and description='$d'";
    $r = $sql->query($q);
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
