<?php

/* WFC Report Caching - not configurarable at the moment*/

/*
if (!class_exists("SQLManager"))
	require($FANNIE_ROOT."src/SQLManager.php");

function db(){
	return new SQLManager('192.168.1.3','PGSQL','html_cache','wfc_pos','is4c');
}
*/

function get_cache($type){
	global $FANNIE_ROOT;
	$type = strtolower($type);
	
	// match type
	if ($type[0]=='m') $type='monthly';
	elseif($type[0]=='d') $type='daily';
	else return False;

	$key = md5($_SERVER['REQUEST_URI']);

	if (file_exists($FANNIE_ROOT.'cache/cachefilesi/'.$type.'/'.$key))
		return file_get_contents($FANNIE_ROOT.'cache/cachefiles/'.$type.'/'.$key);
	else
		return False;

	/*
	$table = $type."_cache";
	$sql = db();

	$checkQ = "SELECT content FROM $table WHERE cache_id='$key'";
	$checkR = $sql->query($checkQ);
	if ($sql->num_rows($checkR) == 0)
		return False;
	else
		return array_pop($sql->fetch_row($checkR));
	*/
}

function put_cache($type,$content){
	global $FANNIE_ROOT;
	$type = strtolower($type);

	// match type
	if ($type[0]=='m') $type='monthly';
	elseif($type[0]=='d') $type='daily';
	else return False;

	$key = md5($_SERVER['REQUEST_URI']);
	$fp = fopen($FANNIE_ROOT.'cache/cachefiles/'.$type.'/'.$key,'w');
	fwrite($fp,$content);
	fclose($fp);
	/*
	$table = $type."_cache";
	$sql = db();
	$content = $sql->escape($content);

	$sql->query("DELETE FROM $table WHERE cache_id='$key'");
	$insQ = "INSERT INTO $table VALUES ('$key',$content)";
	$sql->query($insQ);
	*/
}

?>
