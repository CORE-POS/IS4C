<?php
/*
View: likeCodeView

Columns:
    likeCode int
    likeCodeDesc varchar
    upc varchar
    normal_price dbms currency

Depends on:
    likeCodes (table)
    upcLike (table)
    products (table)

Use:
This view exists to simplify rolling out
sales batches with WFC's likecode-as-a-upc
convention. Using this just makes subsequent
queries to put items in a batch on sale
a little less ugly
*/
$CREATE['op.likeCodeView'] = "
    CREATE VIEW likeCodeView AS
    SELECT l.likeCode,l.likeCodeDesc,u.upc,p.normal_price
    FROM likeCodes AS l LEFT JOIN upcLike AS u
    ON l.likeCode=u.likeCode LEFT JOIN
    products AS p ON u.upc=p.upc
";
?>
