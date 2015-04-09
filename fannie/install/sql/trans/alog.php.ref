<?php
/*
Table: alog

Columns:
    datetime datetime
    LaneNo int
    CashierNo int
    TransNo int
    Activity int
    Interval double

Depends on:
    none

Use:
This table logs cashier activities. I think
Interval is time between activities and
Activity specifies what exactly the cashier did,
but I don't know what the various codings mean.
WFC does not use this table for anything.
*/
$CREATE['trans.alog'] = "
    CREATE TABLE alog (
    `datetime` datetime,
    LaneNo smallint,
    CashierNo smallint,
    TransNo int,
    Activity tinyint,
    `Interval` double
    )
";
if ($dbms == 'MSSQL'){
    $CREATE['trans.alog'] = str_replace("`datetime`","[datetime]",$CREATE['trans.alog']);
    $CREATE['trans.alog'] = str_replace("`","",$CREATE['trans.alog']);
}
?>
