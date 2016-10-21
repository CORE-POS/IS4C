<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

require($FANNIE_ROOT.'auth/login.php');
$user = validateUser('projects');
if (!$user){
  return;
}

if (isset($_POST['projDesc'])){
  $projDesc = $_POST['projDesc'];
  $link = $_POST['link'];
  $priority = $_POST['priority'];
  $notes = preg_replace('/\n/','<br />',$_POST['notes']);
  $status = 0;
  
  $q = "select max(projID) from projects";
  $r = $sql->query($q);
  $row = $sql->fetchRow($r);
  $projID = $row[0] + 1;
  $date = date("Y-m-d");
  if(isset($_POST['party'])){
     $insPartyQ = $sql->prepare("INSERT INTO project_parties VALUES(?,?)");
     foreach($_POST['party'] as $key=>$value){
        $insPartyR = $sql->execute($insPartyQ, array($projID, $value));
     }
  }
  $q = $sql->prepare("insert into projects (projID,projDesc,reqestDate,status,notes,link,priority) 
        values (?,?,?,?,?,?,?)");
  $r = $sql->execute($q, array($projID, $projDesc, $date, $status, $notes, $link, $priority));
  
  $checkQ = $sql->prepare("select * from projects where LCASE(ITName) = LCASE(?) limit 1");
  $checkR = $sql->execute($checkQ, array($user));
  // not an IT person so add to interested parties
  if ($sql->num_rows($checkR) == 0){
    $partyQ = $sql->prepare("insert into project_parties values (?,?)");
    $partyR = $sql->execute($partyQ, array($projID, $user));
  }
  
  // build email 'to' all interested parties
  $q = $sql->prepare("select email from project_parties where projID = ?");
  $r = $sql->execute($q, array($projID));
  $to_string = 'it@wholefoods.coop';
  if ($sql->num_rows($r) > 0){
    while($row = $sql->fetchRow($r)){
      $to_string .= ", ".$row[0]."@wholefoods.coop";
    }
  }

  // mail notification
  $subject = "New project: $projDesc";
  $message = wordwrap("A new project has been posted at http://key/it/projects/project.php?projID=$projID", 70);
  $headers = "From: automail@wholefoods.coop";
  mail($to_string,$subject,$message,$headers);

  header("Location: index.php");
}
else {
?>
<html><head><title>New project</title></head><body>
<?php
  echo "<form method=post action=newproject.php>";
  echo "Project name:<br /><input type=text name=projDesc><br />";
  echo "Link: <br /><input type=text name=link value=http:// />&nbsp;&nbsp;";
  echo "Priority: <select name=priority>";
  for ($i = 1; $i<=10; $i++)
     echo "<option>$i</option>";
  echo "</select><br />";
  echo "Description: <br />";
  echo "<textarea name=notes rows=10 cols=35></textarea><br />";
  echo "<table><tr><td>Check interested parties:</td>";
  echo "<td><input type=checkbox name=party[] value='sharon'>Sharon</td><td><input type=checkbox name=party[] value=MTM>MTM</td></tr>";
  echo "<tr><td>&nbsp;</td><td><input type=checkbox name=party[] value='briana'>Briana</td><td><input type=checkbox name=party[] value=michaelo>Michael O</td></tr>";
  echo "<tr><td>&nbsp;</td><td><input type=checkbox name=party[] value='michael'>Michael</td><td><input type=checkbox name=party[] value=justin>Justin</td></tr>";
  echo "<tr><td>&nbsp;</td><td><input type=checkbox name=party[] value='lisa'>Lisa</td><td><input type=checkbox name=party[] value=raelynn>Rae Lynn</td></tr>";
  echo "<tr><td>&nbsp;</td><td><input type=checkbox name=party[] value='xina'>Xina</td><td><input type=checkbox name=party[] value=Finance>Finance</td></tr>";
  echo "<tr><td>&nbsp;</td><td><input type=checkbox name=party[] value='colleen'>Colleen</td><td><input type=checkbox name=party[] value=fe>Front End</td></tr>";
  echo "<tr><td>&nbsp;</td><td><input type=checkbox name=party[] value='shannon'>Shannon</td><td><input type=checkbox name=party[] value=Buyers>Buyers</td></tr>";
  echo "<input type=submit value=Submit>";
  echo "</form>";
}

?>

</body>
</html>
