<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of IT CORE.

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

if (!isset($CORE_LOCAL)) include_once(dirname(__FILE__).'/LocalStorage/conf.php');

/**
  @class LibraryClass
  Class for defining library functions.
  All methods should be static.

  This exists to make documented hierarchy
  more sensible.
*/
class LibraryClass {
}

/**
  @class AutoLoader
  Map available modules and register automatic
  class loading
*/
class AutoLoader extends LibraryClass {

	/**
	  Autoload class by name
	  @param $name class name
	*/
	static public function LoadClass($name){
		global $CORE_LOCAL;
		$map = $CORE_LOCAL->get("ClassLookup");
		if (!is_array($map)) return;

		if (isset($map[$name]) && !class_exists($name)
		   && file_exists($map[$name])){

			include_once($map[$name]);

		}
	}

	/**
	  Map available classes. Class names should
	  match filenames for lookups to work.
	*/
	static public function LoadMap(){
		global $CORE_LOCAL;
		$class_map = array();
		$search_path = realpath(dirname(__FILE__).'/../');
		self::RecursiveLoader($search_path, $class_map);
		$CORE_LOCAL->set("ClassLookup",$class_map);
	}

	/**
	  Helper function to walk through file structure
	  @param $path starting path
	  @param $map array of class name => file
	  @return $map (by reference)
	*/
	static private function RecursiveLoader($path,&$map=array()){
		if(!is_dir($path)) return $map;

		$dh = opendir($path);
		while($dh && ($file=readdir($dh)) !== False){
			if ($file[0] == ".") continue;
			$fullname = realpath($path."/".$file);
			if (is_dir($fullname)){
				self::RecursiveLoader($fullname, $map);
			}
			else if (substr($file,-4) == '.php'){
				$class = substr($file,0,strlen($file)-4);
				$map[$class] = $fullname;
			}
		}
		closedir($dh);
	}

}

if (function_exists('spl_autoload_register')){
	spl_autoload_register(array('AutoLoader','LoadClass'));
}
else {
	function __autoload($name){
		AutoLoader::LoadClass($name);
	}
}

/** internationalization */
setlocale(LC_MESSAGES, "en_US.UTF-8");
bindtextdomain("pos-nf",realpath(dirname(__FILE__).'/../locale'));
bind_textdomain_codeset("pos-nf","UTF-8");
textdomain("pos-nf");

?>
