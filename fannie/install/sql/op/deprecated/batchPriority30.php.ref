<?php
/*
Table: batchPriority30

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

Use:
This view lists sale batch info for
current sales with a priority of 30+
(master-override).
*/
$CREATE['op.batchPriority30'] = "
CREATE VIEW batchPriority30 AS
SELECT l.upc,b.batchID,b.startDate,b.endDate,
b.discountType,l.salePrice,l.pricemethod,
l.quantity FROM batches AS b LEFT JOIN
batchList AS l ON b.batchID=l.batchID
WHERE ".
$con->datediff($con->now(),'b.startDate')." >= 0 AND ".
$con->datediff($con->now(),'b.endDate')." <= 0
AND b.priority >= 30
";

?>
