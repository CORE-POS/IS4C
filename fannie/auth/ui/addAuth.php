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

include('../login.php');
include("../../config.php");
$page_title = 'Fannie : Auth : Add Authorization';
$header = 'Fannie : Auth : Add Authorization';

include($FANNIE_ROOT."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_POST['name'])){
  $name = $_POST['name'];
  $class = $_POST['class'];
  $start = $_POST['start'];
  $end = $_POST['end'];

  $success = addAuth($name,$class,$start,$end);
  if (!$success){
    echo "Authorization could not be added.  Make sure user '$name' exists<p />";
    echo "<a href=menu.php>Main menu</a>  |  <a href=addAuth.php>Try again</a>?";
    return;
  }
  echo "Authorization added<p />";
  echo "<a href=menu.php>Main menu</a>";
}
else {
  echo "<form action=addAuth.php method=post>";
  echo "<table cellspacing=3 cellpadding=3>";
  echo "<tr><td>Username:</td><td><select name=name>";
  foreach(getUserList() as $uid => $name)
    echo "<option>".$name."</option>";
  echo "</select></td></tr>";
  echo "<tr><td>Authorization class:</td><td><select name=class>";
  foreach(getAuthList() as $name)
    echo "<option>".$name."</option>";
  echo "</select></td></tr>";
  echo "<tr><td>Subclass start:</td><td><input type=text name=start value=all></td></tr>";
  echo "<tr><td>Subclass end:</td><td><input type=text name=end value=all></td></tr>";
  echo "<tr><td><input type=submit value=Add></td><td><input type=reset value=reset></td></tr>";
  echo "</table></form>";
  echo "<p /><a href=menu.php>Main menu</a>";
}

include($FANNIE_ROOT."src/footer.html");
?>
