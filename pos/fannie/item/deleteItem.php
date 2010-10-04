<?php
/*******************************************************************************

    Copyright 2005,2009 Whole Foods Community Co-op

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
include('../config.php');

include($FANNIE_ROOT.'auth/login.php');
$name = checkLogin();
if (!$name){
	header("Location: {$FANNIE_URL}auth/ui/loginform.php?redirect={$FANNIE_URL}item/deleteItem.php");
	exit;
}
$user = validateUserQuiet('delete_items');
if (!$user){
	echo "Not allowed";
	exit;
}

include('../src/mysql_connect.php');
$page_title = 'Fannie - Item Maintanence';
$header = 'Item Maintanence';
include('../src/header.html');
?>
<script type"text/javascript" src=ajax.js></script>

<?php

echo "<h1 style=\"color:red;\">Delete Product Tool</h1>";

if (isset($_REQUEST['upc']) && !isset($_REQUEST['deny'])){
	$upc = str_pad($_REQUEST['upc'],13,'0',STR_PAD_LEFT);
	
	if (isset($_REQUEST['submit'])){
		$rp = $dbc->query(sprintf("SELECT * FROM products WHERE upc=%s",$dbc->escape($upc)));
		if ($dbc->num_rows($rp) == 0){
			printf("No item found for <b>%s</b><p />",$upc);
			echo "<a href=\"deleteItem.php\">Go back</a>";
		}
		else {
			$rw = $dbc->fetch_row($rp);
			echo "<form action=deleteItem.php method=post>";
			echo "<b>Delete this item?</b><br />";
			echo "<table cellpadding=4 cellspacing=0 border=1>";
			echo "<tr><th>UPC</th><th>Description</th><th>Price</th></tr>";
			printf("<tr><td><a href=\"itemMain.php?upc=%s\" target=\"_new%s\">
				%s</a></td><td>%s</td><td>%.2f</td></tr>",$rw['upc'],
				$rw['upc'],$rw['upc'],$rw['description'],$rw['normal_price']);
			echo "</table><br />";
			printf("<input type=hidden name=upc value=\"%s\" />",$upc);
			echo "<input type=submit name=confirm value=\"Yes, delete this item\" />";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			echo "<input type=submit name=deny value=\"No, keep this item\" />";
		}
	}
	else if (isset($_REQUEST['confirm'])){
		$upc = $dbc->escape($upc);
		$delQ = sprintf("DELETE FROM products WHERE upc=%s",$upc);
		$dbc->query($delQ);
		$delxQ = sprintf("DELETE FROM prodExtra WHERE upc=%s",$upc);
		$dbc->query($delxQ);
		if ($dbc->table_exists("scaleItems")){
			$scaleQ = sprintf("DELETE FROM scaleItems WHERE upc=%s",$upc);
			$dbc->query($scaleQ);
		}

		include('laneUpdates.php');
		deleteProductAllLanes($upc);

		printf("Item %s has been deleted<br /><br />",$upc);
		echo "<a href=\"deleteItem.php\">Delete another item</a>";
	}
}else{
	echo "<form action=deleteItem.php method=post>";
	echo "<input name=upc type=text id=upc> Enter UPC/PLU here<br><br>";

	echo "<input name=submit type=submit value=submit>";
	echo "</form>";
	echo "<script type=\"text/javascript\">
		\$(document).ready(function(){ \$('#upc').focus(); });
		</script>";
}

include ('../src/footer.html');

?>
