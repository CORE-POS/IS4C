<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

extract($_GET);

switch ($on){
  case 'yes':
    $q = "insert into project_parties values ($projID,'$user')";
    $r = $sql->query($q);
    echo "<p /><a href='' onclick=\"watchToggle('no',$projID,'$user'); return false;\">Stop watching this project</a><br />";
    break;
  case 'no':
    $q = "delete from project_parties where projID = $projID and email = '$user'";
    $r = $sql->query($q);
    echo "<p /><a href='' onclick=\"watchToggle('yes',$projID,'$user'); return false;\">Watch this project</a><br />";
    break;
}

?>
