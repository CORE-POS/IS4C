<?php
/*
Table: lane_config

Columns:
	modified datetime

Depends on:
	none

Use:
Keep track of when ini file is updated
*/
$CREATE['op.lane_config'] = "
	CREATE TABLE lane_config (
		modified datetime
	)
";

?>
