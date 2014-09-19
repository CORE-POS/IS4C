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
if (isset($_GET['group'])){
    $group=$_GET['group'];
    $groupHeading=" : $group";
} else {
    $group="";
    $groupHeading="";
}
$page_title = "Fannie : Auth : Group Details$groupHeading";
$header = "Fannie : Auth : Group Details$groupHeading";

include($FANNIE_ROOT."src/header.html");

if (!validateUser('admin')){
  return;
}

if (isset($_GET['group'])){
  detailGroup($group);
}
echo "<form method=get action=groupDetail.php>";
echo "Group name to view: <select name=group>";
foreach(getGroupList() as $uid => $name)
    echo "<option>".$name."</option>";
echo "</select>";
echo '&nbsp;&nbsp;&nbsp;<input type="submit" value="View" />';
echo '</form>';

?>
<p />
<a href=menu.php>Main menu</a>
<?php
include($FANNIE_ROOT."src/footer.html");
?>
