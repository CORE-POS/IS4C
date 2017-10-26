<?php

function dbconnect(){
    if (!class_exists("SQLManager")) require(__DIR__ . "/../../../src/SQLManager.php");
    include(__DIR__ . '/../../../src/Credentials/recipizer.wfcdb.php');    
    return $sql;
}

