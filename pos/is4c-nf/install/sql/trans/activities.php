<?php
/*
Table: activities

Columns:
	Activity int
	Description varchar

Depends on:
	none

Use:
Lists meaning for different activity IDs.
*/
$CREATE['trans.activities'] = "
	CREATE TABLE activities (
		Activity tinyint,
		Description varchar(15),
		PRIMARY KEY (Activity)	
	)
";
