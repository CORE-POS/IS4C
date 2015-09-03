<?php
/*
View: screendisplay

Columns:
	description varchar
	comment varchar
	total varchar
	status varchar
	lineColor varchar
	discounttype int
	trans_type varchar
	trans_status varchar
	voided int
	trans_id int

Depends on:
	localtemptrans, taxrates

Use:
Formats current transaction info for
onscreen display.

The first four columns are display text
and on rendered left to right. Description
goes on the far left, comment goes in the middle,
total is the lineitem's price, and status is
a character or two on the far right.

trans_id is used to select appropriate lines if
the entire transaction does not fit on the screen

The remaining columns are used for display formatting
mostly in terms of background & text color.
*/
$CREATE['trans.screendisplay'] = "
	CREATE view screendisplay as 
		select 
		CASE
		WHEN (voided = 5 or voided = 11 or voided = 17 or trans_type = 'T')
			THEN ''
		ELSE
			l.description
		END
		as description,
		CASE
		WHEN(discounttype = 3 and trans_status = 'V')
			THEN ".$con->concat('ItemQtty',"' /'",'UnitPrice','')."
		WHEN (voided = 5)
			THEN 'Discount'
		WHEN (trans_status = 'M')
			THEN 'Mbr special'
		WHEN (trans_status = 'S')
			THEN 'Staff special'
		WHEN (scale <> 0 and quantity <> 0 and unitPrice <> 0.01)
			THEN ".$con->concat('quantity',"' @ '",'unitPrice','')."
		WHEN (SUBSTR(upc, 1, 3) = '002')
			THEN ".$con->concat('itemQtty',"' @ '",'regPrice','')."
		WHEN (abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1)
			THEN ".$con->concat('volume',"' for '",'unitPrice','')."
		WHEN (abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1)
			THEN ".$con->concat('quantity',"' @ '",'volume',"' for '",'unitPrice','')."
		WHEN (abs(itemQtty) > 1 and discounttype = 3)
			THEN ".$con->concat('itemQtty',"' / '",'unitPrice','')."
		WHEN (abs(itemQtty) > 1)
			THEN ".$con->concat('quantity',"' @ '",'unitPrice','')."
		WHEN (voided = 3)
			THEN 'Total '
		WHEN (voided = 5)
			THEN 'Discount '
		WHEN (voided = 7)
			THEN ''
		WHEN (voided = 11 or voided = 17)
			THEN upc
		WHEN (matched > 0)
			THEN '1 w/ vol adj'
		WHEN (trans_type = 'T')
			THEN l.description
		ELSE
			''
		END
		as comment,
		CASE
		WHEN (voided = 3 or voided = 5 or voided = 7 or voided = 11 or voided = 17)
			THEN unitPrice
		WHEN (trans_status = 'D')
			THEN ''
		ELSE
			total
		END
		as total,
		CASE
		WHEN (trans_status = 'V')
			THEN 'VD'
		WHEN (trans_status = 'R')
			THEN 'RF'
		WHEN (trans_status = 'C')
			THEN 'MC'
        WHEN trans_type = 'T' AND charflag='PT'
            THEN 'PC'
		WHEN (tax = 1 and foodstamp <> 0)
			THEN 'TF'
		WHEN (tax = 1 and foodstamp = 0)
			THEN 'T' 
		WHEN (tax > 1 and foodstamp <> 0)
			THEN ".$con->concat('SUBSTR(t.description,0,1)',"'F'",'')."
		WHEN (tax > 1 and foodstamp = 0)
			THEN SUBSTR(t.description,0,1)
		WHEN (tax = 0 and foodstamp <> 0)
			THEN 'F'
		WHEN (tax = 0 and foodstamp = 0)
			THEN ''
		ELSE
			''
		END
		as status,
		CASE
		WHEN (trans_status = 'V' or trans_type = 'T' or trans_status = 'R' or trans_status = 'C' or trans_status = 'M' or voided = 17 or trans_status = 'J')
			THEN '800000'
		WHEN ((discounttype <> 0 and (matched > 0 or volDiscType=0)) or voided = 2 or voided = 6 or voided = 4 or voided = 5 or voided = 10 or voided = 22)
			THEN '408080'
		WHEN (voided = 3 or voided = 11)
			THEN '000000'
		WHEN (voided = 7)
			THEN '800080'
		ELSE
			'004080'
		END
		as lineColor,
		discounttype,
		trans_type,
		trans_status,
		voided,
		trans_id
		from localtemptrans as l
		left join taxrates as t
		on l.tax = t.id
		WHERE trans_type <> 'L'
		order by trans_id
