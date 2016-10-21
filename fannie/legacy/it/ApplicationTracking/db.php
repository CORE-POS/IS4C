<?php

if (!class_exists("SQLManager"))
    require($FANNIE_ROOT.'src/SQLManager.php');
function db_connect(){
    global $FANNIE_ROOT;
    include($FANNIE_ROOT.'src/Credentials/AppTracking.db.php');
    return $appdb;
}

