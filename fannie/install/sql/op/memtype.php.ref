<?php
/*
Table: memtype

Columns:
    memtype tinyint
    memDesc varchar
    custdataType varchar
    discount int
    staff int
    ssi int

Depends on:
    none

Use:
Housekeeping. If you want to sort people in
custdata into more categories than just
member/nonmember, use memtype.

The custdataType, discount, staff, and ssi
are the default values for custdata's
Type, discount, staff, and ssi columns
when creating a new record of a given
memtype.
*/
$CREATE['op.memtype'] = "
    CREATE TABLE memtype (
        memtype tinyint,
        memDesc varchar(20),
        custdataType varchar(10),
        discount smallint,
        staff tinyint,
        ssi tinyint,
        primary key (memtype)
    )
";

