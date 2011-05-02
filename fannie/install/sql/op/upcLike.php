<?php
/*
Table: upcLike

Columns:
	upc varchar or int, dbms dependent
	likeCode int

Depends on:
	likeCodes (table)

Use:
Lists the items contained in each like code
*/
$CREATE['op.upcLike'] = "
	CREATE TABLE `upcLike` (
		`upc` bigint(13) unsigned zerofill NOT NULL default '0000000000000',
		`likeCode` int default NULL
	)
"; 

if ($dbms == "MSSQL"){
	$CREATE['op.upcLike'] = "
	CREATE TABLE upcLike (
		upc varchar(13),
		likeCode int
	)
	";
}

?>
