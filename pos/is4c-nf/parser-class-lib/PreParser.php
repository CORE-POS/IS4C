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

/**
  @class PreParser
  The base module for preparsing input

  Preparse modules
  are all checked for every input. These
  modueles may modify the input string.

*/
class PreParser {

	/**
	  Check whether the module handles this input
	  @param $str The input string
	  @return 
	   - True The module handles this input.
	     The parse method() will be called next.
	   - False The module does not handle this input.
	     The parse method() will not be called and
	     processing will proceed to the next Parser module.

	*/
	function check($str){
	
	}

	/**
	  Deal with the input
	  @param $str The input string
	  @return mixed

	  Preparse modules should return a string. This
	  value will replace the input string for remaining
	  parsing.

	*/
	function parse($str){

	}

	/**
	  Make this module last
	  @return True or False

	  Modules are not run in any guaranteed order.
	  Return True will force this module to be last.

	  BE VERY VERY CAREFUL IF YOU OVERRIDE THIS.
	  Quantity is the last preparse module and
	  DefaultTender is the last parse module. Making
	  your own module last will break one of these
	  and probably make a mess.
	*/
	function isLast(){
		return False;
	}

	/**
	  Make this module first
	  @return True or False

	  Modules are not run in any guaranteed order.
	  Return True will force this module to be first
	  (or nearly first if multiple modules override
	  this method)
	*/
	function isFirst(){
		return False;
	}

	/**
	  Display documentation
	  @return A string describing the module
	
	  Ideally you should note what your module it does
	  and what the input format is.
	*/
	function doc(){
		return "Developer didn't document this module very well";
	}

	/**
	  Gather preparse modules
	  @return array of Parser class names

	  Scan the preparse directory for module files.
	  Return an array of available modules.
	*/
	static public function get_preparse_chain(){

		return AutoLoader::ListModules('PreParser');

		$PARSEROOT = realpath(dirname(__FILE__));

		$preparse_chain = array();
		$dh = opendir($PARSEROOT."/preparse");
		$first = "";
		while (False !== ($file=readdir($dh))){
			if (is_file($PARSEROOT."/preparse/".$file) &&
			    substr($file,-4)==".php"){

				$classname = substr($file,0,strlen($file)-4);
				if (!class_exists($classname))
					include_once($PARSEROOT."/preparse/".$file);
				$instance = new $classname();
				if ($instance->isLast())
					array_push($preparse_chain,$classname);
				elseif ($instance->isFirst())
					$first = $classname;
				else
					array_unshift($preparse_chain,$classname);

			}
		}
		closedir($dh);
		if ($first != "")
			array_unshift($preparse_chain,$first);

		return $preparse_chain;
	}

}

?>
