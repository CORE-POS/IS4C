<?php
/*
View: staffdiscountadd

Columns:
	identical to dtransactions

Depends on:
	localtemptrans (table)

Use:
This view calculates staff discounts on items
in the transaction that have not yet been applied.
These records are then inserted into localtemptrans
to apply the relevant discount(s).
*/
$CREATE['trans.staffdiscountadd'] = "
	CREATE VIEW staffdiscountadd AS
		select max(datetime) AS datetime,
		register_no AS register_no,
		emp_no AS emp_no,
		trans_no AS trans_no,
		upc AS upc,
		description AS description,
		'I' AS trans_type,
		'' AS trans_subtype,
		'S' AS trans_status,
		max(department) AS department,
		1 AS quantity,
		0 AS scale,
		0 AS cost,
		(-(1) * sum(memDiscount)) AS unitPrice,
		(-(1) * sum(memDiscount)) AS total,
		(-(1) * sum(memDiscount)) AS regPrice,
		max(tax) AS tax,
		max(foodstamp) AS foodstamp,
		0 AS discount,
		(-(1) * sum(memDiscount)) AS memDiscount,
		3 AS discountable,
		40 AS discounttype,
		8 AS voided,
		MAX(percentDiscount) as percentDiscount,
		0 AS ItemQtty,0 AS volDiscType,
		0 AS volume,0 AS VolSpecial,
		0 AS mixMatch,0 AS matched,
		MAX(memType) as memType,
		MAX(staff) as staff,
		0 as numflag,
		'' as charflag,
		card_no AS card_no 
		from localtemptrans 
		where (((discounttype = 4) and (unitPrice = regPrice)) or (trans_status = 'S')) 
		group by register_no,emp_no,trans_no,upc,description,card_no having (sum(memDiscount) <> 0)
";
?>
