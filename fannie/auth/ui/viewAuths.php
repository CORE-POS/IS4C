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
$page_title = 'Fannie : Auth : View Authorizations';
$header = 'Fannie : Auth : View Authorizations';

include($path."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_POST['name'])){
  $name = $_POST['name'];
  showAuths($name);
}
echo "<p />";
echo "<form action=viewAuths.php method=post>";
echo "Username: <input type=text name=name> <input type=submit value=View>";
echo "</form>";
echo "<a href=menu.php>Main menu</a>";

include($path."src/footer.html");
?>
