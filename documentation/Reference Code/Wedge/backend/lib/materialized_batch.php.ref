<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

	function get_batchList($backoffice) {
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			// TODO - Order by freshness?
			$query='SELECT 
			`batchHeaders`.`id`, 
			`batchHeaders`.`name` AS \'batchHeaders name\', 
			`batchHeaders`.`start`, 
			`batchHeaders`.`end`, 
			`batchTypes`.`name` AS \'batchTypes name\',
			`batchMerges`.`modified` AS \'batchMerges modified\' 
			FROM `is4c_op`.`batchHeaders`
			JOIN `is4c_op`.`batchTypes` ON `batchHeaders`.`batchType_id`=`batchTypes`.`id`
			LEFT JOIN `is4c_log`.`batchMerges` ON `batchHeaders`.`id`=`batchMerges`.`batchHeader_id`
			WHERE `batchHeaders`.`active`=1 
			ORDER BY `id` DESC';
			$result=mysql_query($query, $link);
			if ($result) {
				if (mysql_num_rows($result)==0) {
					array_push($backoffice['status'], 'No batchList found...');
					return false;
				} else {
					return $result; 
				}
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function get_batchInfo($backoffice, $id) {
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			$query='SELECT 
			`batchHeaders`.`id`, 
			`batchHeaders`.`name` AS \'batchHeaders name\', 
			`batchHeaders`.`start`, 
			`batchHeaders`.`end`, 
			`batchTypes`.`name` AS \'batchTypes name\',
			`batchMerges`.`modified` AS \'batchMerges modified\' 
			FROM `is4c_op`.`batchHeaders`
			JOIN `is4c_op`.`batchTypes` ON `batchHeaders`.`batchType_id`=`batchTypes`.`id`
			LEFT JOIN `is4c_log`.`batchMerges` ON `batchHeaders`.`id`=`batchMerges`.`batchHeader_id`
			WHERE `batchHeaders`.`id`='.$id.' AND `batchHeaders`.`active`=1
			LIMIT 1';
			$result=mysql_query($query, $link);
			if ($result) {
				if (mysql_num_rows($result)==0) {
					array_push($backoffice['status'], 'No batchInfo found...');
					return false;
				} else {
					return $result; 
				}
			} else {
				array_push($backoffice['status'], 'Error with MySQL query: '.mysql_error($link));
			}
		} else {
			array_push($backoffice['status'], 'Error connecting to MySQL');
		}
	}
	
	function get_batchProducts($backoffice, $id) {
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
	
?>