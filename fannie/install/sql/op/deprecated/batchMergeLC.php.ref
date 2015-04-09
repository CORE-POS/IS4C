<?php
/*
View: batchMergeLC

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
    upcLike (table)

Use:
This view just feeds into batchMergeTable
*/
$CREATE['op.batchMergeLC'] = "
    CREATE VIEW batchMergeLC AS
    SELECT b.startDate, b.endDate, p.upc, p.description, b.batchID
    FROM batchList AS l LEFT JOIN batches AS b
    ON b.batchID=l.batchID INNER JOIN upcLike AS u
    ON l.upc = concat('LC',convert(u.likeCode,char))
    INNER JOIN products AS p ON u.upc=p.upc
    WHERE p.upc IS NOT NULL
";

if ($dbms == "MSSQL"){
    $CREATE['op.batchMergeLC'] = "
        CREATE VIEW batchMergeLC AS
        SELECT b.startDate, b.endDate, p.upc, p.description, b.batchID
        FROM batchList AS l LEFT JOIN batches AS b
        ON b.batchID=l.batchID INNER JOIN upcLike AS u
        ON l.upc = 'LC'+convert(varchar,u.likeCode)
        INNER JOIN products AS p ON u.upc=p.upc
        WHERE p.upc IS NOT NULL
    ";
}

?>
