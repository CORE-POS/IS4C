<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../../config.php');

$page_title = "Fannie :: Patronage Tools";
$header = "Patronage Tools";

include($FANNIE_ROOT.'src/header.html');
?>
<ul>
<li><a href="working.php">Create working table</a></li>
<li><a href="gross.php">Calculate gross purchases</a></li>
<li><a href="rewards.php">Calculate rewards</a></li>
<li><a href="net.php">Update net purchases</a></li>
<li><a href="report.php">Report of loaded info</a></li>
<li><a href="upload.php">Upload rebates</a></li>
</ul>
<?php
include($FANNIE_ROOT.'src/footer.html');
?>
