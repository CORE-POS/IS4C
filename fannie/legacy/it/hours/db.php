<?php

function hours_dbconnect(){
    if (!class_exists('FannieAPI'))
        include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
    if (!class_exists("SQLManager"))
        require(__DIR__ . '/../../../src/SQLManager.php');
    return FannieDB::get('HoursTracking');
}

