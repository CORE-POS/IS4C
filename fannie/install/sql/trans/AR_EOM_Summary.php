<?php
/*
Table: ar_history

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
	ar_history_backup (table)

Use:
View of customer start/end AR balances
over past few months
*/
$names = qualified_names();
$CREATE['trans.AR_EOM_Summary'] = "
	CREATE VIEW AR_EOM_Summary AS
	SELECT c.cardno,"
	.$con->concat("c.firstname","' '","c.lastname",'')." AS memName,

	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." <= -4
	THEN charges ELSE 0 END)
	- SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." <= -4
	THEN payments ELSE 0 END) AS priorBalance,

	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." = -3
		THEN a.charges ELSE 0 END) AS threeMonthCharges,
	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." = -3
		THEN a.payments ELSE 0 END) AS threeMonthPayments,

	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." <= -3
	THEN charges ELSE 0 END)
	- SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." <= -3
	THEN payments ELSE 0 END) AS threeMonthBalance,

	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." = -2
		THEN a.charges ELSE 0 END) AS twoMonthCharges,
	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." = -2
		THEN a.payments ELSE 0 END) AS twoMonthPayments,

	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." <= -2
	THEN charges ELSE 0 END)
	- SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." <= -2
	THEN payments ELSE 0 END) AS twoMonthBalance,

	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." = -1
		THEN a.charges ELSE 0 END) AS lastMonthCharges,
	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." = -1
		THEN a.payments ELSE 0 END) AS lastMonthPayments,

	SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." <= -1
	THEN charges ELSE 0 END)
	- SUM(CASE WHEN ".$con->monthdiff('a.tdate',$con->now())." <= -1
	THEN payments ELSE 0 END) AS lastMonthBalance

	FROM ar_history_backup AS a LEFT JOIN
	{$names['op']}.custdata AS c ON a.card_no=c.cardno AND c.personnum=1
	GROUP BY c.cardno,c.lastname,c.firstname
";
?>
