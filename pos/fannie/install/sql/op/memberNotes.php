<?php
/*
Table: memberNotes

Columns:
	cardno int
	note text
	stamp datetime
	username varchar(50)

Depends on:
	custdata (table)

Use:
This table just holds generic blobs of text
associated with a given member. Used to make
a note about a membership and keep a record of
it.
*/
$CREATE['op.memberNotes'] = "
	CREATE TABLE memberNotes (
		cardno int,
		note text,
		stamp datetime,
		username varchar(50)
	)
";
?>
