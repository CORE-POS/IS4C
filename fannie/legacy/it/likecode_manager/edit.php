<?php
include('../../../config.php');

include($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('manage_likecodes')){
  header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/likecode_manager/edit.php");
  return;
}

$lc = $_GET['likecode'];

if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
include('../../db.php');

$q = "select likecodedesc from likecodes where likecode=$lc";
$r = $sql->query($q);
$row = $sql->fetch_array($r);
$desc = $row[0];

?>

<form action=index.php method=post>
Like code: <?php echo $lc ?> <br />
Description:
<input type=text name=desc value="<?php echo $desc ?>"><br />
<input type=submit value=Change>
<input type=hidden name=type value=edit>
<input type=hidden name=likecode value=<?php echo $lc ?>>

</form>
