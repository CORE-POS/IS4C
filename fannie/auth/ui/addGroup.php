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
$path = guesspath();
$page_title = 'Fannie : Auth : Add Group';
$header = 'Fannie : Auth : Add Group';

include($path."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_GET['group'])){
  $group=$_GET['group'];
  $user = $_GET['user'];
  if (addGroup($group,$user)){
    echo "Group $group added succesfully<p />";
  }
}
else {
  echo "<form method=get action=addGroup.php>";
  echo '<table>';
  echo "<tr><th>Group name</th><td><input type=text name=group /></td></tr>";
echo "<tr><th>Username</th><td><select name=name>";
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
include($path."src/footer.html");
?>

