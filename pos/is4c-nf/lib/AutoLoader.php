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
class AutoLoader extends LibraryClass 
{

	/**
	  Autoload class by name
	  @param $name class name
	*/
	static public function loadClass($name)
    {
		global $CORE_LOCAL;
		$map = $CORE_LOCAL->get("ClassLookup");
		if (!is_array($map)) {
            return;
        }

		if (isset($map[$name]) && !file_exists($map[$name])) {
			// file is missing. 
			// rebuild map to see if the class is
			// gone or the file just moved
			self::loadMap();
			$map = $CORE_LOCAL->get("ClassLookup");
			if (!is_array($map)) {
                return;
            }
		} else if (!isset($map[$name])) {
			// class is unknown
			// rebuild map to see if the definition
			// file has been added
			self::loadMap();
			$map = $CORE_LOCAL->get("ClassLookup");
			if (!is_array($map)) {
                return;
            }
		}

		if (isset($map[$name]) && !class_exists($name,false)
		   && file_exists($map[$name])) {

			include_once($map[$name]);
		}
	}

	/**
	  Map available classes. Class names should
	  match filenames for lookups to work.
	*/
	static public function loadMap()
    {
		global $CORE_LOCAL;
		$class_map = array();
		$search_path = realpath(dirname(__FILE__).'/../');
		self::recursiveLoader($search_path, $class_map);
		$CORE_LOCAL->set("ClassLookup",$class_map);
	}

	/**
	  Get a list of available modules with the
	  given base class
	  @param $base_class string class name
	  @param $include_base whether base class should be included
		in the return value
	  @return an array of class names
	*/
	static public function listModules($base_class, $include_base=False)
    {
		$ret = array();
		
		// lookup plugin modules, then standard modules
		$map = Plugin::pluginMap();
		switch($base_class){
            case 'DiscountType':
                $path = realpath(dirname(__FILE__).'/Scanning/DiscountTypes');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'FooterBox':
                $path = realpath(dirname(__FILE__).'/FooterBoxes');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'Kicker':
                $path = realpath(dirname(__FILE__).'/Kickers');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'Parser':
                $path = realpath(dirname(__FILE__).'/../parser-class-lib/parse');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'PreParser':
                $path = realpath(dirname(__FILE__).'/../parser-class-lib/preparse');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'PriceMethod':
                $path = realpath(dirname(__FILE__).'/Scanning/PriceMethods');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'SpecialUPC':
                $path = realpath(dirname(__FILE__).'/Scanning/SpecialUPCs');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'SpecialDept':
                $path = realpath(dirname(__FILE__).'/Scanning/SpecialDepts');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'TenderModule':
                $path = realpath(dirname(__FILE__).'/Tenders');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'TenderReport':
                $path = realpath(dirname(__FILE__).'/ReceiptBuilding/TenderReports');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'DefaultReceiptDataFetch':
                $path = realpath(dirname(__FILE__).'/ReceiptBuilding/ReceiptDataFetch');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'DefaultReceiptFilter':
                $path = realpath(dirname(__FILE__).'/ReceiptBuilding/ReceiptFilter');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'DefaultReceiptSort':
                $path = realpath(dirname(__FILE__).'/ReceiptBuilding/ReceiptSort');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'DefaultReceiptTag':
                $path = realpath(dirname(__FILE__).'/ReceiptBuilding/ReceiptTag');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'ReceiptMessage':
                $path = realpath(dirname(__FILE__).'/ReceiptBuilding/Messages');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'CustomerReceiptMessage':
                $path = realpath(dirname(__FILE__).'/ReceiptBuilding/custMessages');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'ProductSearch':
                $path = realpath(dirname(__FILE__).'/Search/Products');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'DiscountModule':
                $map['DiscountModule'] = realpath(dirname(__FILE__).'/DiscountModule.php');
                break;
            case 'MemberLookup':
                $map['MemberLookup'] = realpath(dirname(__FILE__).'/MemberLookup.php');
                break;
            case 'PrintHandler':
                $path = realpath(dirname(__FILE__).'/PrintHandlers');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'BasicModel':
                $path = realpath(dirname(__FILE__).'/models');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'TotalAction':
                $path = realpath(dirname(__FILE__).'/TotalActions');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'VariableWeightReWrite':
                $path = realpath(dirname(__FILE__).'/Scanning/VariableWeightReWrites');
                $map = Plugin::pluginMap($path,$map);
                break;
            case 'ItemNotFound':
                $map['ItemNotFound'] = realpath(dirname(__FILE__) . '/ItemNotFound.php');
                break;
		}

		foreach($map as $name => $file) {

			// matched base class
			if ($name === $base_class) {
				if ($include_base) $ret[] = $name;
				continue;
			}

			ob_start();
			if (!class_exists($name)) { 
				ob_end_clean();
				continue;
			}

			if (strstr($file,'plugins')) {
				$parent = Plugin::memberOf($file);
				if ($parent && Plugin::isEnabled($parent) && is_subclass_of($name,$base_class)) {
					$ret[] = $name;
				} else if ($base_class=="Plugin" && is_subclass_of($name,$base_class)) {
					$ret[] = $name;
                }
			} else {
				if (is_subclass_of($name,$base_class)) {
					$ret[] = $name;
                }
			}
			ob_end_clean();
		}

		return $ret;
	}

	/**
	  Helper function to walk through file structure
	  @param $path starting path
	  @param $map array of class name => file
	  @return $map (by reference)
	*/
	static private function recursiveLoader($path,&$map=array())
    {
		if(!is_dir($path)) {
            return $map;
        }

		$dh = opendir($path);
		while($dh && ($file=readdir($dh)) !== false) {
			if ($file[0] == ".") continue;

			$fullname = realpath($path."/".$file);
			if (is_dir($fullname) && $file != "gui-modules") {
				self::recursiveLoader($fullname, $map);
			} else if (substr($file,-4) == '.php') {
				$class = substr($file,0,strlen($file)-4);
				$map[$class] = $fullname;
			}
		}
		closedir($dh);
	}

}

if (function_exists('spl_autoload_register')){
	spl_autoload_register(array('AutoLoader','loadClass'));
}
else {
	function __autoload($name){
		AutoLoader::loadClass($name);
	}
}

/** internationalization */
/*
setlocale(LC_MESSAGES, "en_US.UTF-8");
bindtextdomain("pos-nf",realpath(dirname(__FILE__).'/../locale'));
bind_textdomain_codeset("pos-nf","UTF-8");
textdomain("pos-nf");
 */

