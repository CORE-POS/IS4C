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

/* simple set up for managing margins
   table unfiCategories looks like this:
	categoryID - int
	name - varchar
	margin - some variety of float
	testing - some variety of float

"testing" column exists to try out the effect of
new margins without misplacing the current settings
*/

/* configuration for your module - Important */
include("../../config.php");
/* html header, including navbar */
$page_title = "Fannie - UNFI Price File";
$header = "UNFI Category Margins";
include($FANNIE_ROOT.'src/header.html');

include($FANNIE_ROOT.'src/mysql_connect.php');

if (isset($_GET["reset"])){
	$reset = $_GET["reset"];
	$upQ = "";
	if ($reset == "testing")
		$upQ = "update unfiCategories set testing=margin";
	$upR = $dbc->query($upQ);
}

if (isset($_POST["ids"])){
	$ids = $_POST["ids"];
	$markups = $_POST["markups"];
	$testings = $_POST["testings"];

	for ($i = 0; $i < count($ids); $i++){
		$percent = $markups[$i] / 100.0;
		$tpercent = $testings[$i] / 100.0;	
		$upQ = "update unfiCategories set margin=$percent,testing=$tpercent where categoryID=$ids[$i]";
		$upR = $dbc->query($upQ);
	}
	echo "<h3>Category Margins Updated</h3>";
}

$fetchQ = "select categoryID,name,margin,testing from unfiCategories order by categoryID";
$fetchR = $dbc->query($fetchQ);

echo "<form method=post action=categoryMargins.php>";
echo "<table><tr>";
echo "<tr><td></td><td></td><td><i>Default</i></td><td><i>Testing</i></td</tr>";
echo "<th>Category</th><th>Description</th><th>Markup</th><th>Markup</th></tr>";
while ($fetchW = $dbc->fetch_array($fetchR)){
	echo "<tr>";
	echo "<td>$fetchW[0]<input type=hidden name=ids[] value=$fetchW[0] /></td>";
	echo "<td>$fetchW[1]</td>";
	$markup = $fetchW[2]*100;
	echo "<td><input type=text name=markups[] size=4 value=$markup />%</td>";
	$testing = $fetchW[3]*100;
	echo "<td><input type=text name=testings[] size=4 value=$testing />%</td>";
	echo "</tr>";
}
echo "</table>";
echo "<input type=submit value=Update />";
echo "</form>";
echo "<a href=categoryMargins.php?reset=testing>Reset testing to defaults</a><br />";
echo "<a href=reCalcSRPs.php>Re-calculate SRPs</a>";

/* html footer */
include($FANNIE_ROOT.'src/footer.html');

?>
