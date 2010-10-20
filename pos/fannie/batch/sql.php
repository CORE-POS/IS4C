<?php
	function addBatch($backoffice) {
		global $dbc,$FANNIE_OP_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
		// TODO - Check name, start, end, type against expected values
			$vals = array(
				'name'=>$dbc->escape($_REQUEST['addBatch_name']),
				'start'=>$dbc->escape($_REQUEST['addBatch_start']),
				'end'=>$dbc->escape($_REQUEST['addBatch_end']),
				'batchType_id'=>sprintf("%d",$_REQUEST['addBatch_type']),
				'modified'=>$dbc->now(),
				'whomodified'=>$dbc->escape($_SERVER['REMOTE_ADDR']),
				'active'=>1
			);
			$res = $dbc->smart_insert('batchHeaders',$vals);
			if ($res) {
				$batchHeader_id=$dbc->insert_id($link);
				array_push($backoffice['status'], 'Added batch <a href="edit.php?id='.$batchHeader_id.'">'.$_REQUEST['addBatch_name'].'</a>');
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function editBatch($backoffice) {
		global $dbc,$FANNIE_OP_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			$vals = array(
			'name'=>$dbc->escape($_REQUEST['editBatch_name']),
			'start'=>$dbc->escape($_REQUEST['editBatch_start']),
			'end'=>$dbc->escape($_REQUEST['editBatch_end']),
			'batchType_id'=>sprintf("%d",$_REQUEST['editBatch_type']),
			'modified'=>$dbc->now(),
			'whomodified'=>$dbc->escape($_SERVER['REMOTE_ADDR'])
			);
			$where = sprintf("id=%d",$_REQUEST['id']);
		
			$res = $dbc->smart_update('batchHeaders',$vals,$where);

			if ($res) {
				array_push($backoffice['status'], 'Updated batch information');
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function search_batchProducts($backoffice, $id, $upc) {
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
				AND b.upc LIKE \'%'.$upc.'%\'
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

	function search_allProducts($backoffice, $upc) {
		global $dbc,$FANNIE_OP_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			$query='SELECT
			upc,
			description,
			normal_price
			FROM products
			WHERE 1=1
				AND upc LIKE \'%'.$upc.'%\'
			ORDER BY upc';
			
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
	
	function addProduct($backoffice) {
		global $dbc,$FANNIE_OP_DB;
		// TODO - Validate data
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			$vals = array(
				'batchHeader_id'=>sprintf("%d",$_REQUEST['id']),
				'upc'=>$dbc->escape($_REQUEST['addProduct_upc']),
				'price'=>sprintf("%f",$_REQUEST['addProduct_pirce']),
				'modified'=>$dbc->now(),
				'whomodified'=>$dbc->escape($_SERVER['REMOTE_ADDR'])
			);
			$result=$dbc->smart_insert('batchProducts',$vals);
			if ($result) {
				array_push($backoffice['status'], 'Added/modified product');
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}	
	
	function listBatch($backoffice) {
		// For now, just mark a batchHeader as active=0 to delete it
		global $dbc,$FANNIE_OP_DB;
		if ($dbc->connections[$FANNIE_OP_DB] != false) {
			if (isset($_REQUEST['listBatch_deleteFlag'])) {
				foreach ($_REQUEST['listBatch_deleteFlag'] as $key=>$id) {
					$query=sprintf('UPDATE batchHeaders SET active=0 WHERE id=%d',$id);
					$result=$dbc->query($query);
					if ($result) {
						array_push($backoffice['status'], 'Deleted batchHeader #'.$id);
					} else {
						array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());
					}
				}
			}
			
			if (isset($_REQUEST['listBatch_mergeFlag'])) {
				foreach ($_REQUEST['listBatch_mergeFlag'] as $key=>$id) {
					// multi-table updates require special casing
					// to support various SQL backends
					// this one only works with MySQL
					$query='UPDATE products as p, batchProducts as b, batchHeaders as h SET
					p.special_price=b.price,
					p.specialpricemethod=b.pricemethod,
					/* p.specialgroupprice=b.groupprice,
					p.specialquantity=b.quantity, */
					p.start_date=h.start,
					p.end_date=h.end,
					p.discounttype=1, 
					p.modified='.$dbc->now().'
					WHERE 1=1 
						AND p.upc=b.upc 
						AND b.batchHeader_id=h.id
						AND h.id='.$id.'';
					$result=$dbc->query($query);
					if ($result) {
						// TODO - Add information to merge log!!!
						array_push($backoffice['status'], 'Merged batch #'.$id.', updated '.$dbc->affected_rows($link).' products');
					} else {
						array_push($backoffice['status'], 'Error with MySQL query: '.$dbc->error());						
					}
				}
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
?>
