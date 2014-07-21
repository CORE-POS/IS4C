<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
    * 28Feb13 Andy Theuninck moved functionality to SyncIndexPage.php
    * 19Jan13 Eric Lee Add productUser to table list
    * 10Oct12 Eric Lee Add memberCards to table list
*/
header('Location: SyncIndexPage.php');
exit;

include('../config.php');

$page_title = "Fannie : Sync Lane";
$header = "Sync Lane Operational Tables";
include($FANNIE_ROOT.'src/header.html');

echo "<form action=\"tablesync.php\" method=\"get\">";

echo "<b>Table</b>: <select name=\"tablename\">
    <option>Select a table</option>
    <option>products</option>
    <option>productUser</option>
    <option>custdata</option>
    <option>memberCards</option>
    <option>employees</option>
    <option>departments</option>
    <option>tenders</option>
</select><br /><br />";

echo "<b>Other table</b>: <input type=\"text\" name=\"othertable\" /><br /><br />";

echo '<input type="submit" value="Send Data" />';
echo "</form>";
echo '<a href="store/">Sync Stores</a>';

include($FANNIE_ROOT.'src/footer.html');

?>
