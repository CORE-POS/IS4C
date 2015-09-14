<?php
/*
Table: likeCodes

Columns:
    likeCode int
    likeCodeDesc varchar

Depends on:
    upcLike (table)

Use:
Like Codes group sets of items that will always
have the same price. It's mostly used for produce,
but could be applied to product lines, too
(e.g., all Clif bars)

The actual likeCode => upc mapping is in upcLike
*/
$CREATE['op.likeCodes'] = "
    CREATE TABLE likeCodes (
        likeCode int,
        likeCodeDesc varchar(50),
        PRIMARY KEY(likeCode)
    )
";
?>
