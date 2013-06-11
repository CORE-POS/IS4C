<?php
/*
Table: valutecRequest

Columns:
	date int
	cashierNo int
	laneNo int
	transNo int
	transID int
	datetime datetime
	identifier varchar
	terminalID varchar
	live tinyint
	mode varchar
	amount double
	PAN varchar
	manual tinyint

Depends on:
	none

Use:
This table logs information that is
sent to a gift-card payment gateway.
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
sale or balance check. Exact value can vary from gateway
to gateway.

PAN is the cardnumber. Storing the whole thing
doesn't really matter for gift cards.

manual indicates a typed-in card number. Otherwise,
the assumption is you sent track data.

identifier and terminalID are historically related
to the Valutec gateway. Other modules can use
these fields for anything.
*/
$CREATE['trans.valutecRequest'] = "
	CREATE TABLE valutecRequest (
		date int,
		cashierNo int,
		laneNo int,
		transNo int,
		transID int,
		datetime datetime,
		identifier varchar(10),
		terminalID varchar(20),
		live tinyint,
		mode varchar(32),
		amount double,
		PAN varchar(19),
		manual tinyint
	)
";
?>
