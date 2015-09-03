<?php
/*
Table: MasterSuperDepts

Columns:
	superID int
	super_name varchar
	dept_ID smallint

Depends on:
	departments (table)

Use:
A department may belong to more than one super department,
but for the purpose of categorizing items on the receipt
an item can only go in one category. The "master" super
department is used for this.
*/
$CREATE['op.MasterSuperDepts'] = "
	CREATE TABLE MasterSuperDepts (
	  superID INT(4) NOT NULL, 
	  super_name varchar(50) default NULL,
	  dept_ID smallint(4) default NULL,
	  PRIMARY KEY (superID, dept_ID)
	) 
";

?>
