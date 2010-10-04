<?php

$LOCAL_STORAGE_MECHANISM = 'SessionStorage';

if (!class_exists($LOCAL_STORAGE_MECHANISM)){
	include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/"
		.$LOCAL_STORAGE_MECHANISM.".php");
}

$IS4C_LOCAL = new $LOCAL_STORAGE_MECHANISM();
global $IS4C_LOCAL;

?>
