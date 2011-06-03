<?php
/*******************************************************************************

    Copyright 2007 People's Food Co-op, Portland, Oregon.

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
$page_title = 'Fannie - Admin Tools';
$header = 'Admin Tools';
include('../src/header.html');
?>

<a href="mem/find_member.php"><font size=4>Membership Management</font></a></br>
	Access membership database
</br></br>
<a href="LookupReceipt"><font size=4>Transaction Look-up</font></a></br>
	Search transaction history and reprint receipts
</br></br>
<a href="labels"><font size=4>Generate Shelftags</font></a><br>
	Create and print shelftag batches
<!--<a href="/admin/volunteers.php"><font size=4>Volunteer Hours</font></a></br>
	Enter volunteer hours worked
</br></br>
<a href="/admin/charges.php"><font size=4>Staff Charges</font></a><br>
	View staff charge totals
</br></br>
<a href="/admin/patronage.php"><font size=4>Patronage Pts. Calculator</font></a></br>
	View patronage point totals and calculate refunds
</br></br>
<a href="shelftags.php"><font size=4>Generate Shelftags</font></a><br>
	Create and print shelftag batches
</br>-->
</body>
<?
include('../src/footer.html');
?>
