<?php
	function get_batchHeaders($backoffice) {
		global $dbc,$FANNIE_OP_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			// TODO - Order by freshness?
			$query='SELECT id, name, start, end, batchType_id, modified, whomodified FROM batchHeaders WHERE active=1 ORDER BY id DESC';
			$result=$dbc->query($query);
			if ($result) {
				if ($dbc->num_rows($result)==0) {
					array_push($backoffice['status'], 'No batchHeaders found...');
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
