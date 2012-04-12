<?php
/*
Table: customReceipt

Columns:
	text varchar
	seq int
	type varchar

Depends on:
	none

Use:
This table contains extra lines for the header
and/or footer of the register receipt. type
should be 'header' or 'footer', text is the
line to print, and seq puts them in order
*/
$CREATE['op.customReceipt'] = "
	CREATE TABLE customReceipt (
		text varchar(20),
		seq int,
		type varchar(20),
		PRIMARY KEY (seq, type)
	)
";
?>
