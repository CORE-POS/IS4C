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

  $q = "insert into project_notes values(
        $projID,'$name','$notes','$date')";
  $r = $sql->query($q);

  // get the project description
  $nameQ = "select projDesc from projects where projID=$projID";
  $nameR = $sql->query($nameQ);
  $row = $sql->fetch_array($nameR);
  $projDesc = $row['projDesc'];

  $checkQ = "select * from projects left outer join project_parties
             on projects.ITName = project_parties.email
             where LCASE(projects.ITName) = LCASE('$name') or
             LCASE(project_parties.email) = LCASE('$name')
             limit 1";
  $checkR = $sql->query($checkQ);
  // check #2 - see if this person is already 'in on' this project
  $check2Q = "select * from project_parties where email='$name' and projID=$projID";
  $check2R = $sql->query($check2Q);
  // not an IT person so add to interested parties
  if ($sql->num_rows($checkR) == 0 && $sql->num_rows($check2R) == 0){
    $partyQ = "insert into project_parties values ($projID,'$name')";
    $partyR = $sql->query($partyQ);
  }

  // build email 'to' all interested parties
  $q = "select email from project_parties where projID = $projID";
  $r = $sql->query($q);
  $to_string = 'it@wholefoods.coop';
  if ($mail == 'all' && $sql->num_rows($r) > 0){
    while($row = $sql->fetch_array($r)){
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

?>
