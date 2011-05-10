<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require('../login.php');
$path = guesspath();
include($path."config.php");
$page_title = 'Fannie : Auth : Menu';
$header = 'Fannie : Auth : Menu';
include($path."src/header.html");

$name = checkLogin();
if (!$name){
  echo "You must be <a href=loginform.php>logged in</a> to use this</a>";
}
else {
  $priv = checkAuth($name,'admin');
  $options = 'all';
  if (!$priv){
    $options = 'limited';
  }
  
  /* password change only allowed if not using
     UNIX or LDAP passwords */
  echo "Welcome $name<p />";
  echo "<ul>";
  if ($options == 'all'){
    echo "<li><a href=viewUsers.php>View users</a></li>";
    echo "<li><a href=createUser.php>Create user</a></li>";
    if (!$FANNIE_AUTH_SHADOW && !$FANNIE_AUTH_LDAP)
	echo "<li><a href=resetUserPassword.php>Reset user password</a></li>";
    echo "<li><a href=deleteUser.php>Delete user</a></li>";
    echo "<li><a href=viewAuths.php>View authorizations</a></li>";
    echo "<li><a href=addAuth.php>Add new authorization</a></li>";
    echo "<li><a href=deleteAuth.php>Delete user's authorizations</a></li>";
    echo "<br />";
    echo "<li><a href=addGroup.php>Add a Group</a></li>";
    echo "<li><a href=deleteGroup.php>Delete a Group</a></li>";
    echo "<li><a href=addGroupUser.php>Add User to Group</a></li>";
    echo "<li><A href=deleteGroupUser.php>Delete User from Group</a></li>";
    echo "<li><a href=addGroupAuth.php>Add Group authorization</a></li>";
    echo "<li><A href=deleteGroupAuth.php>Delete Group authorizations</a></li>";
    echo "<li><a href=viewGroups.php>View Groups</a></li>";
    echo "<li><a href=groupDetail.php>View Group Details</a></li>";
    echo "<br />";
    echo "<li><a href=pose.php>Switch User</a></li>";
  }
  if (!$FANNIE_AUTH_SHADOW && !$FANNIE_AUTH_LDAP)
	  echo "<li><a href=changepass.php>Change password</a></li>";
  echo "<li><a href=loginform.php?logout=yes>Logout</a></li>";
  echo "</ul>";
}  

include($path."src/footer.html");
?>
