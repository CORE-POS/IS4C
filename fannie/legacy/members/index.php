<html>
<head>
<title>Welcome to the Back End!</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body bgcolor="#66CC99"><table width="500" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td colspan="2"><div align="center"><font face="Papyrus, Verdana, Arial, Helvetica, sans-serif">To begin, select one of the two options below...</font></div></td>
  </tr>
  <tr>
    <td><div align="center"><a href="mainMenu.php"><img src="../images/exist_up.gif" width="120" height="60" border="0"></a>
    </div></td>
    <td><div align="center"><a href=""><img src="../images/new_up.gif" width="120" height="60"></a>
    </div></td>
  </tr>
</table>
<div style="margin-top: 50px; margin-left: 20px;">
<?php
include('../../config.php');
require($FANNIE_ROOT.'auth/login.php');

if (!validateUserQuiet('memgen')){
	echo "<a href={$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}legacy/members>Login</a> to create owner numbers</a>";
}
else {
	echo "<a href=/git/fannie/mem/new.php>Create Owner Numbers</a>";
}
echo "<br /><a href={$FANNIE_URL}mem/numbers/>Print Owner Stickers</a>";
?>
</div>
</body>
</html>

