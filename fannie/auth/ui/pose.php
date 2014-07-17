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
$page_title = 'Fannie : Auth : Pose';
$header = 'Fannie : Auth : Pose';

$name = checkLogin();
if (validateUserQuiet('admin')){
    if (isset($_POST["newname"])){
        pose($_POST["newname"]);
        header("Location: menu.php");
        return;
    }
    else {
        include($FANNIE_ROOT."src/header.html");
?>
<form method=post action=pose.php>
<?php
echo "Username:<select name=newname>";
foreach(getUserList() as $uid => $name)
    echo "<option>".$name."</option>";
echo "</select>";
echo '&nbsp;&nbsp;&nbsp;<input type="submit" value="Pose" />';
?>
</form>
<?php
        include($FANNIE_ROOT."src/footer.html");
    }
}
else {
    include($FANNIE_ROOT."src/header.html");
    echo "You aren't authorized to use this feature";
    include($FANNIE_ROOT."src/footer.html");
}
?>
