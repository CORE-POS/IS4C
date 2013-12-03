<?php
/*
Table: localtrans_today

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
Contains today's transactions. Access is generally
via the view localtranstoday that enforces the
today-only restriction but truncating this table
daily will yield better performance on some actions
that reference the current day's info - for example,
reprinting receipts.
*/
$CREATE['trans.localtrans_today'] = InstallUtilities::duplicateStructure($dbms,'dtransactions','localtrans_today');

if ($CREATE['trans.localtrans_today'] !== false) {
    $CREATE['trans.localtrans_today'] = array(
                                            $CREATE['trans.localtrans_today'],
                                            'ALTER TABLE localtrans_today ADD INDEX (trans_no)',
                                            'ALTER TABLE localtrans_today ADD INDEX (datetime)',
                                            );
}

?>
