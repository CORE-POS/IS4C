<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

// set a variable in the config file
function confset($key, $value){
	global $FILEPATH;
	$lines = array();
	$found = False;
	$fp = fopen($FILEPATH.'config.php','r');
	while($line = fgets($fp)){
		if (strpos($line,"\$$key ") === 0){
			$lines[] = "\$$key = $value;\n";
			$found = True;
		}
		elseif (strpos($line,"?>") === 0 && $found == False){
			$lines[] = "\$$key = $value;\n";
			$lines[] = "?>\n";
		}
		else
			$lines[] = $line;
	}
	fclose($fp);

	$fp = fopen($FILEPATH.'config.php','w');
	foreach($lines as $line)
		fwrite($fp,$line);
	fclose($fp);
}

function db_test_connect($host,$type,$db,$user,$pw){
	global $FANNIE_ROOT;
	if (!function_exists("check_db_host"))
		include($FANNIE_ROOT.'src/host_up.php');
	if (!check_db_host($host,$type))
		return False;

	if (!class_exists('SQLManager'))
		include($FANNIE_ROOT.'src/SQLManager.php');
	$sql = False;
	try {
		$sql = new SQLManager($host,$type,$db,$user,$pw);
	}
	catch(Exception $ex) {}
	
	if ($sql === False || $sql->connections[$db] === False)
		return False;
	else
		return $sql;
}

?>
