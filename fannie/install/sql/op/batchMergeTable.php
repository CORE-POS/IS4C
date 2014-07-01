<?php
/*
Table: batchMergeTable

Columns:
    startDate datetime
    endDate datetime
    upc varchar or int, dbms dependent
    description varchar
    batchID int

Depends on:
    batchMergeProd
    batchMergeLC

Use:
This is a speedup table for reports. It's
populated (daily) from the views batchMergeProd
and batchMergeLC. It unrolls likecoded batchList
entries back into upcs which simplifies subsequent
queries. At WFC batchList is also a bit large
and slow to join against directly. 
*/

$CREATE['op.batchMergeTable'] = "
    CREATE TABLE batchMergeTable (
        startDate datetime,
        endDate datetime,
        upc varchar(13),
        description varchar(30),
        batchID int,
        PRIMARY KEY (batchID,upc),
        INDEX (upc),
        INDEX (batchID)
    )
";

?>
