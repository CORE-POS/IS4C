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
$page_title='Fannie - Lane Synchronization';
$header='Lane Sync';
include('../src/header.html');


echo '<p><a href="tablesync.php?tablename=products"><font size=4>Sync products</font></a></p>
	<p><a href="tablesync.php?tablename=custdata"><font size=4>Sync customer records</font></a></p>
	<p><a href="tablesync.php?tablename=employees"><font size=4>Sync employee records</font></a></p>
	<p><a href="tablesync.php?tablename=departments"><font size=4>Sync departments</font></a></p>';
	
include('../src/footer.html');
?>
