<?php
/*
View: rp_receipt_header_90

Columns:
	dateTimeStamp datetime
	memberID int
	trans_num (calculated)
	register_no int
	emp_no int
	trans_no int
	discountTTL (calculated)
	memSpecial (calculated)
	couponTotal (calculated)
	memCoupon (calculated)
	chargeTotal (calculated)
	transDiscount (calculated)
	tenderTotal (calculated)

Depends on:
	transarchive (table)

Use:
This view pulls per-transaction info
from transarchive for
reprinting receipts (in the "old" style).
*/
$CREATE['trans.rp_receipt_header_90'] = "
	create  view rp_receipt_header_90 as
		select
		datetime as dateTimeStamp,
		card_no as memberID,
		concat(convert(emp_no,char), '-', convert(register_no,char), '-', convert(trans_no,char)) as trans_num,
		register_no,
		emp_no,
		trans_no,
		convert(sum(case when discounttype = 1 then discount else 0 end),decimal(10,2)) as discountTTL,
		convert(sum(case when discounttype = 2 then memDiscount else 0 end),decimal(10,2)) as memSpecial,
		convert(sum(case when upc = '0000000008005' then total else 0 end),decimal(10,2)) as couponTotal,
		convert(sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end),decimal(10,2)) as memCoupon,
		abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
		sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
		sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal
		from transarchive
		group by register_no, emp_no, trans_no, card_no, datetime
";

if ($dbms == "MSSQL"){
	$CREATE['trans.rp_receipt_header_90'] = "
		create  view rp_receipt_header_90 as
			select
			datetime as dateTimeStamp,
			card_no as memberID,
			(convert(varchar,emp_no) +  '-' + convert(varchar,register_no) + '-' + convert(varchar,trans_no)) as trans_num,
			register_no,
			emp_no,
			trans_no,
			convert(numeric(10,2), sum(case when discounttype = 1 then discount else 0 end)) as discountTTL,
			convert(numeric(10,2), sum(case when discounttype = 2 then memDiscount else 0 end)) as memSpecial,
			convert(numeric(10,2), sum(case when upc = '0000000008005' then total else 0 end)) as couponTotal,
			convert(numeric(10,2), sum(case when upc = 'MEMCOUPON' then unitPrice else 0 end)) as memCoupon,
			abs(sum(case when trans_subtype = 'MI' or trans_subtype = 'CX' then total else 0 end)) as chargeTotal,
			sum(case when upc = 'Discount' then total else 0 end) as transDiscount,
			sum(case when trans_type = 'T' then -1 * total else 0 end) as tenderTotal
			from transarchive
			group by register_no, emp_no, trans_no, card_no, datetime
	";
}
?>
