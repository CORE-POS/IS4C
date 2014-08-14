<?php
/*
Table: memdefaults

Columns:
    memtype tinyint
    cd_type varchar
    discount smallint
    staff tinyint
    SSI tinyint

Depends on:
    memtype (table)
    custdata (table)

Use:
This table contains defaults to use for
some custdata fields when creating a new member
of the given type. cd_type maps to custdata's
Type field; I think the others are obvious.
*/
$CREATE['op.memdefaults'] = "
    CREATE TABLE memdefaults (
        memtype tinyint,
        cd_type varchar(10),
        discount smallint,
        staff tinyint,
        SSI tinyint,
        primary key (memtype)
    )
";
?>
