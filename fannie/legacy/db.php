<?php
if (!isset($FANNIE_SERVER)) {
    include(dirname(__FILE__).'/../config.php');
}
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__).'/../classlib2.0/FannieAPI.php');
}
$dbc = FannieDB::get($FANNIE_OP_DB);
$sql = $dbc;

if (!function_exists('add_second_server')){
    function add_second_server(){
        return;
    }
}

