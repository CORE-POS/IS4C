<?php
/*
View: memdiscountadd

Columns:
	identical to dtransactions

Depends on:
	localtemptrans (table)

Use:
This view calculates member discounts on items
in the transaction that have not yet been applied.
These records are then inserted into localtemptrans
to apply the relevant discount(s).
*/
$CREATE['trans.memdiscountadd'] = "
	CREATE VIEW memdiscountadd AS
		select 
		max(datetime) as datetime, 
		register_no, 
		emp_no, 
		trans_no, 
		upc, 
		CASE WHEN volDiscType IN (3,4) THEN 'Set Discount' ELSE description END as description, 
		'I' as trans_type, 
		'' as trans_subtype,
		'M' as trans_status, 
		max(department) as department, 
		1 as quantity, 
		0 as scale, 
		0 as cost,
		-1 * sum(memDiscount) as unitPrice, 
		-1 * sum(memDiscount) as total, 
		-1 * sum(memDiscount )as regPrice, 
		max(tax) as tax, 
		max(foodstamp) as foodstamp, 
		0 as discount, 
		-1 * sum(memDiscount) as memDiscount, 
		MAX(discountable) as discountable, 
		20 as discounttype, 
		8 as voided,
		MAX(percentDiscount) as percentDiscount,
		0 as ItemQtty, 
		0 as volDiscType, 
		0 as volume, 
		0 as VolSpecial, 
		0 as mixMatch, 
		0 as matched, 
		MAX(memType) as memType,
		MAX(staff) as staff,
		0 as numflag,
		'' as charflag,
		 card_no as card_no
		from localtemptrans 
		where ((discounttype = 2 and unitPrice = regPrice) or trans_status = 'M') 
		group by register_no, emp_no, trans_no, upc, description, card_no 
		having 
		sum(memDiscount)<> 0
";
?>
