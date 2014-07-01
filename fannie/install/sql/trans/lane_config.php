<?php
/*
Table: lane_config

Columns:
    keycode varchar
    value varchar
    modified datetime

Depends on:
    none

Use:
Store settings for lane ini.php
globally
*/
$CREATE['trans.lane_config'] = "
    CREATE TABLE lane_config (
    keycode varchar(255),
    value varchar(255),
    modified datetime,
    PRIMARY KEY (keycode)
    )
";
?>
