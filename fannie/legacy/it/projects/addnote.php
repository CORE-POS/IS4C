<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

if(isset($_POST['name'])){
    
  $name = $_POST['name'];
  $projID = $_POST['projID'];
  $mail = $_POST['mail'];
  $notes = preg_replace('/\n/','<br />',$_POST['notes']);
  $date = date("y/m/d : H:i:s",time());

  $q $sql->prepare("insert into project_notes values(?, ?, ?, ?)");
  $r = $sql->execute($q, array($projID, $name, $notes, $date));

  // get the project description
  $nameQ = $sql->prepare("select projDesc from projects where projID=?");
  $nameR = $sql->execute($nameQ, array($projID));
  $row = $sql->fetchRow($nameR);
  $projDesc = $row['projDesc'];

  $checkQ = $sql->prepare("select * from projects left outer join project_parties
             on projects.ITName = project_parties.email
             where LCASE(projects.ITName) = LCASE(?) or
             LCASE(project_parties.email) = LCASE(?)
             limit 1");
  $checkR = $sql->execute($checkQ, array($name, $name));
  // check #2 - see if this person is already 'in on' this project
  $check2Q = $sql->prepare("select * from project_parties where email=? and projID=?");
  $check2R = $sql->execute($check2Q, array($name, $projID));
  // not an IT person so add to interested parties
  if ($sql->num_rows($checkR) == 0 && $sql->num_rows($check2R) == 0){
    $partyQ = $sql->prepare("insert into project_parties values (?,?)");
    $partyR = $sql->execute($partyQ, array($projID, $name));
  }

  // build email 'to' all interested parties
  $q = $sql->prepare("select email from project_parties where projID = ?");
  $r = $sql->execute($q, array($projID));
  $to_string = 'it@wholefoods.coop';
  if ($mail == 'all' && $sql->num_rows($r) > 0){
    while($row = $sql->fetchRow($r)){
      $to_string .= ", ".$row[0]."@wholefoods.coop";
    }
  }

  // mail notification
  $to = "it@wholefoods.coop";
  $subject = "New note: $projDesc";
  $message = wordwrap("A new note has been posted to $projDesc at http://key/it/projects/project.php?projID=$projID", 70);
  $message.="\n\n".wordwrap($_POST['notes'], 70);
  $headers = "From: automail@wholefoods.coop";
  mail($to_string,$subject,$message,$headers);

  header("Location: project.php?projID=$projID");
}
else {
  $projID = $_GET['projID'];

  require($FANNIE_ROOT.'auth/login.php');
  $user = validateUserQuiet('projects');

  ?>
  <form method=post action=<?php echo $_SERVER['PHP_SELF'] ?> >
  <input type=hidden name=projID value=<?php echo $projID ?> />
  <?php
  if ($user){
  ?>
    <input type=hidden name=name value=<?php echo $user ?> />
    Posting as <?php echo $user ?><br />
  <?php
  }
  else {
  ?>
     Who are you? <br />
     <input type=text name=name /><br />
  <?php
  }
  ?>
  <textarea name=notes rows=20 cols=50></textarea><br />
  <input type=submit value=Post /> 
  <select name=mail>
  <option value=it>Just IT</option>
  <option value=all>Everyone</option>
  </select>
  </form>
<?php

}

