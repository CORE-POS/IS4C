<?php
/*
Table: upcLike

Columns:
    upc varchar or int, dbms dependent
    likeCode int

Depends on:
    likeCodes (table)

Use:
Lists the items contained in each like code
*/
$CREATE['op.upcLike'] = "
    CREATE TABLE `upcLike` (
        `upc` varchar(13),
        `likeCode` int default NULL,
        PRIMARY KEY (`upc`)
    )
"; 

if ($dbms == "MSSQL"){
    $CREATE['op.upcLike'] = "
    CREATE TABLE upcLike (
        upc varchar(13),
        likeCode int
    )
    ";
}

?>
