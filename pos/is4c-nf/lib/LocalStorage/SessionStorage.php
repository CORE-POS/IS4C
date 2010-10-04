<?php

if (!class_exists("LocalStorage")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/LocalStorage.php");

class SessionStorage extends LocalStorage {
	function SessionStorage(){
		if(ini_get('session.auto_start')==0 && !headers_sent())
                        session_start();
	}

	function get($key){
		if (!isset($_SESSION["$key"])) return "";
		return $_SESSION["$key"];
	}

	function set($key,$val){
		$_SESSION["$key"] = $val;
	}
}

?>
