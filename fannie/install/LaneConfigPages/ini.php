<?php
/**
  This is a fake ini file for the LANE configuration
  It's in this directory instead of LaneConfigPages
  to make relative paths work
*/

if (!isset($CORE_LOCAL)) {

class FakeLaneSession
{
    private $backing = array();

    public function get($key)
    {
        return isset($this->backing[$key]) ? $this->backing[$key] : '';
    }

    public function set($key, $val, $immutable=true)
    {
        $this->backing[$key] = $val;
    }

}

$CORE_LOCAL = new FakeLaneSession();

$CORE_LOCAL->set('laneno', 0);
if (!isset($FANNIE_SERVER)) {
    include_once(dirname(__FILE__) . '/../../config.php');
}
$CORE_LOCAL->set('localhost', $FANNIE_SERVER);
$CORE_LOCAL->set('DBMS', strtolower(str_replace('_','',$FANNIE_SERVER_DBMS)));
$CORE_LOCAL->set('localUser', $FANNIE_SERVER_USER);
$CORE_LOCAL->set('localPass', $FANNIE_SERVER_PW);
$CORE_LOCAL->set('pDatabase', $FANNIE_OP_DB);
$CORE_LOCAL->set('tDatabase', $FANNIE_TRANS_DB);

ini_set('error_log', dirname(__FILE__) . '/../../logs/php-errors.log');

}
