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
$page_title = 'Fannie : Auth : Delete Group Authorization';
$header = 'Fannie : Auth : Delete Group Authorization';

include($FANNIE_ROOT."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_POST['yes'])){
  $name = $_POST['name'];
  $class = $_POST['class'];

  $success = deleteAuthFromGroup($name,$class);

  if (!$success){
    echo "<a href=menu.php>Main menu</a>  |  <a href=deleteGroupAuth.php>Try again</a>?";
    return;
  }
  echo "Authorizations deleted<p />";
  echo "<a href=menu.php>Main menu</a>";

}
else if (isset($_POST['warn'])){
  $name = $_POST['name'];
  $class = $_POST['class'];
  echo "Are you sure you want to delete ALL authorizations for $name in class $class?<p />";
  echo "<table cellspacing=3 cellpadding=3><tr>";
  echo "<td><form action=deleteGroupAuth.php method=post>";
  echo "<input type=submit value=Yes name=yes>";
  echo "<input type=hidden name=name value=$name>";
  echo "<input type=hidden name=class value=$class>";
  echo "</form</td>";
  echo "<td><form method=post action=menu.php>";
  echo "<input type=submit name=no value=No>";
  echo "</form></td></tr></table>";
}
else {
  echo "WARNING: this will delete ALL authorizations for a group in the selected authorization class. ";
  echo "If you need finer-grained control over a group with multiple authorizations in the ";
  echo "same class (e.g., multiple sub-class ranges) you should edit in SQL<p />";
  echo "<form method=post action=deleteGroupAuth.php>";
  echo "<table cellspacing=3 cellpadding=3>";
echo "<tr><th style='text-align:right;'>Group name</th><td><select name=name>";
foreach(getGroupList() as $uid => $name)
    echo "<option>".$name."</option>";
echo "</select></td></tr>";
echo "<tr><th style='text-align:right;' title='Delete all authorizations in the selected class'>Authorization class</th><td><select name=class>";
foreach(getAuthList() as $uid => $name)
    echo "<option>".$name."</option>";
echo "</select></td></tr>";
  echo "<tr><td><input type=submit value=Delete></td><td><input type=reset value=Reset></td></tr>";
  echo "<input type=hidden value=warn name=warn>";
  echo "</table></form>";
  echo "<p /><a href=menu.php>Main menu</a>";
}

include($FANNIE_ROOT."src/footer.html");
?>
