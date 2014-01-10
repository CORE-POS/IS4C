<?php
/*
Table: activitytemplog

Columns:
	datetime datetime
	LaneNo int
	CashierNo int
	TransNo int
	Activity int
	Interval double

Depends on:
	none

Use:
Stores activity info during transaction.
Rotates into alog and activitylog.
*/
$CREATE['trans.activitytemplog'] = InstallUtilities::duplicateStructure($dbms,'alog','activitytemplog');

?>
