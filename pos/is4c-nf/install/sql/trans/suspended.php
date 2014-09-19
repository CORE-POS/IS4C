<?php
/*
Table: suspended

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
Local table for suspending transactions if
server connection is temporarily unavailable
*/
$CREATE['trans.suspended'] = array(
    InstallUtilities::duplicateStructure($dbms,'dtransactions','suspended'),
    'ALTER TABLE suspended DROP COLUMN pos_row_id',
);

