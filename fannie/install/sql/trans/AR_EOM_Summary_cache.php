<?php
/*
Table: AR_EOM_Summary_cache

Columns:
	card_no int
	memName varchar
	priorBalance money
	threeMonthCharges money
	threeMonthPayments money
	threeMonthBalance money
	twoMonthCharges money
	twoMonthPayments money
	twoMonthBalance money
	lastMonthCharges money
	lastMonthPayments money
	lastMonthBalance money

Depends on:
	AR_EOM_Summary (view)

Use:
View of customer start/end AR balances
over past few months
*/
$CREATE['trans.AR_EOM_Summary_Cache'] = "
	CREATE TABLE AR_EOM_Summary_cache (
	cardno int,
	memName varchar(100),
	priorBalance decimal(10,2),
	threeMonthCharges decimal(10,2),
	threeMonthPayments decimal(10,2),
	threeMonthBalance decimal(10,2),	
	twoMonthCharges decimal(10,2),
	twoMonthPayments decimal(10,2),
	twoMonthBalance decimal(10,2),	
	lastMonthCharges decimal(10,2),
	lastMonthPayments decimal(10,2),
	lastMonthBalance decimal(10,2),	
	PRIMARY KEY (cardno)
	)
";
