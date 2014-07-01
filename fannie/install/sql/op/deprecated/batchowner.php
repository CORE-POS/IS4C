<?php
/*
Table: batchowner

Columns:
    batchID int
    owner varchar

Depends on:
    batches

Use:
Pure housekeeping. A batch does not have to
have an owner. WFC uses it to filter down
the list of batches, since $deity forbid anyone
ever delete one when they're done with it...
*/
$CREATE['op.batchowner'] = "
    CREATE TABLE batchowner (
        batchID int,
        owner varchar(50),
        PRIMARY KEY (batchID)
    )
";
?>
