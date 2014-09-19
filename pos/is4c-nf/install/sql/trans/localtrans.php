<?php
/*
Table: localtrans

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
Lane-side record of historical transactions.
See dtransactions for details on what the columns
are used for.
*/
$CREATE['trans.localtrans'] = array(
    InstallUtilities::duplicateStructure($dbms,'dtransactions','localtrans'),
    'ALTER TABLE localtrans DROP COLUMN pos_row_id',
);

?>
