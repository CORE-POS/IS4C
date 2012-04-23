<?php

/**
  @class LocalStorage

  A module for storing persistent values  
  This is handled via PHP sessions by
  default.

  Interface class. Must be subclassed
  to do anything.
*/

class LocalStorage {
	/**
	  Constructor
	*/
	function localStorage(){

	}

	/**
	  Get the value stored with the given key
	  @param $key A unique key string
	  @return The value (mixed)

	  The value can be any PHP type that the
	  underlying mechanism can store.

	  If the key is not found, the return
	  value will be an empty string.
	*/
	function get($key){

	}

	/**
	  Save the value with the given key
	  @param $key A unique key string
	  @param The value (mixed)

	  The value can be any PHP type that the
	  underlying mechanism can store.
	*/
	function set($key,$val){

	}
}

?>
