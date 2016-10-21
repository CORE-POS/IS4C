<?php
/*
Table: efsnetRequestMod

Columns:
    date int
    cashierNo int
    laneNo int
    transNo int
    transID int
    datetime datetime
    origRefNum varchar
    origAmount double
    origTransactionID varchar
    mode varchar
    altRoute tinyint
    seconds float
    commErr int
    httpCode int
    validResponse smallint
    xResponseCode varchar
    xResultCode varchar
    xResultMessage varchar

Depends on:
    efsnetRequest (table)

Use:
This table logs information that is
returned from a credit-card payment gateway 
when modifying an earlier transaction.
Generally, this means some kind of void.
All current paycard modules use this table
structure. Future ones don't necessarily have
to, but doing so may enable more code re-use.

Some column usage may vary depending on a
given gateway's requirements and/or formatting,
but in general:

cashierNo, laneNo, transNo, and transID are
equivalent to emp_no, register_no, trans_no, and
trans_id in dtransactions (respectively).

mode is the operation type. Exact syntax varies
by gateway. Some gateways provide multiple
addresses. Using a different one can be noted
in altRoute.

seconds, commErr, and httpCode are curl-related
entries noting how long the network request took
and errors that occurred, if any.

the x* columns vary a lot. What to store here 
depends what the gateway returns.
*/
$CREATE['trans.efsnetRequestMod'] = "
    CREATE TABLE efsnetRequestMod (
        date int ,
        cashierNo int ,
        laneNo int ,
        transNo int ,
        transID int ,
        datetime datetime ,
        origRefNum varchar (50),
        origAmount double ,
        origTransactionID varchar(12) ,
        mode varchar (32),
        altRoute tinyint ,
        seconds float ,
        commErr int ,
        httpCode int ,
        validResponse smallint ,
        xResponseCode varchar(4),
        xResultCode varchar(4),
        xResultMessage varchar(100)
    )
";
?>
