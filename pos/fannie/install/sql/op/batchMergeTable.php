<?php
/*
Table: batchMergeTable

Columns:
	startDate datetime
	endDate datetime
	upc varchar or int, dbms dependent
	description varchar
	batchID int

Depends on:
	batchMergeProd
	batchMergeLC

Use:
This is a speedup table for reports. It's
populated (daily) from the views batchMergeProd
and batchMergeLC. It unrolls likecoded batchList
entries back into upcs which simplifies subsequent
queries. At WFC batchList is also a bit large
and slow to join against directly. 
*/
$upc = "upc bigint(13) unsigned zerofill";
if ($dbms == "MSSQL") $upc = "upc varchar(13)";

$CREATE['op.batchMergeTable'] = "
	CREATE TABLE batchMergeTable (
		startDate datetime,
		endDate datetime,
		$upc,
		description varchar(30),
		batchID int
	)
";

?>
