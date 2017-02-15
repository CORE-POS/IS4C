<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

require($FANNIE_ROOT.'auth/login.php');
if (!validateUser('admin')){
  return;
}

$projID = $_GET['projID'];

$date = date("Y-m-d");

$q = $sql->prepare("update projects set status=2,completeDate=? where projID=?");
$r = $sql->execute($q, array($date, $projID));

// build email 'to' all interested parties
$q = $sql->prepare("select email from project_parties where projID = ?");
$r = $sql->execute($q, array($projID));
$to_string = 'it@wholefoods.coop';
if ($sql->num_rows($r) > 0){
  while($row = $sql->fetchRow($r)){
    $to_string .= ", ".$row[0]."@wholefoods.coop";
  }
}


$descQ = $sql->prepare("select projDesc from projects where projID=?");
$descR = $sql->execute($descQ, array($projID));
$descW = $sql->fetchRow($descR);
$projDesc = $descW[0];

// mail notification
$subject = "Completed project: $projDesc";
$message = wordwrap("The project $projDesc has been marked completed.  http://key/it/projects/project.php?projID=$projID", 70);
$headers = "From: automail@wholefoods.coop";
mail($to_string,$subject,$message,$headers);

header("Location: index.php");

