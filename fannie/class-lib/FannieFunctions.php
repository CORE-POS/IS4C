<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  @class FannieFunctions
  Functions for loading and registering modules
*/
class FannieFunctions extends FannieModule {

	public $required = True;

	public $description = "
	Provides functions for registering
	and loading other modules.
	";

	function provided_functions(){
		return array(
		'register_symbols',
		'unregister_symbols',
		'save_symbols',
		'unpack_symbols',
		'load_function',
		'load_class',
		'use_module',
		'get_available_modules'
		);
	}
}

/**
  @file
  @brief Base fannie functions
*/


/**
  Register available function(s) and class(es)
  @param $file is the file providing the function(s) and/or class(es)
  @param $functions is a function name or array of function names
  @param $classes is a class name or array of class names
*/
function register_symbols($file, $functions=null, $classes=null){
	global $FANNIE_SYMBOLS;

	if (!is_null($functions)){
		if (!is_array($functions))
			$functions = array($functions);

		if (!isset($FANNIE_SYMBOLS['functions']))
			$FANNIE_SYMBOLS['functions'] = array();

		foreach($functions as $f)
			$FANNIE_SYMBOLS['functions'][$f] = $file;	
	}

	if (!is_null($classes)){
		if (!is_array($classes))
			$classes = array($classes);

		if (!isset($FANNIE_SYMBOLS['classes']))
			$FANNIE_SYMBOLS['classes'] = array();

		foreach($classes as $c)
			$FANNIE_SYMBOLS['classes'][$c] = $file;	
	}

	save_symbols();
}

/**
  Unregister available function(s) and class(es)
  @param $file is the file providing the function(s) and/or class(es)
  @param $functions is a function name or array of function names
  @param $classes is a class name or array of class names
*/
function unregister_symbols($functions=null, $classes=null){
	global $FANNIE_SYMBOLS;

	if (!is_null($functions)){
		if (!is_array($functions))
			$functions = array($functions);

		if (!isset($FANNIE_SYMBOLS['functions']))
			$FANNIE_SYMBOLS['functions'] = array();

		foreach($functions as $f)
			unset($FANNIE_SYMBOLS['functions'][$f]);
	}

	if (!is_null($classes)){
		if (!is_array($classes))
			$classes = array($classes);

		if (!isset($FANNIE_SYMBOLS['classes']))
			$FANNIE_SYMBOLS['classes'] = array();

		foreach($classes as $c)
			unset($FANNIE_SYMBOLS['classes'][$c]);
	}

	save_symbols();
}

/**
  Write current symbols to Fannie config.php

  Symbols are encoded and serialized to simplify
  saving and quoting issues.
*/
function save_symbols(){
	global $FANNIE_SYMBOLS, $FANNIE_ROOT;

	if (!is_array($FANNIE_SYMBOLS)) return False;	

	$saveStr = base64_encode(serialize($FANNIE_SYMBOLS));
	if (!function_exists('confset'))
		include_once($FANNIE_ROOT.'install/util.php');
	confset('FANNIE_SYMBOLS',"'$saveStr'");
}

/**
  Decode symbols if needed
*/
function unpack_symbols(){
	global $FANNIE_SYMBOLS;
	
	if (is_array($FANNIE_SYMBOLS)) return True;

	$FANNIE_SYMBOLS = unserialize(base64_decode($FANNIE_SYMBOLS));

	return True;
}

/**
  Include the definition for a registered function
  @param $function the function name
  @return True or False
*/
function load_function($function){
	global $FANNIE_SYMBOLS;

	if (function_exists($function))
		return True;

	unpack_symbols();

	if (isset($FANNIE_SYMBOLS['functions'][$function]))
		include_once($FANNIE_SYMBOLS['functions'][$function]);

	return function_exists($function) ? True : False;
}

/**
  Include the definition for a registered class
  @param $class the class name
  @return True or False
*/
function load_class($class){
	global $FANNIE_SYMBOLS;

	if (class_exists($class))
		return True;

	unpack_symbols();

	if (isset($FANNIE_SYMBOLS['classes'][$class])){
		include_once($FANNIE_SYMBOLS['classes'][$class]);
	}

	return class_exists($class) ? True : False;
}

/**
  Helper function for initializing symbols
  Tries to find a class in the given directory
  @param $dir search directory
  @param $class class name
*/
function bootstrap_load($dir,$class){
	$dh = opendir($dir);
	while($dh && ($file = readdir($dh)) !== False){
		if ($file[0] == ".") continue;

		if (is_dir(realpath($dir."/".$file))){
			// recurse
			if(bootstrap_load(realpath($dir."/".$file), $class))
				return True;
		}

		if ($file == $class.".php"){
			// found! return True
			include_once(realpath($dir."/".$file));
			return True;
		}
	}
	closedir($dh);
	return False;
}

function use_module($module){
	$find = load_class($module);
	if (!$find)
		return "Module $module not available";

	$obj = new $module();
	if (!is_object($obj))
		return "Problem instantiating module $module";

	$obj->run_module();
}

/**
  Find all modules
  @param $dir directory to search
  @param $ret return value by reference
  @param $base_class only modules with this parent class

  Upon return, $ret will contain an array
  of filenames keyed by class names
*/
function get_available_modules($dir, &$ret, $base_class="FannieModule"){
	if (!is_array($ret))
		$ret = array();

	$dh = opendir($dir);
	while($dh != False && ($file = readdir($dh)) !== False){
		// skip hidden files and directories
		if ($file[0] == ".") continue;
		
		// recurse through sub directories
		if (is_dir(realpath($dir."/".$file))){
			get_available_modules(realpath($dir."/".$file),$ret, $base_class);
			continue;
		}

		// skip non-php files
		if (substr($file,-4) != '.php') continue;

		$class_name = substr($file,0,strlen($file)-4);
		include_once(realpath($dir."/".$file));
		if (class_exists($class_name)){
			if (is_subclass_of($class_name,$base_class) || $class_name==$base_class)
				$ret[$class_name] = realpath($dir."/".$file);
		}
	}
	closedir($dh);
}

?>
