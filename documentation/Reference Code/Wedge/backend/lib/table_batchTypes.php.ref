<?php
	require_once($_SERVER["DOCUMENT_ROOT"]."/define.conf");

	function get_batchTypes($backoffice) {
		$link=mysql_connect($_SESSION["mServer"], $_SESSION["mUser"], $_SESSION["mPass"]);
		if ($link) {
			// TODO - Order by popularity?
			$query='SELECT `id`, `name` FROM `is4c_op`.`batchTypes` ORDER BY `id`';
			$result=mysql_query($query, $link);
			if ($result) {
				if (mysql_num_rows($result)==0) {
					array_push($backoffice['status'], 'No batchTypes found...');
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