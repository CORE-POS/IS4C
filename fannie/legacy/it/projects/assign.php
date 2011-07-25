<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

require($FANNIE_ROOT.'auth/login.php');
if (!validateUser('admin')){
  return;
}

$projID = $_POST['projID'];
$ITName = $_POST['assign'];

$q = "update projects set ITName='$ITName',status=1 where projID = $projID";
$r = $sql->query($q);

header("Location: index.php");

?>
