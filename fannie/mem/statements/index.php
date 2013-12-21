<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
$header='Send Statements';
include('../../src/header.html');

?>
<ul>
<li><a href=indvAR.php>AR (Member)</a></li>
<li><a href=busAR.php>AR (Business EOM)</a></li>
<li><a href=busAR.php>AR (Business Any Balance)</a></li>
<li><a href=equity.php>Equity</a></li>
</ul>
<p />
<a href=history.php>Sent E-mail History</a>
<?php
include('../../src/footer.html');
?>
