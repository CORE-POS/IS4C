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
$page_title = 'Fannie : Auth : Reset Password';
$header = 'Fannie : Auth : Reset Password';

include($FANNIE_ROOT."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_GET['name'])){
  $name = $_GET['name'];
  $newpass = '';
  srand();
  for($i=0;$i<8;$i++){
    switch(rand(1,3)){
    case 1: // digit
    $newpass .= chr(48+rand(0,9));
    break;
    case 2: // uppercase
    $newpass .= chr(65+rand(0,25));
    break;
    case 3:
    $newpass .= chr(97+rand(0,25));
    break;
    }
  }
  if (changeAnyPassword($name,$newpass)){
    echo "User $name's password reset succesfully<p />";
    echo 'New password is: '.$newpass.'<p />';
  }
}
else {
  echo "<form method=get action=resetUserPassword.php>";
  echo "User name: <select name=name>";
  foreach(getUserList() as $uid => $name)
    echo "<option>".$name."</option>";
  echo '</select> ';
  echo "<input type=submit value=Submit /></form>";  
}
echo '<p />';
echo '<a href="menu.php">Main menu</a>';
include($FANNIE_ROOT."src/footer.html");
?>
