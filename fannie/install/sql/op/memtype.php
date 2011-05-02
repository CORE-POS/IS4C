<?php
/*
Table: memtype

Columns:
	memtype tinyint
	memDesc varchar

Depends on:
	none

Use:
Housekeeping. If you want to sort people in
custdata into more categories than just
member/nonmember, use memtype.
*/
$CREATE['op.memtype'] = "
	CREATE TABLE memtype (
		memtype tinyint,
		memDesc varchar(20),
		primary key (memtype)
	)
";
?>
