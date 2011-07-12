<?php
include('../../config.php');

$lc = isset($_POST['likecode'])?$_POST['likecode']:'';
$desc = isset($_POST['desc'])?$_POST['desc']:'';
echo "<body bgcolor=#ffffcc>";
if (empty($lc)){
  echo "<form action=addLikeCode.php method=post>";
  echo "Like Code: ";
  echo "<input name=likecode type=text><br /><br />";
  echo "Description: ";
  echo "<input type=text name=desc><br /><br />";
  echo "<input type=submit value=Add>";
}
else {
  if (!class_exists("SQLManager")) require_once($FANNIE_ROOT.'src/SQLManager.php');
  include('../db.php');

  $checkQ = "select * from likeCodes where likecode=$lc";
  $checkR = $sql->query($checkQ);
  $checkRow = $sql->fetch_row($checkR);
  if ($sql->num_rows($checkR) > 0){
    echo "Like code $lc is already in use as $checkRow[1]";
    echo "<br />";
    echo "<a href=addLikeCode.php>Try again</a>?<br />";
    echo "<a href=javascript:close()>Close</a>";
  }
  else {
    $writeQ = "insert into likeCodes (likecode,likecodedesc) values ($lc,'$desc')";
    $writeR = $sql->query($writeQ);
    echo "Like code $lc added as $desc<br /><br />";
    echo "<a href=addLikeCode.php>Add another</a><br />";
    echo "<a href=javascript:close()>Close</a>";
  }
}
?>
