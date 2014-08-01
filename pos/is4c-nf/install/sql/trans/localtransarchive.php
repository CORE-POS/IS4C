<?php
/*
Table: localtransarchive

Columns:
	datetime datetime
	register_no int
	emp_no int
	trans_no int
	upc varchar
	description varchar
	trans_type varchar
	trans_subtype varchar
	trans_status varchar
	department smallint
	quantity double
	scale tinyint
	cost currency
	unitPrice currency
	total currency
	regPrice currency
	tax smallint
	foodstamp tinyint
	discount currency
	memDiscount currency
	discounttable tinyint
	discounttype tinyint
	voided tinyint
	percentDiscount tinyint
	ItemQtty double
	volDiscType tinyint
	volume tinyint
	VolSpecial currency
	mixMatch varchar
	matched smallint
	memType tinyint
	staff tinyint
	numflag int
	charflag varchar
	card_no int
	trans_id int

Depends on:
	none

Use:
Lane side storage of transactions older than what's
in localtrans. This could probably go away entirely.
By the data is removed from localtrans it should be
safely archived on the server side anyway.
*/
$CREATE['trans.localtransarchive'] = array(
    InstallUtilities::duplicateStructure($dbms,'dtransactions','localtransarchive'),
    'ALTER TABLE localtransarchive DROP COLUMN pos_row_id',
);

