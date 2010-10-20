<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');
include($FANNIE_ROOT.'auth/login.php');

if (!validateUserQuiet('tenders')){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}admin/Tenders/");
	exit;
}

$page_title = "Fannie : Tenders";
$header = "Tenders";
include($FANNIE_ROOT.'src/header.html');

if (isset($_REQUEST['TenderID']) && is_numeric($_REQUEST['TenderID'])){
	$deleteQ = sprintf("DELETE FROM tenders WHERE TenderID=%d",
		$_REQUEST['TenderID']);
	$deleteR = $dbc->query($deleteQ);

	echo "<i>Tender deleted</i>";
	echo "<br /><br />";
	echo '<a href="delete.php">Delete another</a>';
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo '<a href="index.php">Back to edit tenders</a>';
}
else {
	echo "<b>Be careful. Deleting a tender could make a mess.
		If you run into problems, re-add the tender using
		the same two-character code.</b>";
	echo "<br /><br />";
	echo '<form action="delete.php" method="post">';
	echo '<select name="TenderID">';
	echo '<option>Select a tender...</option>';
	$q = "SELECT TenderID,TenderCode,TenderName FROM tenders ORDER BY TenderID";
	$r = $dbc->query($q);
	while($w = $dbc->fetch_row($r)){
		printf('<option value="%d">%s - %s</option>',
			$w['TenderID'],$w['TenderCode'],
			$w['TenderName']);
	}
	echo "</select>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo '<input type="submit" value="Delete Selected Tender" />';
	echo "</form>";
}

include($FANNIE_ROOT.'src/footer.html');
?>
