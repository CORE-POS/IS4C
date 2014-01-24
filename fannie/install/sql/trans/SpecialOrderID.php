<?php
/*
Table: SpecialOrderID

Columns:
	id int (auto increment)

DEPRECATED SOON: See SpecialOrders

This table just exists as an accumulator
so that IDs in PendingSpecialOrder and
CompletedSpecialOrder never conflict

*/

$CREATE['trans.SpecialOrderID'] = "
	CREATE TABLE `SpecialOrderID` (
		  `id` int(11) NOT NULL auto_increment,
		  PRIMARY KEY  (`id`)
	)
";
if ($dbms == "MSSQL"){
	$CREATE['trans.SpecialOrderID'] = "
		CREATE TABLE [SpecialOrderID] (
			[id] [int] IDENTITY (1, 1) NOT NULL ,
			PRIMARY KEY ([id]) )
	";
}

?>
