<?php

if (!class_exists("SQLManager"))
    require(__DIR__ . '/../../../src/SQLManager.php');
function db_connect(){
    include(__DIR__ . '/../../../src/Credentials/AppTracking.db.php');
    return $appdb;
}

