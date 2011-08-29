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

include('../../config.php');
include($FANNIE_ROOT.'src/mysql_connect.php');

$page_title = "Fannie : Tax Rates";
$header = "Tax Rates";
include($FANNIE_ROOT.'src/header.html');

if (isset($_REQUEST['sub'])){
	$desc = $_REQUEST['desc'];
	$rate = $_REQUEST['rate'];
	$id = 1;
	$dbc->query("TRUNCATE TABLE taxrates");
	for ($j=0;$j<count($desc);$j++){
		if (empty($desc[$j]) || empty($rate[$j])) continue;
		if (isset($_REQUEST['del'.$j])) continue;

		$q = sprintf("INSERT INTO taxrates (id,rate,description)
			VALUES (%d,%f,%s)",$id,$rate[$j],$dbc->escape($desc[$j]));
		$dbc->query($q);
		$id++;
	}
}

$taxQ = "SELECT id,rate,description FROM taxrates ORDER BY id";
$taxR = $dbc->query($taxQ);

echo '<form action="index.php" method="post">';
echo '<table cellspacing="0" cellpadding="4" border="1">';
echo '<tr><th>Description</th><th>Rate</th><th>Delete</th></tr>';
echo '<tr><td>NoTax</th><td>0.00</td><td>&nbsp;</td></tr>';
$i=0;
while($taxW = $dbc->fetch_row($taxR)){
	printf('<tr><td><input type="text" name="desc[]" value="%s" /></td>
		<td><input type="text" size="8" name="rate[]" value="%f" /></td>
		<td><input type="checkbox" name="del%d" /></td></tr>',
		$taxW['description'],$taxW['rate'],$i);
	$i++;
}
echo '<tr><td><input type="text" name="desc[]" /></td>
	<td><input type="text" size="8" name="rate[]" /></td>
	<td>NEW</td></tr>';
echo "</table>";
echo '<br /><input type="submit" value="Save Tax Rates" name="sub" />';
echo '</form>';

include($FANNIE_ROOT.'src/footer.html');
?>
