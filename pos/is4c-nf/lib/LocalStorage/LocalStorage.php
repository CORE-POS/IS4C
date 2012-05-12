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
		debug($key);
	}

	/**
	  Log state changes if debugging is enabled
	  
	  Call this from your set method
	*/
	function debug(){
		global $CORE_PATH;
		if($this->get("Debug_CoreLocal") == 1){
			$stack = debug_backtrace();
			$fp = @fopen($CORE_PATH.'log/core_local.log','a');
			if ($fp){
				foreach($stack as $s){
					if ($s['function']=='set'&&$s['class']==get_class($this)){
						ob_start();
						echo date('r').': Changed value for '.$s['args'][0]."\n";
						echo 'New value: ';
						print_r($s['args'][1]);
						echo "\n"; 
						echo 'Line '.$s['line'].', '.$s['file']."\n\n";
						$out = ob_get_clean();
						fwrite($fp,$out);
						break;	
					}
				}
				fclose($fp);
			}
		}
	}
}

?>
