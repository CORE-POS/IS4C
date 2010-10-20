<?php
	function get_batchTypes($backoffice) {
		global $dbc,$FANNIE_OP_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			// TODO - Order by popularity?
			$query='SELECT id, name FROM batchTypes ORDER BY id';
			$result=$dbc->query($query);
			if ($result) {
				if ($dbc->num_rows($result)==0) {
					$dbc->push($backoffice['status'], 'No batchTypes found...');
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
