<?php

include('../../../config.php');

// this script changes a user's samba password

include($FANNIE_ROOT.'auth/login.php');
$name = checkLogin();

// user must be logged in and can only change their
// own password
if (!$name){
  header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/it/samba");
  return;
}

if (isset($_POST['password1'])){
  $password1 = $_POST['password1'];
  $password2 = $_POST['password2'];
  // passwords must match
  if ($password1 != $password2){
    echo "Passwords do not match.  <a href=index.php>Try again</a>";
    return;
  }
  // passwords must be valid characters
  if (!isAlphanumeric($password1)){
    echo "Password can only contain numbers, letters, and underscores. ";
    echo "<a href=index.php>Try again</a>";
    return;
  }
  // double-check to make sure argument is shell-safe
  $password1 = escapeshellarg($password1);
  exec("sudo /usr/local/bin/sambapass.sh $name $password1");
  echo "Samba password changed for user '$name'";
}
else{
?>
<form method=post action=index.php>
Changing the samba password for user '<?php echo $name ?>'
<p />
<table><tr>
<td><b>New password</b></td>
<td><input type=password name=password1 /></td></tr><tr>
<td><b>Re-type password</b></td>
<td><input type=password name=password2 /></td></tr>
</table>
<input type=submit value=Change />
</form>
<?php  
}
?>
