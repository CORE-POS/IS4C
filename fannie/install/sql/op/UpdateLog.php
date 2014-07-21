<?php
/*
Table: UpdateLog

Columns:
    id varchar
    status tinyint
    tdate datetime

Depends on:
    none

Use:
*/
$CREATE['op.UpdateLog'] = "
    create table UpdateLog (id varchar(14),
        status tinyint,
        tdate datetime,
        PRIMARY KEY (id)
    )
";
?>
