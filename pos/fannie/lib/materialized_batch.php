<?php
	function get_batchList($backoffice) {
		global $dbc,$FANNIE_OP_DB,$FANNIE_TRANS_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			// TODO - Order by freshness?
			$query='SELECT 
			h.id, 
			h.name AS \'batchHeaders name\', 
			h.start, 
			h.end, 
			t.name AS \'batchTypes name\',
			m.modified AS \'batchMerges modified\' 
			FROM '.$FANNIE_OP_DB.'.batchHeaders as h
			JOIN '.$FANNIE_OP_DB.'.batchTypes as t ON h.batchType_id=t.id
			LEFT JOIN '.$FANNIE_TRANS_DB.'.batchMerges as m ON h.id=m.batchHeader_id
			WHERE h.active=1 
			ORDER BY id DESC';
			$result=$dbc->query($query);
			if ($result) {
				if ($dbc->num_rows($result)==0) {
					array_push($backoffice['status'], 'No batchList found...');
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
	
	function get_batchInfo($backoffice, $id) {
		global $dbc,$FANNIE_OP_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			$query='SELECT 
			h.id, 
			h.name AS \'batchHeaders name\', 
			h.start, 
			h.end, 
			t.name AS \'batchTypes name\',
			m.modified AS \'batchMerges modified\' 
			FROM '.$FANNIE_OP_DB.'.batchHeaders as h
			JOIN '.$FANNIE_OP_DB.'.batchTypes as t ON h.batchType_id=t.id
			LEFT JOIN '.$FANNIE_TRANS_DB.'.batchMerges as m ON h.id=m.batchHeader_id
			WHERE h.id='.$id.' AND h.active=1
			LIMIT 1';
			$result=$dbc->query($query);
			if ($result) {
				if ($dbc->num_rows($result)==0) {
					array_push($backoffice['status'], 'No batchInfo found...');
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
	
	function get_batchProducts($backoffice, $id) {
		global $dbc,$FANNIE_OP_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			$query='SELECT
			b.upc,
			b.price,
			p.description,
			p.normal_price
			FROM batchProducts as b
			JOIN products as p ON b.upc=p.upc
			WHERE 1=1
				AND b.batchHeader_id='.$id.'
			ORDER BY b.upc';
			$result=$dbc->query($query);
			if ($result) {
				return $result; 
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
?>
