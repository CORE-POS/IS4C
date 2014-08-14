<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
$page_title = 'Fannie : Auth : Edit Authorization Class';
$header = 'Fannie : Auth : Edit Authorization Class';

include($FANNIE_ROOT."src/header.html");

if (!validateUser('admin')){
  return;
}

if ($_POST['button2']){
  $name = $_POST['name'];
  $notes = $_POST['notes'];
  if (empty($notes)){
    echo "Notes are required. Document your work.<p />";
    echo "<a href=menu.php>Main menu</a>  |  <a href=editClassNotes.php>Try again</a>?";
    return;
  }
  $success = updateAuthNotes($name,$notes);
  echo "Class '$name' updated succesfully<p />";
  echo "<a href=menu.php>Main menu</a>";
}
else if (isset($_POST['button1'])){
    $name = $_POST['name'];
    echo "<form action=editClassNotes.php method=post>";
    echo "<table cellspacing=4 cellpadding=4>";
    echo "<tr><td>Authorization class:</td><td><select name=name>";
    echo "<option>".$name."</option></select></td></tr>";
    echo "<tr><td>Notes</td><td><textarea name=notes rows=10 cols=40>";
    echo getAuthNotes($name);
    echo "</textarea></td></tr>";
    echo "<tr><td><input type=submit name=button2 value=Save></td><td><input type=reset value=Reset></td></tr>";
    echo "</table</form>";
}
else {
  echo "<form action=editClassNotes.php method=post>";
  echo "<table cellspacing=4 cellpadding=4>";
  echo "<tr><td>Authorization class:</td><td><select name=name>";
  foreach(getAuthList() as $name)
    echo "<option>".$name."</option>";
  echo "</select></td></tr>";
  echo "<tr><td><input type=submit name=button1 value=Edit></td><td><input type=reset value=Reset></td></tr>";
  echo "</table</form>";
}

include($FANNIE_ROOT."src/footer.html");
?>
