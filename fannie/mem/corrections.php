<?php
/*******************************************************************************

    Copyright 2007 Alberta Cooperative Grocery, Portland, Oregon.

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/
// A page to search the member base.
$page_title='Fannie - Member Management Module';
$header='Find A Member';
include('../src/header.html');
include ('./includes/header.html');

require_once('../src/mysql_connect.php');

$CORRECTION_CASHIER = 1001;
$CORRECTION_LANE = 30;
$CORRECTION_DEPT = 800;

if (isset($_REQUEST['type'])){
	if (file_exists('includes/'.$_REQUEST['type'].'.php'))
		include('includes/'.$_REQUEST['type'].'.php');	
	else {
		echo "<em>Error: requested correction not found</em>";
		echo "<br /><br />";
		echo "<a href=\"corrections.php\">Back</a>";
	}
}
else {
?>
<ul>
<li><a href="corrections.php?type=equity_transfer">Equity Transfer</a></li>
<li><a href="corrections.php?type=ar_transfer">AR Transfer</a></li>
<li><a href="corrections.php?type=equity_ar_swap">AR/Equity Swap</a></li>
<li><a href="corrections.php?type=equity_ar_dump">Remove Equity/AR</a></li>
<li><a href="corrections.php?type=patronage_transfer">Transfer Patronage</a></li>
</ul>
<?php
}

include('../src/footer.html');
?>
