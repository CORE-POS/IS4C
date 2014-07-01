<?php
/*
Table: deptMargin

Columns:
    dept_ID int
    margin decimal(10,5)

Depends on:
    departments (table)

Use:
This table has a desired margin for each
department. 

This table is deprecated. Please use
the margin column in the departments table.
*/
$CREATE['op.deptMargin'] = "
    CREATE TABLE deptMargin (
        dept_ID int,
        margin decimal(10,5),
        PRIMARY KeY (dept_ID)
    )
";
?>
