<?php
/*
Table: cronBackup

Columns:
    modified datetime
    file_contents text

Depends on:
    none

Use:
*/
$CREATE['op.cronBackup'] = "
    CREATE TABLE cronBackup (
        modified datetime,
        file_contents text
    )
";
?>
