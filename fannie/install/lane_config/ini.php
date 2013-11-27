<?php
include('../../config.php');
if (!class_exists('FannieAPI')) {
        include(dirname(__FILE__).'/../classlib2.0/FannieAPI.php');
}
$CORE_PATH = realpath($FANNIE_ROOT.'../pos/is4c-nf/').'/';
include($FANNIE_ROOT.'src/SQLManager.php');

/*
 This is not a real lane ini file
 It mimics the ini-writing behavior at the lane
 but stores the results in the database. This means
 the browser configuration code works on the lane
 level or the server level without much change
*/

class cl_wrapper {
	var $CL;
	function cl_wrapper(){
		$this->CL = array();
	}
	function get($k){
		return (isset($this->CL["$k"])) ? $this->CL["$k"] : "";
	}
	function set($k,$v){
		$this->CL["$k"] = $v;
	}
}

function confsave($k,$v){
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
	if (is_string($v))
		$v = trim($v,"'");
	$p = $dbc->prepare_statement("SELECT value FROM lane_config WHERE keycode=?");
	$r = $dbc->exec_statement($p,array($k));
	if ($dbc->num_rows($r)==0){
		$insP = $dbc->prepare_statement('INSERT INTO lane_config (keycode, value,
				modified) VALUES (?, ?, '.$dbc->now().')');
		$dbc->exec_statement($insP,array($k,serialize($v)));
	}
	else {
		$current = array_pop($dbc->fetch_row($r));
		if ($v !== unserialize($current)){
			$upP = $dbc->prepare_statement('UPDATE lane_config SET value=?,
				modified='.$dbc->now().' WHERE keycode=?');
			$dbc->exec_statement($upP, array(serialize($v),$k));
		}	
	}
}

$CORE_LOCAL = new cl_wrapper();

$q = $dbc->prepare_statement("SELECT keycode,value FROM lane_config");
$r = $dbc->exec_statement($q);
while($w = $dbc->fetch_row($r)){
	$k = $w['keycode'];
	$v = unserialize($w['value']);
	$CORE_LOCAL->set($k,$v);
}

?>
