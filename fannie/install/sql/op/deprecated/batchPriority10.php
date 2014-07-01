<?php
/*
Table: batchPriority10

Columns:
    upc varchar(13)
    batchID int
    startDate datetime
    endDate datetime
    discountType int
    salePrice dbms currency
    pricemethod int
    quantity int

Depends on:
    batches (table)
    batchList (table)
    batchPriority30 (view)
    batchPriority20 (view)

Use:
This view lists sale batch info for
current sales with a priority of 10+
(master-default) and aren't in a
higher priority batch
*/
$CREATE['op.batchPriority10'] = "
CREATE VIEW batchPriority10 AS
SELECT l.upc,b.batchID,b.startDate,b.endDate,
b.discountType,l.salePrice,l.pricemethod,
l.quantity FROM batches AS b LEFT JOIN
batchList AS l ON b.batchID=l.batchID
WHERE ".
$con->datediff($con->now(),'b.startDate')." >= 0 AND ".
$con->datediff($con->now(),'b.endDate')." <= 0
AND b.priority >= 10
AND l.upc NOT IN (SELECT upc FROM batchPriority30)
AND l.upc NOT IN (SELECT upc FROM batchPriority20)
";

?>
