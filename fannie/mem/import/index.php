<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
include('../../config.php');

$page_title = "Fannie :: Member Tools";
$header = "Import Member Information";

include($FANNIE_ROOT.'src/header.html');
?>
<ul>
<li><a href="nn.php">Names &amp; Numbers</a></li>
<li><a href="contact.php">Contact Information</a></li>
<li><a href="equity.php">Existing Equity</a></li>
</ul>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
