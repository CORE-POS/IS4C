<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

    This file is part of Fannie.

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

require_once('../src/mysql_connect.php');

if (isset($_POST['yes'])){
	$batchID = $_POST['batchID'];
	$delBatchQ = "delete from batches where batchID = $batchID";
	echo $delBatchQ."<br />";
	$delBatchR = mysql_query($delBatchQ);
	$delItemsQ = "delete from batchlist where batchID = $batchID";
	echo $delItemsQ."<br />";
	$delBatchR = mysql_query($delItemsQ);
	$updateQ = "exec batchUpdate";
	$updateR = mysql_query($updateQ);
	echo "<h2>Batch #$batchID deleted</h2><p /></br></br>";
	//  echo "Return to <a href=index.php>list of batches</a>";
	echo "<p>Return to batch list";
	echo "<form action=index.php method=post>";
	echo "<input type=submit name=back value=back></form></p>";
}
else {
	$batchID = $_GET['batchID'];
	$fetchQ = "select batchName from batches where batchID = $batchID";
	$fetchR = mysql_query($fetchQ);
	$fetchRow = mysql_fetch_row($fetchR);
	$name = $fetchRow[0];

	echo "<h2>Are you sure you want to delete batch #$batchID:</h2><p>$name</p>";
	echo "<table cellspacing=4 cellpadding=4><tr><td>";
	echo "<form action=deleteBatch.php method=post>";
	echo "<input type=hidden name=batchID value=$batchID>";
	echo "<input type=submit name=yes value=Yes>";
	echo "</form>";
	echo "</td><td>";
	echo "<form action=index.php method=post>";
	echo "<input type=submit name=no value=No>";
	echo "</form>";
	echo "</td></tr></table>";
}

?>
