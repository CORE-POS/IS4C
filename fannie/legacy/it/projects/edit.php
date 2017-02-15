<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

require($FANNIE_ROOT.'auth/login.php');
if (!validateUser('projects')){
  return;
}

if(isset($_POST['projDesc'])){
  $projDesc = $_POST['projDesc'];
  $link = $_POST['link'];
  $priority = $_POST['priority'];
  $projID = $_POST['projID'];
  $emaillist = $_POST['emaillist'];
  $notes = preg_replace('/\n/','<br />',$_POST['notes']);
  $q = $sql->prepare("update projects set
        projDesc = ?
        link = ?
        priority = ?
        notes = ?
        where projID=?");
  $r = $sql->execute($q, array($projDesc, $link, $priority, $notes, $projID));
  
  $mails = explode(",",$emaillist);
  $q = $sql->prepare("delete from project_parties where projID=?");
  $r = $sql->execute($q, array($projID));
  
  $q = $sql->prepare("insert into project_parties values (?,?)");
  foreach ($mails as $m){
      $m = trim($m);
      if ($m != ''){
        $r = $sql->execute($q, array($projID, $m));
      }    
  }
      
  header("Location: project.php?projID=$projID");
}
else {
  $projID = $_GET['projID'];

  $q = $sql->prepare("select projDesc, notes, link, priority from projects where projID=?");
  $r = $sql->execute($q, array($projID));

  $row = $sql->fetchRow($r);
  $olddesc = $row['projDesc'];
  $oldnotes = preg_replace('/<br \/>/',"\n",$row['notes']); 
  $oldlink = $row['link'];
  $oldpriority = $row['priority'];
  
  $emailQ = $sql->prepare("select email from project_parties where projID=? order by email");
  $emailR = $sql->execute($emailQ, array($projID));
  $emaillist = "";
  while ($emailW = $sql->fetchRow($emailR))
    $emaillist .= $emailW[0].", ";
  $emaillist = substr($emaillist,0,strlen($emaillist)-2);
?>

  <form method=post action=<?php echo $_SERVER['PHP_SELF'] ?> >
  <input type=hidden name=projID value=<?php echo $projID ?> />
  Project description:<br />
  <input type=text name=projDesc value="<?php echo $olddesc ?>" /><br />
  Link:<br />
  <input type=text name=link value="<?php echo $oldlink ?>" />&nbsp;&nbsp;
  Priority: <select name=priority>
  <?php
  for ($i = 1; $i <= 10; $i++){
    if ($i == $oldpriority)
      echo "<option selected>$i</option>";
    else
      echo "<option>$i</option>";
  }
  ?>
  </select><br />
  Email list:<br />
  <input type=text name=emaillist value="<?php echo $emaillist ?>" size=51 /><br />
  Notes:<br />
  <textarea name=notes rows=20 cols=50>
<?php echo $oldnotes ?>
  </textarea><br />
  <input type=submit value=Edit />
  </form>
<?php
}

