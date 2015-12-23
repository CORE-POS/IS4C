<?php
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
  include('../db.php');

  $checkQ = $sql->prepare("select * from likeCodes where likecode=?");
  $checkR = $sql->execute($checkQ, array($lc));
  $checkRow = $sql->fetch_row($checkR);
  if ($sql->num_rows($checkR) > 0){
    echo "Like code $lc is already in use as $checkRow[1]";
    echo "<br />";
    echo "<a href=addLikeCode.php>Try again</a>?<br />";
    echo "<a href=javascript:close()>Close</a>";
  }
  else {
    $writeQ = $sql->prepare("insert into likeCodes (likeCode,likeCodeDesc) values (?, ?)");
    $writeR = $sql->execute($writeQ, array($lc, $desc));
    echo "Like code $lc added as $desc<br /><br />";
    echo "<a href=addLikeCode.php>Add another</a><br />";
    echo "<a href=javascript:close()>Close</a>";
  }
}

