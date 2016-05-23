<?php
include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

/*
  Print out all the projects for the selected name
  Make the whole thing a form
  Each row has a project ID name proj[number], and two
  checkboxes: include[number] and details[number]
*/
if (isset($_POST['name']) && !isset($_POST['proj0'])){
  $name = $_POST['name'];
  $name = $_POST['name'];
  $date = $_POST['date'];

  $q = $sql->prepare("select projDesc,projID,status,priority,reqestDate,completeDate from projects where ITName = ?
        AND ((completeDate > ? AND status = 2) or status = 1) 
        order by status,priority,completeDate desc,priority");
  //echo $q;
  $r = $sql->execute($q, array($name, $date));
  echo "<form action={$_SERVER['PHP_SELF']} method=POST>";
  echo "<input type=hidden name=name value=$name />";
  $count = 0;
  echo "<h3>In progress</h3>";
  echo "<table cellspacing=2 cellpadding=2 border=1><tr><th>Description</th><th>Request Date</th>";
  echo "<th>Priority</th><th>Include</th><th>No details</th></tr>";  
  $complete_flag = false;
  while ($row = $sql->fetchRow($r)){
    if (!$complete_flag && $row['status'] == 2){
       echo "</table>";
       echo "<h3>Completed</h3>";
       echo "<table cellspacing=2 cellpadding=2 border=1><tr><th>Description</th><th>Request Date</th>";
       echo "<th>Complete Date</th><th>Priority</th><th>Include</th><th>No details</th></tr>";
       $complete_flag = true;
    }
    echo "<tr>";
    echo "<input type=hidden name=proj".$count." value={$row['projID']} />";
    echo "<td><a href=project.php?projID={$row['projID']}>{$row['projDesc']}</a></td>";
    echo "<td>{$row['reqestDate']}</td>";
    if ($complete_flag)
      echo "<td>{$row['completeDate']}</td>";
    echo "<td>{$row['priority']}</td>";
    echo "<td><input type=checkbox name=include".$count;//." checked /></td>";
    //if(!$complete_flag){
       echo " checked ";
    //}
    echo "/></td>";
    echo "<td><input type=checkbox name=details".$count;//." /></td>";
    if($complete_flag){
       echo " checked ";
    }
    echo "/></tr>";
    $count++;
  }
  echo "</table>";
  echo "<br />";
  echo "<input type=hidden name=date value='$date'>";
  echo "<input type=submit value=Generate />";
  echo "</form>";
}
/*
  read the post arguments into arrays
  print the project is its include is checked
  include the details if its details ISN'T checked
  (including details is the default)
*/
else if (isset($_POST['proj0'])){
  $projID = array();
  $include = array();
  $details = array();
  $count = 0;
  while (isset($_POST['proj'.$count])){
    $projID[$count] = $_POST['proj'.$count];
    $include[$count] = $_POST['include'.$count];
    $details[$count] = $_POST['details'.$count];
    $count++;
  }
  $name = $_POST['name'];
  $date = $_POST['date'];
  echo "<h3>Project report for $name</h3>";
  echo "<hr />";
  $q = $sql->prepare("select projDesc,reqestDate,status,priority,completeDate,notes from projects where projID = ? order by priority");
    $notesQ = $sql->prepare("select ITName, stamp, notes from project_notes where projID = ?
               AND stamp > ? order by stamp DESC");
  for ($i = 0; $i < count($projID); $i++){
    if ($include[$i]){
      $r = $sql->execute($q, array($projID[$i]));
      $row = $sql->fetchRow($r);
      echo "Project: <a href=project.php?projID=$projID[$i]>{$row['projDesc']}</a><br />";
      echo "Request date: {$row['reqestDate']} Priority: {$row['priority']}<br />";
      if ($row['status'] == 2)
          echo "Complete date: {$row['completeDate']}<br />";
      echo $row["notes"]."<br />";
      if (!$details[$i]){
        echo "Notes: <br />";
        //echo $notesQ;
        $notesR = $sql->execute($notesQ, array($projID[$i], $date));
        $num = $sql->num_rows($notesR);
        if ($num > 0){
          echo "<br /><u>Additional notes on this project:</u><br />";
          for ($j = 0; $j < $num; $j++){
            $row = $sql->fetchRow($notesR);
            echo "Posted by {$row['ITName']} on {$row['stamp']}";
            echo "<blockquote>{$row['notes']}</blockquote>";
          }
        }
      }
      echo "<hr />"; 
    }
  }
}
else {
  echo "<form action={$_SERVER['PHP_SELF']} method=post>\n";
  echo "<select name=name>\n";
  $q = "select distinct ITName from projects where ITName is not NULL";
  $r = $sql->query($q);
  while ($row = $sql->fetchRow($r)){
    echo "<option>".$row['ITName']."</option>\n";
  }  
  echo "</select>\n";
  echo "<br \>Start Date: <input type=text name=date value='2005-01-01'>";
  echo "<input type=submit value=submit>\n";
  echo "</form>";
}

