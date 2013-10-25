<?php
$config = dirname(__FILE__).'/../config.php';
$tokens = token_get_all(file_get_contents($config));
foreach($tokens as $t){
	if ($t[0] != T_VARIABLE) continue;
	
	$name = substr($t[1],1);
	global $$name;
}
include($config);

?>