";
if ($dbms == 'MSSQL'){
	$CREATE['trans.screendisplay'] = "CREATE view screendisplay as 
		select 
		CASE
		WHEN (voided = 5 or voided = 11 or voided = 17 or trans_type = 'T')
			THEN ''
		ELSE
			l.description
		END
		as description,
		CASE
		WHEN(discounttype = 3 and trans_status = 'V')
			THEN ItemQtty+' /'+UnitPrice
		WHEN (voided = 5)
			THEN 'Discount'
		WHEN (trans_status = 'M')
			THEN 'Mbr special'
		WHEN (trans_status = 'S')
			THEN 'Staff special'
		WHEN (scale <> 0 and quantity <> 0)
			THEN quantity+' @ '+unitPrice
		WHEN (SUBSTRING(upc, 1, 3) = '002')
			THEN itemQtty+' @ '+regPrice
		WHEN (abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity = 1)
			THEN volume+' for '+unitPrice
		WHEN (abs(itemQtty) > 1 and abs(itemQtty) > abs(quantity) and discounttype <> 3 and quantity <> 1)
			THEN Quantity+' @ '+Volume+' for '+unitPrice
		WHEN (abs(itemQtty) > 1 and discounttype = 3)
			THEN ItemQtty+' /'+UnitPrice
		WHEN (abs(itemQtty) > 1)
			THEN quantity+' @ '+unitPrice
		WHEN (voided = 3)
			THEN 'Total '
		WHEN (voided = 5)
			THEN 'Discount '
		WHEN (voided = 7)
			THEN ''
		WHEN (voided = 11 or voided = 17)
			THEN upc
		WHEN (matched > 0)
			THEN '1 w/ vol adj'
		WHEN (trans_type = 'T')
			THEN l.description
		ELSE
			''
		END
		as comment,
		CASE
		WHEN (voided = 3 or voided = 5 or voided = 7 or voided = 11 or voided = 17)
			THEN unitPrice
		WHEN (trans_status = 'D')
			THEN ''
		ELSE
			total
		END
		as total,
		CASE
		WHEN (trans_status = 'V')
			THEN 'VD'
		WHEN (trans_status = 'R')
			THEN 'RF'
		WHEN (trans_status = 'C')
			THEN 'MC'
		WHEN (tax <> 0 and foodstamp <> 0)
			THEN 'TF'
		WHEN (tax <> 0 and foodstamp = 0)
			THEN 'T' 
		WHEN (tax = 0 and foodstamp <> 0)
			THEN 'F'
		WHEN (tax = 0 and foodstamp = 0)
			THEN ''
		ELSE
			''
		END
		as status,
		CASE
		WHEN (trans_status = 'V' or trans_type = 'T' or trans_status = 'R' or trans_status = 'C' or trans_status = 'M' or voided = 17 or trans_status = 'J')
			THEN '800000'
		WHEN ((discounttype <> 0 and (volDiscType=0 or matched>0)) or voided = 2 or voided = 6 or voided = 4 or voided = 5 or voided = 10 or voided = 22)
			THEN '408080'
		WHEN (voided = 3 or voided = 11)
			THEN '000000'
		WHEN (voided = 7)
			THEN '800080'
		ELSE
			'004080'
		END
		as lineColor,
		discounttype,
		trans_type,
		trans_status,
		voided,
		trans_id
		from localtemptrans
		WHERE trans_type <> 'L'
		order by trans_id";
}
?>
