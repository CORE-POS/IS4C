<?php
/*
Table: superdepts

Columns:
    superID int
    dept_ID

Depends on:
    departments (table)

Use:
Super departments contain departments. A department
may belong to multiple super departments, although
every department has one "master" super department
for the purpose of some reporting (by convention
the one with the lowest superID).

This is just an extra level of granularity to group
departments together when they're often all collected
in the same report, maintained by the same buyer, etc
*/
$CREATE['op.superdepts'] = "
    CREATE TABLE superdepts (
        superID int,
        dept_ID int,
        PRIMARY KEY (superID, dept_ID),
        INDEX(superID),
        INDEX(dept_ID)
    )
";
?>
