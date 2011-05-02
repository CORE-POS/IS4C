<?php
/*
Table: prodUpdate

Columns:
	upc int or varchar, dbms dependent
	description varchar
	price dbms currency
	dept int
	tax bit
	fs bit
	scale bit
	likeCode int
	modified datetime
	user int
	forceQty bit
	noDisc bit
	inUse bit

Depends on:
	products (table)

Use:
In theory, every time a product is change in fannie,
the update is logged here. In practice, not all
tools/cron jobs/sprocs/etc actually do. They probably
should though, ideally.
*/
$CREATE['op.prodUpdate'] = "
	CREATE TABLE `prodUpdate` (
	  `upc` varchar(13) default NULL,
	  `description` varchar(50) default NULL,
	  `price` decimal(10,2) default NULL,
	  `dept` int(6) default NULL,
	  `tax` bit(1) default NULL,
	  `fs` bit(1) default NULL,
	  `scale` bit(1) default NULL,
	  `likeCode` int(6) default NULL,
	  `modified` date default NULL,
	  `user` int(8) default NULL,
	  `forceQty` bit(1) default NULL,
	  `noDisc` bit(1) default NULL,
	  `inUse` bit(1) default NULL
	)
";

if ($dbms == "MSSQL"){
	$CREATE['op.prodUpdate'] = "
		CREATE TABLE prodUpdate (
			upc varchar(13),
			description varchar(50),
			price money,
			dept int,
			tax smallint,
			fs smallint,
			scale smallint,
			likeCode int,
			modified datetime,
			[user] int,
			forceQty smallint,
			noDisc smallint,
			inUse smallint
		)
	";
}
