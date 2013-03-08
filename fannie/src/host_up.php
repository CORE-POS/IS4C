<?php

function check_db_host($host,$dbms){
	if (!function_exists("socket_create"))
		return True; // test not possible

	$port = 0;
	switch(strtoupper($dbms)){
	case 'MYSQL':
	case 'MYSQLI':
	case 'PDO_MYSQL':
		$port = 3306;
		break;
	case 'MSSQL':
		$port = 1433;
		break;	
	case 'PGSQL':
		$port = 5432;
		break;
	}

	if (strstr($host,":"))
		list($host,$port) = explode(":",$host);

	$test = False;
	$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0)); 
	socket_set_block($sock);
	try {
		$test = @socket_connect($sock,$host,$port);
	}
	catch(Exception $ex) {}
	socket_close($sock);

	return ($test ? True : False);	
}

?>
