<?php
/*
View: rp_dt_receipt_90

Columns:
	datetime datetime
	register_no int
	emp_no int
	trans_no int
	description varchar
	comment (calculated)
	total dbms currency
	Status (calculated)
	trans_type varchar
	memberID int
	unitPrice dbms currency
	voided int
	trans_id int
	trans_num (calculated)

Depends on:
	transarchive (table)

Use:
This view formats transarchive for
reprinting receipts (in the "old" style).
*/
$CREATE['trans.rp_dt_receipt_90'] = "
	CREATE  view rp_dt_receipt_90 as 
		select 
		datetime,
		register_no,
		emp_no,
		trans_no,
		description,
		case 
			when voided = 5 
				then 'Discount'
			when trans_status = 'M'
				then 'Mbr special'
			when scale <> 0 and quantity <> 0 
				then concat(convert(quantity,char), ' @ ', convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
				then concat(convert(volume,char), ' /', convert(unitPrice,char))
			when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
				then concat(convert(Quantity,char), ' @ ', convert(Volume,char), ' /', convert(unitPrice,char))
			when abs(itemQtty) > 1 and discounttype = 3
				then concat(convert(ItemQtty,char), ' /', convert(UnitPrice,char))
			when abs(itemQtty) > 1
				then concat(convert(quantity,char), ' @ ', convert(unitPrice,char))	
			when matched > 0
				then '1 w/ vol adj'
			else ''
		end
		as comment,
		total,
		case 
			when trans_status = 'V' 
				then 'VD'
			when trans_status = 'R'
				then 'RF'
			when tax <> 0 and foodstamp <> 0
				then 'TF'
			when tax <> 0 and foodstamp = 0
				then 'T' 
			when tax = 0 and foodstamp <> 0
				then 'F'
			when tax = 0 and foodstamp = 0
				then '' 
		end
		as Status,
		trans_type,
		card_no as memberID,
		unitPrice,
		voided,
		trans_id,
		concat(convert(emp_no,char), '-', convert(register_no,char), '-', convert(trans_no,char)) as trans_num
		from transarchive
		where voided <> 5 and upc <> 'TAX' and upc <> 'DISCOUNT'
	";

if ($dbms == "MSSQL"){
	$CREATE['trans.rp_dt_receipt_90'] = "
		CREATE  view rp_dt_receipt_90 as 
			select 
			datetime,
			register_no,
			emp_no,
			trans_no,
			description,
			case 
				when voided = 5 
					then 'Discount'
				when trans_status = 'M'
					then 'Mbr special'
				when scale <> 0 and quantity <> 0 
					then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)
				when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1
					then convert(varchar, volume) + ' /' + convert(varchar, unitPrice)
				when abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1
					then convert(varchar, Quantity) + ' @ ' + convert(varchar, Volume) + ' /' + convert(varchar, unitPrice)
				when abs(itemQtty) > 1 and discounttype = 3
					then convert(varchar,ItemQtty) + ' /' + convert(varchar, UnitPrice)
				when abs(itemQtty) > 1
					then convert(varchar, quantity) + ' @ ' + convert(varchar, unitPrice)	
				when matched > 0
					then '1 w/ vol adj'
				else ''
			end
			as comment,
				total,
			case 
				when trans_status = 'V' 
					then 'VD'
				when trans_status = 'R'
					then 'RF'
				when tax <> 0 and foodstamp <> 0
					then 'TF'
				when tax <> 0 and foodstamp = 0
					then 'T' 
				when tax = 0 and foodstamp <> 0
					then 'F'
				when tax = 0 and foodstamp = 0
					then '' 
			end
			as Status,
			trans_type,
			card_no as memberID,
			unitPrice,
			voided,
			trans_id,
			(convert(varchar,emp_no) +  '-' + convert(varchar,register_no) + '-' + convert(varchar,trans_no)) as trans_num
			from transarchive
			where voided <> 5 and upc <> 'TAX' and upc <> 'DISCOUNT'
	";
}
?>

}
