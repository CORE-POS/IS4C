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

/* SRPs are re-calculated based on the current margin or testing
   settings, which may have changed since the order was imported */

/* configuration for your module - Important */
include("../../config.php");
/* html header, including navbar */
$page_title = "Fannie - UNFI Price File";
$header = "Recalculate SRPs from Margins";
include($FANNIE_ROOT.'src/header.html');

if (isset($_GET['type'])){
	include($FANNIE_ROOT.'src/mysql_connect.php');

	$type = $_GET['type'];
	$margin_field = ($type == "defaults") ? "margin" : "testing";

	$margins = array();
	$marginQ = "select categoryID,$margin_field from unfiCategories";
	$marginR = $dbc->query($marginQ);
	while ($marginW = $dbc->fetch_array($marginR))
		$margins[$marginW[0]] = (float)$marginW[1];

	$fetchQ = "select u.upcc, u.cat, p.cost from UNFI_order as u left join
		   prodExtra as p on p.upc=u.upc";
	$fetchR = $dbc->query($fetchQ);
	while ($fetchW = $dbc->fetch_array($fetchR)){
		// calculate a SRP from unit cost and desired margin
		$srp = round($fetchW[2] / (1 - $margins[$fetchW[1]]),2);

		// prices should end in 5 or 9, so add a cent until that's true
		while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" and
		       substr($srp,strlen($srp)-1,strlen($srp)) != "9")
			$srp+=.01;

		$upQ = "update UNFI_order set wfc_srp=$srp where upcc='$fetchW[0]'";
		$upR = $dbc->query($upQ);
	}

	echo "<b>SRPs have been updated</b><br />";
	echo "<a href=index.php>NPP Menu</a>";
}
else{
?>
<body>
Which margins would you like to use to calculate new SRPs<br />
<br />
<a href=reCalcSRPs.php?type=defaults>Default margins</a><br />
<a href=reCalcSRPs.php?type=testing>Testing Margins</a>
</body>
</html>
<?php
}

/* html footer */
include($FANNIE_ROOT.'src/footer.html');
?>
