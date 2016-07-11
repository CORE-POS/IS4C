<?php
/*
View: staffdiscountremove

Columns:
	identical to dtransactions

Depends on:
	localtemptrans (table)

Use:
This view is the opposite of staffdiscountadd.
It calculates the reverse of all currently
applied staff discounts on items. These records
are inserted into localtemptrans to remove
staff discounts if needed.
*/
$CREATE['trans.staffdiscountremove'] = "
	CREATE view staffdiscountremove as
		Select 
		max(datetime) as datetime, 
		register_no, 
		emp_no, 
		trans_no, 
		upc, 
		description, 
		'I' as trans_type, 
		'' as trans_subtype, 
		'S' as trans_status, 
		max(department) as department, 
		1 as quantity, 
		0 as scale, 
		0 as cost,
		-1 * (sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)) as unitPrice, 
		-1 * (sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)) as total, 
		-1 * (sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end))as regPrice, 
		max(tax) as tax, 
		max(foodstamp) as foodstamp, 
		0 as discount, 
		-1 * (sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)) as memDiscount, 
		3 as discountable, 
		40 as discounttype, 
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
		where ((discounttype = 4 and unitPrice <> regPrice) or trans_status = 'S') 
		group by register_no, emp_no, trans_no, upc, description, card_no 
		having 
		sum(case when (discounttype = 4 and unitPrice <> regPrice) then -1 * memDiscount 
		else memDiscount end)<> 0
";
?>
