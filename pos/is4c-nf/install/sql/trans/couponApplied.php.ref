<?php
/*
Table: couponApplied

Columns:
	emp_no int
	trans_no int
	quantity float
	trans_id int

Depends on:
	none

Use:
Track which items have had coupons applied
to them. This is primarily to deal with
"free" coupons that can apply to multiple,
differently-priced items ina single transaction.
*/
$CREATE['trans.couponApplied'] = "
	CREATE TABLE couponApplied (
		emp_no INT,
		trans_no INT,
		quantity FLOAT,
		trans_id INT
	)
";
