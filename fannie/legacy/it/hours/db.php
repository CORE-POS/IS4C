<?php

if (!class_exists("SQLManager"))
	require($FANNIE_ROOT.'src/SQLManager.php');

function hours_dbconnect(){
	global $FANNIE_ROOT;
	include($FANNIE_ROOT.'src/Credentials/HoursDB.wfc.php');
	return $hoursdb;
}


?>
