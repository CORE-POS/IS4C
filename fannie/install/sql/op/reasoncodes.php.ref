<?php
/*
Table: reasoncodes

Columns:
    textStr varchar
    mask int

Depends on:
    suspensions

Use:
Reason code work in conjunction with suspended
memberships. The mask here is a bitmask. This
lets you tag the suspensions.reasonCode with
multiple reasons in one field. Probably not the
most "SQL-y" way of doing things.
*/
$CREATE['op.reasoncodes'] = "
    CREATE TABLE reasoncodes (
        textStr varchar(100),
        mask int,
        PRIMARY KEY (mask)
    )
";
?>
