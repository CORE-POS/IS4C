<html>
<head><title>Project details</title>
<script>
/* create XML request object (i.e., AJAX-ify) */
function createRequestObject() {
    var ro;
    var browser = navigator.appName;
    if(browser == "Microsoft Internet Explorer"){
        ro = new ActiveXObject("Microsoft.XMLHTTP");
    }else{
        ro = new XMLHttpRequest();
    }
    return ro;
}

/* global variables */
var http = createRequestObject();   // the AJAX request object

/* sends a request for the given action
   designed to call this page with arguments as HTTP GETs */
function sndReq(action) {
    http.open('get', action);
    http.onreadystatechange = handleResponse;
    http.send(null);
}

function handleResponse(){
    if(http.readyState == 4){
      var response = http.responseText;
      document.getElementById('watchfield').innerHTML = response;
    }
}

function watchToggle(yn,projID,user){
  sndReq('watch.php?on='+yn+'&projID='+projID+'&user='+user);
}

</script>
</head>
<body>

<?php

$projID = $_GET['projID'];

include('../../../config.php');
if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include($FANNIE_ROOT.'src/Credentials/projects.wfc.php');

$q = $sql->prepare("select projDesc, ITName, reqestDate, status, notes, link, priority from projects where projID = ?");
$r = $sql->execute($q, array($projID));

$row = $sql->fetchRow($r);

$emailQ = $sql->prepare("select email from project_parties where projID=? order by email");
$emailR = $sql->execute($emailQ, array($projID));
$emaillist = "";
while ($emailW = $sql->fetchRow($emailR))
    $emaillist .= $emailW[0].", ";
$emaillist = substr($emaillist,0,strlen($emaillist)-2);

require($FANNIE_ROOT.'auth/login.php');
$admin_user = validateUserQuiet('admin');
$proj_user = validateUserQuiet('projects');

switch($row['status']){
 case 0:
   echo "<h3>STATUS: PENDING</h3>";
   echo "Project name: {$row['projDesc']}<br />";
   echo "Link: <a href={$row['link']}>{$row['link']}</a><br />";
   echo "Request date: {$row['reqestDate']}<br />";
   echo "Email receivers: $emaillist<br />";
   echo "Notes:<br />{$row['notes']}<p />";
   if ($admin_user){
     echo "<form action=assign.php method=post>";
     echo "<input type=hidden name=projID value=$projID>";
     echo "<select name=assign>";
     $q2 = "select * from employees";
     $r2 = $sql->query($q2);
     while ($row2 = $sql->fetchRow($r2)){
       echo "<option>{$row2[0]}</option>";
     }
     echo "</select>";
     echo "<br />";
     echo "<input type=submit value=Assign>";
     echo "</form>";
     echo "<p />";
   }
   break;
 case 1:
   echo "<h3>STATUS: IN PROGRESS</h3>";
   echo "Project name: {$row['projDesc']}<br />";
   echo "IT Contact: {$row['ITName']}<br />";
   echo "Link: <a href={$row['link']}>{$row['link']}</a><br />";
   echo "Request date: {$row['reqestDate']}<br />";
   echo "Email receivers: $emaillist<br />";
   echo "Notes:<br />{$row['notes']}<p />";
   if ($admin_user){
     echo "<a href=unassign.php?projID=$projID>Unassign project</a><br />";
     echo "<a href=complete.php?projID=$projID>Completed</a><p />";
   }
   break;
 case 2:
   echo "<h3>STATUS: COMPLETE</h3>";
   echo "Project name: {$row['projDesc']}<br />";
   echo "IT Contact: {$row['ITName']}<br />";
   echo "Link: <a href={$row['link']}>{$row['link']}</a><br />";
   echo "Request date: {$row['reqestDate']}<br />";
   echo "Email receivers: $emaillist<br />";
   echo "Notes:<br />{$row['notes']}<p />";
   if ($admin_user){
     echo "<a href=reopen.php?projID=$projID>Reopen project</a><p />";
   }
   break;
}

