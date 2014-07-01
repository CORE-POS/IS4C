<?php
/*
Table: custAvailablePrefs

Columns:
    pref_key varchar
    pref_default_value varchar
    pref_description

Depends on:
    none

Use:
List of available customer preferences
*/
$CREATE['op.custAvailablePrefs'] = "
    CREATE TABLE custAvailablePrefs (
        custAvailablePrefID int not null auto_increment,
        pref_key varchar(50),
        pref_default_value varchar(100),
        pref_description text,
        PRIMARY KEY (pref_key),
        INDEX(custAvailablePrefID)
    )
";
?>
