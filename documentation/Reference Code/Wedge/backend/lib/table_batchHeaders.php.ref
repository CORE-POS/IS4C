<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

	function get_batchHeaders($backoffice) {
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			// TODO - Order by freshness?
			$query='SELECT `id`, `name`, `start`, `end`, `batchType_id`, `modified`, `whomodified` FROM `is4c_op`.`batchHeaders` WHERE `active`=1 ORDER BY `id` DESC';
			$result=mysql_query($query, $link);
			if ($result) {
				if (mysql_num_rows($result)==0) {
					array_push($backoffice['status'], 'No batchHeaders found...');
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
?>