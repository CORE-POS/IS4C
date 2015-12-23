<?php

function dbconnect(){
    global $FANNIE_ROOT;
    if (!class_exists("SQLManager")) require_once($FANNIE_ROOT."src/SQLManager.php");
    include($FANNIE_ROOT.'src/Credentials/recipizer.wfcdb.php');    
    return $sql;
}

