<?php
/*
Table: SpecialOrderContact

Columns:
	Same as meminfo

Depends on:
	PendingSpecialOrder

Use:

DEPRECATED SOON: See SpecialOrders

This table is for a work-in-progress special
order tracking system. Conceptually, it will
work like a partial suspended transactions,
where rows with a given order_id can be
pulled in at a register when someone picks up
their special order.

This table stores contact information
for non-member special orders. It's
structure is identical to meminfo for
simplicity. The order's order_id goes
into card_no.
*/

$CREATE['trans.SpecialOrderContact'] = "
	CREATE TABLE `SpecialOrderContact` (
	  `card_no` int(11) default NULL,
	  `last_name` varchar(30) default NULL,
	  `first_name` varchar(30) default NULL,
	  `othlast_name` varchar(30) default NULL,
	  `othfirst_name` varchar(30) default NULL,
	  `street` varchar(255) default NULL,
	  `city` varchar(20) default NULL,
	  `state` varchar(2) default NULL,
	  `zip` varchar(10) default NULL,
	  `phone` varchar(30) default NULL,
	  `email_1` varchar(50) default NULL,
	  `email_2` varchar(50) default NULL,
	  `ads_OK` tinyint(1) default '1'
	)
";

if ($dbms == "MSSQL"){
	$CREATE['trans.SpecialOrderContact'] = "
		CREATE TABLE SpecialOrderContact (
		  card_no int ,
		  last_name varchar(30) ,
		  first_name varchar(30) ,
		  othlast_name varchar(30) ,
		  othfirst_name varchar(30) ,
		  street varchar(255) ,
		  city varchar(20) ,
		  state varchar(2) ,
		  zip varchar(10) ,
		  phone varchar(30) ,
		  email_1 varchar(50) ,
		  email_2 varchar(50) ,
		  ads_OK tinyint 
		)
	";
}
?>
