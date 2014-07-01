<?php
/*
View: batchPriority

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
    batchPriority10 (view)
    batchPriority0 (view)

Use:
This view combines the other
batchPriority views to show the
highest-priority batch info for
each item that's currently on sale
*/
$CREATE['op.batchPriority'] = "
CREATE VIEW batchPriority AS
SELECT * FROM batchPriority0
UNION ALL 
SELECT * FROM batchPriority10
UNION ALL 
SELECT * FROM batchPriority20
UNION ALL 
SELECT * FROM batchPriority30
";

?>
