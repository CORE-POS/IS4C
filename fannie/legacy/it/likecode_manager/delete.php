<?php
include('../../../config.php');

include($FANNIE_ROOT.'auth/login.php');
if (!validateUserQuiet('manage_likecodes')){
  header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/likecode_manager/edit.php");
  return;
}

$lc = $_GET['likecode'];

?>

<form action=index.php method=post>
Delete like code <?php echo $lc ?><br />
<input type=hidden name=type value=delete>
<input type=hidden name=likecode value=<?php echo $lc?>>
<input type=submit name=submit value=Yes>
&nbsp;&nbsp;&nbsp;
<input type=submit name=submit value=No>
</form>
