<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class FannieAPI {

	/**
	  Initialize session to retain class
	  definition info.
	*/
	static public function init(){
		if (ini_get('session.auto_start')==0 && !headers_sent())
                        @session_start();
		if (!isset($_SESSION['FannieClassMap']))
			$_SESSION['FannieClassMap'] = array();
		elseif(!is_array($_SESSION['FannieClassMap']))
			$_SESSION['FannieClassMap'] = array();
		if (!isset($_SESSION['FannieClassMap']['SQLManager']))
			$_SESSION['FannieClassMap']['SQLManager'] = realpath(dirname(__FILE__).'/../src/SQLManager.php');
	}

	/**
	  Load definition for given class
	  @param $name the class name
	*/
	static public function LoadClass($name){
		$map = $_SESSION['FannieClassMap'];

		// class map should be array
		// of class_name => file_name
		if (!is_array($map)){ 
			$map = array();
			$_SESSION['FannieClassMap'] = array();
		}

		// if class is known in the map, include its file
		// otherwise search for an appropriate file
		if (isset($map[$name]) && !class_exists($name,False)
		   && file_exists($map[$name])){

			include_once($map[$name]);
		}
		else {
			$file = self::FindClass($name, dirname(__FILE__));
			if ($file !== False)
				include_once($file);
		}
	}

	/**
	  Search for class in given path
	  @param $name the class name
	  @param $path path to search
	  @return A filename or False
	*/
	static private function FindClass($name, $path){
		if (!is_dir($path)){
			return False;
		}

		$dh = opendir($path);
		while($dh && ($file=readdir($dh)) !== False){
			if ($file[0] == ".") continue;
			$fullname = realpath($path.'/'.$file);
			if (is_dir($fullname)){
				// recurse looking for file
				$file = self::FindClass($name, $fullname);
				if ($file !== False) return $file;
			}
			elseif (substr($file,-4) == '.php'){
				// map all PHP files as long as we're searching
				// but only return if the correct file is found
				$class = substr($file,0,strlen($file)-4);
				$_SESSION['FannieClassMap'][$class] = $fullname;
				if ($class == $name) return $fullname;
			}
		}

		return False;
	}
}

FannieAPI::init();
if (function_exists('spl_autoload_register')){
	spl_autoload_register(array('FannieAPI','LoadClass'));
}
else {
	function __autoload($name){
		FannieAPI::LoadClass($name);
	}
}

?>
