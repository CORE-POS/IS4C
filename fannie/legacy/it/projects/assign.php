<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once(__DIR__ . "/../../../src/SQLManager.php");
include(__DIR__ . '/../../../src/Credentials/projects.wfc.php');

require(__DIR__ . '/../../../auth/login.php');
if (!validateUser('admin')){
  return;
}

$projID = $_POST['projID'];
$ITName = $_POST['assign'];

$q = $sql->prepare("update projects set ITName=?,status=1 where projID = ?");
$r = $sql->execute($q, array($ITName, $projID));

header("Location: index.php");

