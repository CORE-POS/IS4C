<?php
/*
Table: prodDepartmentHistory

Columns:
    prodDepartmentHistoryID int
    upc varchar(13)
    modified datetime
    dept_ID int
    uid int
    prodUpdateID int

Depends on:
    prodUpdate (table)

Use:
This table holds a compressed version of prodUpdate.
A entry is only made when an item's department setting
changes. uid is the user who made the change.
*/
$CREATE['op.prodDepartmentHistory'] = "
    CREATE TABLE prodDepartmentHistory (
        prodDepartmentHistoryID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        upc varchar(13),
        modified datetime,
        dept_ID int,
        uid int,
        prodUpdateID BIGINT UNSIGNED,
        PRIMARY KEY (prodDepartmentHistoryID),
        INDEX (prodUpdateID)
    )
";
if ($dbms == "MSSQL") {
    $CREATE['op.prodDepartmentHistory'] = "
        CREATE TABLE prodDepartmentHistory (
            prodDepartmentHistoryID BIGINT IDENTITY (1, 1) NOT NULL ,
            upc varchar(13),
            modified datetime,
            dept_ID int,
            uid int,
            prodUpdateID BIGINT UNSIGNED
        )
    ";
}

