<?php
/*
Table: prodPriceHistory

Columns:
    prodPriceHistory int
    upc varchar(13)
    modified datetime
    price decimal(10,2)
    uid int
    prodUpdateID int

Depends on:
    prodUpdate (table)

Use:
This table holds a compressed version of prodUpdate.
A entry is only made when an item's regular price setting
changes. uid is the user who made the change.
*/
$CREATE['op.prodPriceHistory'] = "
    CREATE TABLE prodPriceHistory (
        prodPriceHistoryID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        upc varchar(13),
        modified datetime,
        price decimal(10,2),
        uid int,
        prodUpdateID BIGINT UNSIGNED,
        PRIMARY KEY (prodPriceHistoryID),
        INDEX (prodUpdateID)
    )
";
if ($dbms == "MSSQL") {
    $CREATE['op.prodPriceHistory'] = "
        CREATE TABLE prodPriceHistory (
            prodPriceHistoryID BIGINT IDENTITY (1, 1) NOT NULL ,
            upc varchar(13),
            modified datetime,
            price decimal(10,2),
            uid int,
            prodUpdateID BIGINT
        )
    ";
}