if ($admin_user){
  echo "<a href=edit.php?projID=$projID>Edit project</a><br />";
  echo "<a href=delete.php?projID=$projID>Delete</a><p />";
}
//else if ($proj_user){
//  echo "<a href=edit.php?projID=$projID>Edit project</a><br />";
//}
else {
  echo "<a href={$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/projects/project.php?projID=$projID>";
  echo "Login</a> to edit this project<p />";
}

echo "<a href=index.php>Projects main</a><p />";

// notes
$notesQ = $sql->prepare("select ITName, stamp, notes from project_notes where projID = ?
           order by stamp DESC");
$notesR = $sql->execute($notesQ, array($projID));
$num = $sql->num_rows($notesR);
echo "<h3>Additional notes on this project</h3>";
if ($proj_user){
  echo "<a href=addnote.php?projID=$projID>Add a note</a> for this project<br />";
  
  $checkQ = $sql->prepare("select * from projects left outer join project_parties
             on projects.ITName = project_parties.email
             where LCASE(projects.ITName) = LCASE(?) or
             LCASE(project_parties.email) = LCASE(?)
             limit 1");
  $checkR = $sql->execute($checkQ, array($proj_user, $proj_user));
  // check #2 - see if this person is already 'in on' this project
  $check2Q = $sql->prepare("select * from project_parties where email=? and projID=?");
  $check2R = $sql->execute($check2Q, array($proj_user, $projID));
  /* not an IT person or an interested party */
  if ($sql->num_rows($checkR) == 0 && $sql->num_rows($check2R) == 0){
    echo "<div id=watchfield>";
    echo "<p /><a href='' onclick=\"watchToggle('yes',$projID,'$proj_user'); return false;\">Watch this project</a><br />";
    echo "</div>";
  }
  /* IT doesn't get to stop watching projects */
  else if ($sql->num_rows($checkR) == 0){
    echo "<div id=watchfield>";
    echo "<p /><a href='' onclick=\"watchToggle('no',$projID,'$proj_user'); return false;\">Stop watching this project</a><br />";
    echo "</div>";
  }
}
else {
  echo "<p /><a href={$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/projects/project.php?projID=$projID><br />";
  echo "Login</a> to add notes to this project<p />";
}
echo "<hr />";
for ($i = 0; $i < $num; $i++){
  $row = $sql->fetchRow($notesR);
  echo "Posted by {$row['ITName']} on {$row['stamp']}";
  echo "<blockquote>{$row['notes']}</blockquote>";
  echo "<hr />";
}

if ($proj_user){
  echo "<a href=addnote.php?projID=$projID>Add a note</a> for this project<br />";
  
  $checkQ = $sql->prepare("select * from projects left outer join project_parties
             on projects.ITName = project_parties.email
             where LCASE(projects.ITName) = LCASE(?) or
             LCASE(project_parties.email) = LCASE(?)
             limit 1");
  $checkR = $sql->execute($checkQ, array($proj_user, $proj_user));
  // check #2 - see if this person is already 'in on' this project
  $check2Q = $sql->prepare("select * from project_parties where email=? and projID=?");
  $check2R = $sql->execute($check2Q, array($proj_user, $prodID));
  /* not an IT person or an interested party */
  if ($sql->num_rows($checkR) == 0 && $sql->num_rows($check2R) == 0){
    echo "<div id=watchfield>";
    echo "<p /><a href='' onclick=\"watchToggle('yes',$projID,'$proj_user'); return false;\">Watch this project</a><br />";
    echo "</div>";
  }
  /* IT doesn't get to stop watching projects */
  else if ($sql->num_rows($checkR) == 0){
    echo "<div id=watchfield>";
    echo "<p /><a href='' onclick=\"watchToggle('no',$projID,'$proj_user'); return false;\">Stop watching this project</a><br />";
    echo "</div>";
  }
}
else {
  echo "<p /><a href=/auth/ui/loginform.php?redirect=/it/projects/project.php?projID=$projID><br />";
  echo "Login</a> to add notes to this project<p />";
}
echo "<a href=index.php>Projects main</a><p />";

?>

</body>
</html>
