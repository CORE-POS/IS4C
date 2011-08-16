<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require('../login.php');
$path = guesspath();
$page_title = 'IS4C : Auth : Delete User';
$header = 'IS4C : Auth : Delete User';

include($path."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_POST['yes'])){
  $name = $_POST['name'];
  $success = deleteLogin($name);
  if (!$success){
    echo "User '$name' does not exist<p />";
    echo "<a href=menu.php>Main menu</a>  |  <a href=deleteUser.php>Try again</a>?";
    return;
  }
  echo "User $name deleted<p />";
  echo "<a href=menu.php>Main menu</a>";
}
else if (isset($_POST['warn'])){
  $name = $_POST['name'];
  echo "Are you sure you want to delete user '$name'?<p />";
  echo "<table cellspacing=3 cellpadding=3><tr>";
  echo "<td><form action=deleteUser.php method=post>";
  echo "<input type=submit name=yes value=Yes>";
  echo "<input type=hidden name=name value=$name>";
  echo "</form</td>";
  echo "<td><form action=menu.php method=post>";
  echo "<input type=submit name=no value=No>";
  echo "</form></td></tr></table>";
}
else {
  echo "<form action=deleteUser.php method=post>";
  echo "Username: <input type=text name=name> <input type=submit value=Delete>";
  echo "<input type=hidden name=warn value=warn>";
  echo "</form>";
  echo "<p /><a href=menu.php>Main menu</a>";
}

include($path."src/footer.html");
?>
