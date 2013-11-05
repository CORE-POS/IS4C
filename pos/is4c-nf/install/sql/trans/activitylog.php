<?php
/*
Table: activitylog

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
Lane-side record of activities. These are
shipped to the server via alog.
*/
$CREATE['trans.activitylog'] = InstallUtilities::duplicateStructure($dbms,'alog','activitylog');

?>
