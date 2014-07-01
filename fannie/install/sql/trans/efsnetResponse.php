<?php
/*
Table: efsnetResponse

Columns:
    date int
    cashierNo int
    laneNo int
    transNo int
    transID int
    datetime datetime
    refNum varchar
    seconds float
    commErr int
    httpCode int
    validResponse smallint
    xResponseCode varchar
    xResultCode varchar
    xResultMessage varchar
    xTransactionID varchar
    xApprovalNumber varchar
    efsnetRequestID int

Depends on:
    efsnetRequest (table)

Use:
This table logs information that is
returned from a credit-card payment gateway
after sending a [non-void] request.
All current paycard modules use this table
structure. Future ones don't necessarily have
to, but doing so may enable more code re-use.

Some column usage may vary depending on a
given gateway's requirements and/or formatting,
but in general:

cashierNo, laneNo, transNo, and transID are
equivalent to emp_no, register_no, trans_no, and
trans_id in dtransactions (respectively).

seconds, commErr, and httpCode are curl-related
entries noting how long the network request took
and errors that occurred, if any.

the x* columns vary a lot. What to store here 
depends what the gateway returns.
*/
$CREATE['trans.efsnetResponse'] = "
    CREATE TABLE efsnetResponse (
        date int ,
        cashierNo int ,
        laneNo int ,
        transNo int ,
        transID int ,
        datetime datetime ,
        refNum varchar (50),
        seconds float ,
        commErr int ,
        httpCode int ,
        validResponse smallint ,
        xResponseCode varchar (4),
        xResultCode varchar (8), 
        xResultMessage varchar (100),
        xTransactionID varchar (12),
        xApprovalNumber varchar (20),
        efsnetRequestID INT
    )
";
?>
