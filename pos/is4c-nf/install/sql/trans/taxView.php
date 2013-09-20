<?php
/*
View: suspendedtoday

Columns:
	id int
	description varchar
	taxTotal currency
	fsTaxable currency
	fsTaxTotal currency
	foodstampTender currency
	taxrate float

Depends on:
	localtemptrans (table), taxrates (table),
	lttsummary (view)

Use:
This view is a revised, BETA way of dealing
with taxes. Rather than generate the tax total
(including foodstamp exemptions) with a series of
cascading views, this single view provides a
record for each available tax rate. Exemption 
calculations then occur on the code side in a
far-easier-to-read imperative style.

id is the tax rate's identifier and description
is it's description.

taxTotal is the total tax due for this particular
rate. SUM(taxTotal) over the view would be the total
tax due with all rates.

fsTaxable is the *retail* cost of goods taxed at this rate.
fsTaxTotal is tax due on those items at this rate.

foodstampTender is the total amount tendered in foodstamps
for the transaction. This will be the same for all records
in this view and is provided as a convenience to avoid a 
second look-up query.

rate is this tax rate as a decimal - i.e., 1% is 0.01.

----------------------------------------------
In calculating exemptions, foodstampTender and fsTaxable
are important. If foodstampTender is >= fsTaxable then
all foodstampable, taxable items were purchased with foodstamps
and you can subtract fsTaxTotal from taxTotal. On the other
hand if foodstampTender is < fsTaxable then you should reduce
taxTotal by a proportional pro-rated amount.

When dealing with multiple tax rates, it is important to
reduce foodstampTender each time it is used. The value in the
view is the same for all records and POS has to decide where
to apply that tender more than once.
*/
$CREATE['trans.taxView'] = "
	CREATE VIEW taxView AS
		SELECT 
		r.id,
		r.description,
		CAST(SUM(CASE 
			WHEN l.trans_type IN ('I','D') AND discountable=0 THEN total 
			WHEN l.trans_type IN ('I','D') AND discountable<>0 THEN total * ((100-s.percentDiscount)/100)
			ELSE 0 END
		) * r.rate AS DECIMAL(10,2)) as taxTotal,
		CAST(SUM(CASE 
			WHEN l.trans_type IN ('I','D') AND discountable=0 AND foodstamp=1 THEN total 
			WHEN l.trans_type IN ('I','D') AND discountable<>0 AND foodstamp=1 THEN total * ((100-s.percentDiscount)/100)
			ELSE 0 END
		) AS DECIMAL(10,2)) as fsTaxable,
		CAST(SUM(CASE 
			WHEN l.trans_type IN ('I','D') AND discountable=0 AND foodstamp=1 THEN total 
			WHEN l.trans_type IN ('I','D') AND discountable<>0 AND foodstamp=1 THEN total * ((100-s.percentDiscount)/100)
			ELSE 0 END
		) * r.rate AS DECIMAL(10,2)) as fsTaxTotal,
		-1*MAX(fsTendered) as foodstampTender,
		MAX(r.rate) as taxrate
		FROM
		taxrates AS r 
		LEFT JOIN localtemptrans AS l
		ON r.id=l.tax
		JOIN lttsummary AS s
		WHERE trans_type <> 'L'
		GROUP BY r.id,r.description
";
?>
