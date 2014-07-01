<?php
/*
View: batchMergeProd

Columns:
    startDate datetime
    endDate datetime
    upc varchar
    description varchar
    batchID int

Depends on:
    batches (table)
    batchList (table)
    products (table)

Use:
This view just feeds into batchMergeTable
*/
$CREATE['op.batchMergeProd'] = "
    CREATE VIEW batchMergeProd AS
        SELECT b.startDate,b.endDate,p.upc,p.description,b.batchID
        FROM batches AS b LEFT JOIN batchList AS l
        ON b.batchID=l.batchID INNER JOIN products AS p
        ON p.upc = l.upc
";
?>
