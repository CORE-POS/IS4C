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
$page_title = 'Fannie : Auth : Add User to Group';
$header = 'Fannie : Auth : Add User to Group';

include($FANNIE_ROOT."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_GET['group'])){
  $group=$_GET['group'];
  $user = $_GET['user'];
  if (addUserToGroup($group,$user)){
    echo "User $user added to  $group succesfully<p />";
  }
}
else {
  echo "<form method=get action=addGroupUser.php>";
  echo '<table>';
echo "<tr><th>Group name</th><td><select name=group>";
foreach(getGroupList() as $uid => $name)
    echo "<option>".$name."</option>";
echo "</select></td></tr>";
echo "<tr><th>Username</th><td><select name=user>";
foreach(getUserList() as $uid => $name)
    echo "<option>".$name."</option>";
echo "</select></td></tr>";
  echo '</table>';
  echo "<input type=submit value=Submit /></form>";  
}
?>
<p />
<a href=menu.php>Main menu</a>
<?php
include($FANNIE_ROOT."src/footer.html");
?>
