<?php
/*
Table: efsnetRequest

Columns:
    date int
    cashierNo int
    laneNo int
    transNo int
    transID int
    datetime datetime
    refNum varchar
    live tinyint
    mode varchar
    amount double
    PAN varchar
    issuer varchar
    name varchar
    manual tinyint
    sentPAN tinyint
    sentExp tinyint
    sentTr1 tinyint
    sentTr2 tinyint 
    efsnetRequestID int

Depends on:
    none

Use:
This table logs information that is
sent to a credit-card payment gateway.
All current paycard modules use this table
structure. Future ones don't necessarily have
to, but doing so may enable more code re-use.

Some column usage may vary depending on a
given gateway's requirements and/or formatting,
but in general:

cashierNo, laneNo, transNo, and transID are
equivalent to emp_no, register_no, trans_no, and
trans_id in dtransactions (respectively).

mode indicates the type of transaction, such as
refund or sale. Exact value can vary from gateway
to gateway.

PAN is the cardnumber - for the love of $deity
only save the last 4 digits here - issuer is
Visa, MC, etc, and name is the cardholder's name
(if available).

The sent* columns indicate which information was
sent. Most gateways will accept PAN + expiration
date, or either track. Sending both tracks is
usually fine; I've never seen a system where
you send all 4 pieces of card info.

efsnetRequestID is an incrementing ID columns. This
is unique at a lane level but not an overall system
level since different lanes will increment through
the same ID values. The combination of laneNo and
efsnetRequestID should be unique though.
*/
$CREATE['trans.efsnetRequest'] = "
    CREATE TABLE efsnetRequest (
        date int ,
        cashierNo int ,
        laneNo int ,
        transNo int ,
        transID int ,
        datetime datetime ,
        refNum varchar (50) ,
        live tinyint ,
        mode varchar (32) ,
        amount double ,
        PAN varchar (19) ,
        issuer varchar (16) ,
        name varchar (50) ,
        manual tinyint ,
        sentPAN tinyint ,
        sentExp tinyint ,
        sentTr1 tinyint ,
        sentTr2 tinyint ,
        efsnetRequestID INT
    )
";
?>
