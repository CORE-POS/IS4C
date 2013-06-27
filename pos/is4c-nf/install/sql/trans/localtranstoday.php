<?php
/*
View: localtranstoday

Columns:
	identical to dtransactions

Depends on:
	localtrans_today (table)

Use:
List today's transactions
*/
$CREATE['trans.localtranstoday'] = "
	CREATE VIEW localtranstoday AS
	SELECT * FROM localtrans_today WHERE "
	.$con->datediff($con->now(),'datetime')." = 0
";
?>
