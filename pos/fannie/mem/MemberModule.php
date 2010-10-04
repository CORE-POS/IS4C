<?php

class MemberModule {

	function db(){
		global $dbc,$FANNIE_ROOT;
		if (!isset($dbc)) include_once($FANNIE_ROOT.'src/mysql_connect.php');
		return $dbc;
	}

	function ShowEditForm($memNum){

	}

	function SaveFormData($memNum){

	}

	function HasSearch(){
		return False;
	}

	function ShowSearchForm(){

	}

	function GetSearchResults(){

	}
	
	function RunCron(){

	}
}

?>
