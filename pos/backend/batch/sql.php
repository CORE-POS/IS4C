<?php
	function addBatch($backoffice) {
		// TODO - Check name, start, end, type against expected values
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='INSERT INTO `is4c_op`.`batchHeaders` (`name`,`start`,`end`,`batchType_id`,`modified`,`whomodified`) VALUES (\''.$_REQUEST['addBatch_name'].'\', \''.$_REQUEST['addBatch_start'].'\', \''.$_REQUEST['addBatch_end'].'\', '.$_REQUEST['addBatch_type'].', NOW(), \''.$_SERVER['REMOTE_ADDR'].'\')';
			$result=mysql_query($query, $link);
			if ($result) {
				$batchHeader_id=mysql_insert_id($link);
				array_push($backoffice['status'], 'Added batch <a href="edit.php?id='.$batchHeader_id.'">'.$_REQUEST['addBatch_name'].'</a>');
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function editBatch($backoffice) {
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='UPDATE `is4c_op`.`batchHeaders` SET 
			`name`=\''.$_REQUEST['editBatch_name'].'\', 
			`start`=\''.$_REQUEST['editBatch_start'].'\', 
			`end`=\''.$_REQUEST['editBatch_end'].'\', 
			`batchType_id`='.$_REQUEST['editBatch_type'].',
			`modified`=NOW(),
			`whomodified`=\''.$_SERVER['REMOTE_ADDR'].'\'
			WHERE `batchHeaders`.`id`='.$_REQUEST['id'].' LIMIT 1';

			$result=mysql_query($query, $link);
			if ($result) {
				array_push($backoffice['status'], 'Updated batch information');
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function search_batchProducts($backoffice, $id, $upc) {
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='SELECT
			`batchProducts`.`upc`,
			`batchProducts`.`price`,
			`products`.`description`,
			`products`.`normal_price`
			FROM `is4c_op`.`batchProducts`
			JOIN `is4c_op`.`products` ON `batchProducts`.`upc`=`products`.`upc`
			WHERE 1=1
				AND `batchProducts`.`batchHeader_id`='.$id.'
				AND `batchProducts`.`upc` LIKE \'%'.$upc.'%\'
			ORDER BY `batchProducts`.`upc`';
			
			$result=mysql_query($query, $link);
			if ($result) {
				return $result; 
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}

	function search_allProducts($backoffice, $upc) {
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='SELECT
			`products`.`upc`,
			`products`.`description`,
			`products`.`normal_price`
			FROM `is4c_op`.`products`
			WHERE 1=1
				AND `products`.`upc` LIKE \'%'.$upc.'%\'
			ORDER BY `products`.`upc`';
			
			$result=mysql_query($query, $link);
			if ($result) {
				return $result; 
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function addProduct($backoffice) {
		// TODO - Validate data
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='INSERT INTO `is4c_op`.`batchProducts` (`batchHeader_id`,`upc`,`price`,`modified`,`whomodified`) VALUES ('.$_REQUEST['id'].', \''.$_REQUEST['addProduct_upc'].'\', '.$_REQUEST['addProduct_price'].', NOW(), \''.$_SERVER['REMOTE_ADDR'].'\')';
			$result=mysql_query($query, $link);
			if ($result) {
				array_push($backoffice['status'], 'Added product');
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}	
?>