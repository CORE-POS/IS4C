<?php
/*
Table: transarchive

Columns:
	identical to dtransactions

Depends on:
	dtransactions (table)

Use:
This is a look-up table. Under WFC's day
end polling, transarchive contains the last
90 days' transaction entries. For queries
in that time frame, using this table can
simplify or speed up queries.
*/
$CREATE['trans.transarchive'] = duplicate_structure($dbms,
					'dtransactions','transarchive');
?>
