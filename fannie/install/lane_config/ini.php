<?php
include('../../config.php');
$CORE_PATH = realpath($FANNIE_ROOT.'../pos/is4c-nf/').'/';
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'src/trans_connect.php');

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
	global $dbc;
	if (is_string($v))
		$v = trim($v,"'");
	$r = $dbc->query("SELECT value FROM lane_config WHERE keycode='$k'");
	if ($dbc->num_rows($r)==0){
		$dbc->query(sprintf("INSERT INTO lane_config (keycode,value,modified)
			VALUES (%s,%s,%s)",
			$dbc->escape($k),
			$dbc->escape(serialize($v)),
			$dbc->now()
		));
	}
	else {
		$current = array_pop($dbc->fetch_row($r));
		if ($v !== unserialize($current)){
			$dbc->query(sprintf("UPDATE lane_config SET value=%s, modified=%s
				WHERE keycode=%s",
				$dbc->escape(serialize($v)),
				$dbc->now(),
				$dbc->escape($k)
			));
		}	
	}
}

$CORE_LOCAL = new cl_wrapper();

$q = "SELECT keycode,value FROM lane_config";
$r = $dbc->query($q);
while($w = $dbc->fetch_row($r)){
	$k = $w['keycode'];
	$v = unserialize($w['value']);
	$CORE_LOCAL->set($k,$v);
}

?>
