<?php
/*
Table: suspensions

Columns:
    cardno int
    type char
    memtype1 int
    memtype2 varchar
    reason text
    suspDate datetime
    mailflag int
    discount int
    chargelimit dbms currency
    reasoncode int

Depends on:
    custdata (table)

Use:
suspensions are a way of putting a membership on
hold. When an account is suspended, it reverts
to the lowest possible privileges and custdata's
settings for Type, memType, Discount, and 
ChargeLimit are stored here in memtype1, memtype2,
discount, and chargelimit (respectively). When
the account is restored, custdata's original settings
are repopulated from these saved values.

type currently contains 'I' (inactive memberships
that may return) or 'T' (terminated memberships that
will not return).

Historically, the "reason" field was used to manually
enter a reason for the suspension. Using the reasoncode
is now preferred. This field is interpretted as binary
using masks from the reasoncodes table to determine
which reason(s) have been given.
*/
$CREATE['op.suspensions'] = "
    CREATE TABLE suspensions (
        cardno int,
        type char(1),
        memtype1 int,
        memtype2 varchar(6),
        suspDate datetime,
        reason text,
        mailflag int,
        discount int,
        chargelimit ".$con->currency().",
        reasoncode int,
        PRIMARY KEY (cardno)
    )
";
?>
