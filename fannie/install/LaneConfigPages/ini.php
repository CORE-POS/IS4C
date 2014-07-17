<?php
/* PHP manual: "Although display_errors may be set at runtime (with ini_set()),
 * it won't have any affect if the script has fatal errors.
 * This is because the desired runtime action does not get executed."
 * It does in fact show at least some fatals.
 * Does it show warnings?
 * Should only be used when debugging.
ini_set('display_errors','1');
*/
include('../../config.php');
$CORE_PATH = realpath($FANNIE_ROOT.'../pos/is4c-nf/').'/';
include($FANNIE_ROOT.'src/SQLManager.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
$dbc = FannieDB::get($FANNIE_TRANS_DB);

/*
 This is not a real lane ini file.
 It mimics the ini-writing behavior at the lane
 but stores the results in the database. This means
 the browser configuration code works on the lane
 level or the server level without much change.

 Initially, populate the array in the instance of cl_wrapper $CORE_LOCAL
  with keys+values from the database
 The calling page assigns form control values from there.
 On submit of the form, add new key+values to the database
  and update values that are different from current db values.
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

/* Save new key+value pairs and changed values to the database.
 * Look for the key+value in the database.
 *  If key does not exist, add key+value to the database.
 *  If key exists and the value from the form is different
 *   change the value in the database.
*/
function confsave($k,$v){
    global $FANNIE_TRANS_DB;
    $dbc = FannieDB::get($FANNIE_TRANS_DB);
    if (is_string($v))
        $v = trim($v,"'");
    $p = $dbc->prepare_statement("SELECT value FROM $FANNIE_TRANS_DB.lane_config WHERE keycode=?");
    $r = $dbc->exec_statement($p,array($k));
    if ($dbc->num_rows($r)==0){
        $insP = $dbc->prepare_statement("INSERT INTO $FANNIE_TRANS_DB.lane_config (keycode, value,
                modified) VALUES (?, ?, ".$dbc->now().")");
        $dbc->exec_statement($insP,array($k,serialize($v)));
    }
    else {
        $current = array_pop($dbc->fetch_row($r));
        if ($v !== unserialize($current)){
            $upP = $dbc->prepare_statement('UPDATE '.$FANNIE_TRANS_DB.'.lane_config SET value=?,
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
