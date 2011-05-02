<?php
/*
Table: valutecRequestMod

Columns:
	date int
	cashierNo int
	laneNo int
	transNo int
	transID int
	datetime datetime
	origAuthCode varchar
	mode varchar
	seconds float
	commErr int
	httpCode int
	validResponse smallint
	xAuthorized varchar
	xAuthorizationCode varchar
	xBalance varchar
	xErrorMsg varchar

Depends on:
	valutecRequest (table)

Use:
This table logs information that is
returned from a gift-card payment gateway 
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
by gateway. 

seconds, commErr, and httpCode are curl-related
entries noting how long the network request took
and errors that occurred, if any.

the x* columns vary a lot. What to store here 
depends what the gateway returns.
*/
$CREATE['trans.valutecRequestMod'] = "
	CREATE TABLE valutecRequestMod (
		date int,
		cashierNo int,
		laneNo int,
		transNo int,
		transID int,
		datetime datetime,
		origAuthCode varchar(9),
		mode varchar(32),
		seconds float,
		commErr int,
		httpCode int,
		validResponse smallint,
		xAuthorized varchar(5),
		xAuthorizationCode varchar(9),
		xBalance varchar(8),
		xErrorMsg varchar(100)
	)
";
?>
