<?php
include('../../../config.php');

include($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('manage_likecodes')){
  header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/likecode_manager/index.php");
  return;
}

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

if (isset($_GET["excel"])){
	header('Content-Type: application/ms-excel');
	header('Content-Disposition: attachment; filename="likecodes.csv"');
	echo "LikeCode,Description\n";
	$fetchQ = "select likecode,likecodedesc from likecodes order by likecode";
	$fetchR = $sql->query($fetchQ);
	while($row = $sql->fetch_array($fetchR))
		echo $row[0].",\"".$row[1]."\"\n";
	return;
}

if (isset($_POST['type'])){
  $lc = $_POST['likecode'];
  if ($_POST['type'] == "edit"){
    $desc = $_POST['desc'];
    $q = "update likecodes set likecodedesc='$desc' where likecode=$lc";
    $sql->query($q);
  }
  else if ($_POST['type'] == "delete"){
    if ($_POST['submit'] == "Yes"){
      $q = "delete from likecodes where likecode=$lc";
      $sql->query($q);
      $q = "delete from upclike where likecode=$lc";
      $sql->query($q);
    }
  }
}

$q = "select likecode,likecodedesc from likecodes order by likecode";
$r = $sql->query($q);

echo "<a href=index.php?excel=yes>Save to Excel</a><br />";
echo "<table cellspacing=2 cellpadding=2 border=1>";
echo "<tr><td>Like Code</td><td>Description</td><td></td><td></td></tr>";
while ($row = $sql->fetch_array($r)){
  echo "<tr>";
  $lc = $row[0];
  $desc = $row[1];
  echo "<td>$lc</td>";
  echo "<td>$desc</td>";
  echo "<td><a href=edit.php?likecode=$lc>Edit</a></td>";
  echo "<td><a href=delete.php?likecode=$lc>Delete</a></td>";
  echo "<tr>";
}
echo "</table>";

?>
