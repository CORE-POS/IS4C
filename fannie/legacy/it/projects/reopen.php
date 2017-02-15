<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

require($FANNIE_ROOT.'auth/login.php');
if (!validateUser('admin')){
  return;
}

$projID = $_GET['projID'];

$q = $sql->prepare("update projects set status=1 where projID=?");
$r = $sql->execute($q, array($projID));

header("Location: index.php");

