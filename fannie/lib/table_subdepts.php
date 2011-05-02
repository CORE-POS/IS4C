<?php
	function get_subdepartments($backoffice) {
		global $dbc,$FANNIE_OP_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			$query='SELECT subdept_no, subdept_name FROM subdepts ORDER BY subdept_no';
			$result=$dbc->query($query);
			if ($result) {
				if ($dbc->num_rows($result)==0) {
					array_push($backoffice['status'], 'No subdepartments found...');
					return false;
				} else {
					return $result; 
				}
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
?>
