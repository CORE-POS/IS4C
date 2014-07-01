<?php
/*
Table: customReports

Columns:
    reportID int
    reportName varchar
    reportQuery text

Depends on:
    none

Use:
Save queries for later use
as reports
*/
$CREATE['op.customReports'] = "
    CREATE TABLE customReports (
        reportID int,
        reportName varchar(50),
        reportQuery text,
        primary key (reportID)
    )
";
?>
