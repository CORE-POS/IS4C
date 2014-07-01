<?php
/*
Table: reportDataCache

Columns:
    hash_key varchar
    report_data text
    expires datetime

Depends on:
    none

Use:
Caches reporting datasets
*/
$CREATE['arch.reportDataCache'] = "
    CREATE TABLE reportDataCache (
    hash_key varchar(32),
    report_data text,
    expires datetime,
    PRIMARY KEY (hash_key)
    )
";
?>
