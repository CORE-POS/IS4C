<?php
/*
Table: vendorItems

Columns:
	upc varchar
	sku varchar
	brand varchar
	description varchar
	size varchar
	units int
	cost dbms currency
	vendorDept int
	vendorID int

Depends on:
	vendors (table)
	vendorDepartments (table)

Use:
This table has items from vendors. Cost
and vendor department margin are used to 
calculate SRPs, but the other fields are useful
for making shelf tags.

Size relates to an indivdual product.
Units relates to a case. So a case of beer has 24
units, each with a size of 12 oz.

Cost represents the unit cost. Cost times units 
should then equal the case cost.
*/
$CREATE['op.vendorItems'] = "
	CREATE TABLE vendorItems (
		upc varchar(13),
		sku varchar(10),
		brand varchar(50),
		description varchar(50),
		size varchar(25),
		units int,
		cost decimal(10,2),
		vendorDept int,
		vendorID int,
		PRIMARY KEY (vendorID,upc),
		INDEX(vendorID),
		INDEX(upc)
	)
";

if ($dbms == 'mssql'){
	$CREATE['op.vendorItems'] = "
		CREATE TABLE vendorItems (
			upc varchar(13),
			sku varchar(10),
			brand varchar(50),
			description varchar(50),
			size varchar(25),
			units int,
			cost money,
			vendorDept int,
			vendorID int
		)
	";
}

?>
