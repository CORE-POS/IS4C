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
?>
<html>
<body bgcolor=cabb1e>
<?php

$DEFAULT_PASSWORD = 'password';

require('../login.php');

if (!validateUser('admin')){
  return;
}

if (isset($_GET['name'])){
  $name = $_GET['name'];
  if (changeAnyPassword($name,$DEFAULT_PASSWORD)){
    echo "User $name's password reset succesfully<p />";
  }
}
else {
  echo "<form method=get action=resetUserPassword.php>";
  echo "User name: <input type=text name=name /><br />";
  echo "<input type=submit value=Submit /></form>";  
}
?>
<p />
<a href=menu.php>Main menu</a>

</body>
</html>

