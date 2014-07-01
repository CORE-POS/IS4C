<?php
/*
Table: tenders

Columns:
    TenderID smallint
    TenderCode varchar
    TenderName varchar
    TenderType varchar
    ChangeMessage varchar
    MinAmount double
    MaxAmount double
    MaxRefund double

Depends on:
    none

Use:
List of tenders IT CORE accepts. TenderCode
should be unique; it's what cashiers type in
at the register as well as the identifier that
eventually shows up in transaction logs.

ChangeMessage, MinAmount, MaxAmount, and
MaxRefund all do exactly what they sound like.

TenderName shows up at the register screen
and on various reports.

TenderType and TenderID are mostly ignored.
*/
$CREATE['op.tenders'] = "
    CREATE TABLE tenders (
        TenderID smallint,
        TenderCode varchar(255),
        TenderName varchar(255),
        TenderType varchar(255),
        ChangeMessage varchar(255),
        MinAmount double,
        MaxAmount double,
        MaxRefund double,
        PRIMARY KEY (TenderID),
        INDEX (TenderCode)
    )
";
?>
