<?php
/*
Table: meminfo

Columns:
	card_no smallint
	last_name varchar
	first_name varchar
	othlast_name varchar
	othfirst_name varchar
	street varchar
	city varchar
	state varchar
	phone varchar
	email_1 varchar
	email_2 varchar
	ads_OK tinyint

Depends on:
	custdata (table)

Use:
This table has contact information for a member.
Street, city/state/zip, and phone are straightforward.

I ignore the name fields entirely. They'll work if your
co-op allows only 1 or 2 people per membership, but
custdata can hold the same information in a more
future-proof way.

I put an email address in email_1 and a second
phone number in email_2. More people seem to have a home
phone and cellphone than two email addresses they check
regularly.

Usage doesn't have to match mine. The member section of
fannie should be modular enough to support alternate
usage of some fields.
*/
$CREATE['op.meminfo'] = "
	CREATE TABLE `meminfo` (
	  `card_no` smallint(5) default NULL,
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
	$CREATE['op.meminfo'] = "
		CREATE TABLE meminfo (
		  card_no smallint ,
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
