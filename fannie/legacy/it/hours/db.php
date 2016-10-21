<?php

function hours_dbconnect(){
    global $FANNIE_ROOT;
    if (!class_exists('FannieAPI'))
        include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
    if (!class_exists("SQLManager"))
        require($FANNIE_ROOT.'src/SQLManager.php');
    return FannieDB::get('HoursTracking');
}

