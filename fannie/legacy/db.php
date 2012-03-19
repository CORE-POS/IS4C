<?php
$dbc = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
		$FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
$sql = $dbc;

if (!function_exists('add_second_server')){
	function add_second_server(){
		return;
	}
}

?>
