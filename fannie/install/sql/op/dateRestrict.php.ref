<?php
/*
Table: dateRestrict

Columns:
    upc varchar
    dept_ID int
    restrict_date date
    restrict_dow smallint
    restrict_start time
    restrict_end time

Depends on:
    products (table)
    departments (table)
Use:
Store restrictions for selling products at
certain dates & times. Restrictions can be specified
by UPC or department number as well as by 
exact date or day of week. If start and end
times are entered, restriction will only apply
during that span
*/
$CREATE['op.dateRestrict'] = "
    CREATE TABLE dateRestrict (
        upc varchar(13),
        dept_ID int,
        restrict_date date default null,
        restrict_dow smallint default null,
        restrict_start time default null,
        restrict_end time default null,
        INDEX (upc),
        INDEX (dept_ID)
    )
";
?>
