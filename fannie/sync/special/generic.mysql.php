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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - -
	.22Mar13 Add Andy's changes: "Variable scoping w/ sync scripts from within class"
	.https://github.com/CORE-POS/IS4C/commit/261fddb60849857043e9ef0da32c672d8d9cf77b
	.        prior to proposing mine be added.
	.14Jan13 Eric Lee Bugfix: non-port branch using -P.
	. 1Oct12 Eric Lee Support a port# in the lane host: 1.2.3.4:50001
	.                 Test return value of dump and each load.
*/

include(dirname(__FILE__).'/../../config.php');
include_once($FANNIE_ROOT.'src/tmp_dir.php');
$tempfile = tempnam(sys_get_temp_dir(),$table.".sql");
if (empty($table)) return;
$ret = 0;
$output = array();
exec("mysqldump -u $FANNIE_SERVER_USER -p$FANNIE_SERVER_PW -h $FANNIE_SERVER $FANNIE_OP_DB $table > $tempfile", $output, $ret);
if ( $ret > 0 ) {
	$report = implode('<br />', $output);
	if ( strlen($report) > 0 ) {
		$report = "<br />$report";
	}
	echo "<li>Dump failed, returned: $ret {$report}</li>";
}
else {
	$i=0;
	foreach($FANNIE_LANES as $lane){
		$ret = 0;
		$output = array();
		if ( strpos($lane['host'], ':') > 0 ) {
			list($host, $port) = explode(":", $lane['host']);
			exec("mysql -u {$lane['user']} -p{$lane['pw']} -h {$host} -P {$port} {$lane['op']} < $tempfile", $output, $ret);
		}
		else {
			exec("mysql -u {$lane['user']} -p{$lane['pw']} -h {$lane['host']} {$lane['op']} < $tempfile", $output, $ret);
		}
		if ( $ret == 0 ) {
			echo "<li>Lane ".($i+1)." completed successfully</li>";
		} else {
			$report = implode('<br />', $output);
			if ( strlen($report) > 0 ) {
				$report = "<br />$report";
			}
			echo "<li>Lane ".($i+1)." failed, returned: $ret {$report}</li>";
		}
		unset($output);
		$i++;
	// each lane
	}
// mysqldump ok
}

unlink($tempfile);

?>
