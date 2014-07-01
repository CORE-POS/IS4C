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
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require('../login.php');
include("../../config.php");
$page_title = 'Fannie : Auth : Delete User from Group';
$header = 'Fannie : Auth : Delete User from Group';

include($FANNIE_ROOT."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_POST['yes'])){
  $name = $_POST['name'];
  $user = $_POST['user'];
  $success = deleteUserFromGroup($name,$user);
  if (!$success){
    echo "<a href=menu.php>Main menu</a>  |  <a href=deleteGroupUser.php>Try again</a>?";
    return;
  }
  echo "User $user deleted from $name<p />";
  echo "<a href=menu.php>Main menu</a>";
}
else if (isset($_POST['warn'])){
  $name = $_POST['name'];
  $user = $_POST['user'];
  echo "Are you sure you want to delete user $user from group '$name'?<p />";
  echo "<table cellspacing=3 cellpadding=3><tr>";
  echo "<td><form action=deleteGroupUser.php method=post>";
  echo "<input type=submit name=yes value=Yes>";
  echo "<input type=hidden name=name value=$name>";
  echo "<input type=hidden name=user value=$user>";
  echo "</form</td>";
  echo "<td><form action=menu.php method=post>";
  echo "<input type=submit name=no value=No>";
  echo "</form></td></tr></table>";
}
else {
  echo "<form action=deleteGroupUser.php method=post>";
    echo "<ul>";
    echo "<li>The dropdowns below are not aware of each other.";
    echo "<br />The choice of a Group name  does not populate the Username dropdown with its actual members.";
    echo "<br />The choice of a Username does not populate the Group name dropdown with his/her actual memberships.";
    echo "</li>";
    echo "<li>There is no harm in deleting a User from a Group of which he/she is not a member.";
    echo "<br />After you click 'Delete' the program does not tell you whether the User was a member of the Group to begin with.";
    echo "</li>";
    echo "<li>If you delete the last User in a Group the Group will be deleted.";
    echo "<br />The program does not warn you if the User you are about to delete is the last in the Group.";
    echo "</li>";
    echo "</ul>";
  echo '<table>';
echo "<tr><th>Group name</th><td><select name=name>";
foreach(getGroupList() as $uid => $name)
    echo "<option>".$name."</option>";
echo "</select></td></tr>";
echo "<tr><th>Username</th><td><select name=user>";
foreach(getUserList() as $uid => $name)
    echo "<option>".$name."</option>";
echo "</select></td></tr>";
  echo '</table>';
  echo "<input type=submit value=Delete>";
  echo "<input type=hidden name=warn value=warn>";
  echo "</form>";
  echo "<p /><a href=menu.php>Main menu</a>";

}

include($FANNIE_ROOT."src/footer.html");
?>
