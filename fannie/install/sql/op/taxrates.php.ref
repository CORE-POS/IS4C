<?php
/*
Table: taxrates

Columns:
    id int
    rate float
    description varchar

Depends on:
    none

Use:
List of applicable tax rates. By convention,
id 0 should be used for untaxed goods. Only
one rate may be applied, so you may have more
entries here than there are local tax rates
if those rates don't stack cleanly.
*/
$CREATE['op.taxrates'] = "
    CREATE TABLE taxrates (
        id int,
        rate float,
        description varchar(30),
        PRIMARY KEY(id)
    )
";
?>
