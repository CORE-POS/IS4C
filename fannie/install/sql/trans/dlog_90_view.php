<?php
/*
View: dlog_90_view

Columns:
	tdate datetime
	register_no int
	emp_no int
	trans_no int
	upc varchar
	trans_type varchar
	trans_subtype varchar
	trans_status varchar
	department int
	quantity double
	unitPrice dbms currency
	total dbms currency
	tax int
	foodstamp int
	ItemQtty double
	card_no int
	trans_id int

Depends on:
	transarchive (table)

Use:
This view applies the same restrictions
as dlog but to the table transarchive.
With WFC's dayend polling, transarchive
contains transaction entries from the past
90 days, hence the name of this view.
For queries in the given time frame, using
the view can be faster or simpler than
alternatives.
*/
$CREATE['trans.dlog_90_view'] = "
	CREATE VIEW dlog_90_view AS
		SELECT
		datetime AS tdate,
		register_no,
		emp_no,
		trans_no,
		upc,
		trans_type,
		trans_subtype,
		trans_status,
		department,
		quantity,
		unitPrice,
		total,
		tax,
		foodstamp,
		ItemQtty,
		card_no,
		trans_id,
		".$con->concat(
			$con->convert('emp_no','char'),"'-'",
			$con->convert('register_no','char'),"'-'",
			$con->convert('trans_no','char'),'')
		." as trans_num
		FROM transarchive
		WHERE trans_status NOT IN ('D','X','Z')
		AND emp_no <> 9999 and register_no <> 99
";
?>
