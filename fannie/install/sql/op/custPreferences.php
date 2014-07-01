<?php
/*
Table: custPreferences

Columns:
    card_no int
    pref_key varchar
    pref_value varchar

Depends on:
    custdata (table)
    custAvailablePrefs (table)

Use:
Store customer-specific preferences
This table supplements custdata and is
available at the lanes.
*/
$CREATE['op.custPreferences'] = "
    CREATE TABLE custPreferences (
        custPreferenceID int not null auto_increment,
        card_no int,
        custAvailablePrefID int,
        pref_key varchar(50),
        pref_value varchar(100),
        PRIMARY KEY (card_no, pref_key),
        INDEX (custPreferenceID)
    )
";
?>
