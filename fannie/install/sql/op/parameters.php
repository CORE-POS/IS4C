<?php
/*
Table: parameters

Columns:
	store_id int
	lane_id int
	param_key varchar
	param_value varchar
	is_array int

Depends on:
    none

Use:
Partial replacement for ini.php.
This differs from the lane_config table.
This contains actual values where as lane_config
contains PHP code snippets that can
be written to a file.

Values with store_id=0 (or NULL) and lane_id=0 (or NULL)
are applied first, then values with the lane's own
lane_id are applied second as local overrides. A similar
precedent level based on store_id may be added at a later date.
*/
$CREATE['op.parameters'] = "
	CREATE TABLE parameters (
	  store_id smallint(4),
	  lane_id smallint(4),
      param_key varchar(100),
      param_value varchar(255),
      is_array TINYINT,
	  PRIMARY KEY (store_id, lane_id, param_key),
	  KEY (param_key)
	) 
";

if ($dbms == 'PDOLITE'){
	$CREATE['op.parameters'] = str_replace('KEY (param_key)','',$CREATE['op.parameters']);
}

?>
