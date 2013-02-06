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

/*
   If all machines are on MySQL, this should be
   much faster than SQLManager transfer
*/

// mysql version probably looks like this (not tested):
// could use some error checking on connection/success, obviously
include_once($FANNIE_ROOT.'src/tmp_dir.php');
$tempfile = tempnam(sys_get_temp_dir(),$table.".sql");
if (empty($table)) return;
exec("mysqldump -u $FANNIE_SERVER_USER -p$FANNIE_SERVER_PW -h $FANNIE_SERVER $FANNIE_OP_DB $table > $tempfile");
$i=0;
foreach($FANNIE_LANES as $lane){
	exec("mysql -u {$lane['user']} -p{$lane['pw']} -h {$lane['host']} {$lane['op']} < $tempfile");
	echo "<li>Lane ".($i+1)." completed successfully</li>";
	$i++;
}
unlink($tempfile);

?>
