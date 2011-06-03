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

/* configuration for your module - Important */
include("../../config.php");

/* html header, including navbar */
$page_title = "Fannie - Vendor Price File";
$header = "Vendor Price File";
include($FANNIE_ROOT.'src/header.html');
?>

<body>
<table cellspacing=0 cellpadding=3 border=1>
<tr>
        <td><a href=categoryMargins.php>Category Margins</a></td>
	<td>View and adjust margins for each UNFI category</td>
</tr>
<tr>
	<td><a href=srps.php>Recalculate SRPs</a></td>
	<td>Re-compute SRPs for the vendor price change page based on
	    default or testing margins</td>
</tr>
<tr>
	<td><a href=uploadPriceSheet.php>Upload Price Sheet</a></td>
	<td>Load a new vendor price sheet (this is still a bit complicated. <a href=howtoUL.php>Howto</a>.)</td>
</tr>
<tr>
	<td><a href=batchStart.php>Create Price Change Batch</a></td>
	<td>Compare current &amp; desired margins, create batch for updates</td>
</tr>
</table>

<?php
/* html footer */
include($FANNIE_ROOT.'src/footer.html');
