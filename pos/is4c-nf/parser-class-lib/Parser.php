<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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
  @class Parser
  The base module for parsing input

  Parser objects are divided into
  two varieties that behave slightly
  differently. 

  Modules in the preparse directory
  are all checked for every input. These
  modueles may modify the input string.

  Modules in the parse are only checked
  until one matches the input. Input processing
  ceases and the matching module must decide
  what to do next.
*/
class Parser {

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

	  Parse modules a keyed array: 
	   - main_frame If set, change page to this URL
	   - output HTML output to be displayed
	   - target Javascript selector string describing which
	     element should contain the output
	   - redraw_footer True or False. Set to True if
	     totals have changed.
	   - receipt False or string type. Print a receipt with
	     the given type.
	   - scale Update the scale display and session variables
	   - udpmsg False or string. Send a message to hardware
	     device(s)
	   - retry False or string. Try the input again shortly.

	   The utility method default_json() provides an array
	   with the proper keys and sane default values.
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
	  A return array for parse() with proper keys
	  @return array
	
	  See parse() method
	*/
	function default_json(){
		return array(
			'main_frame'=>false,
			'target'=>'.baseHeight',
			'output'=>false,
			'redraw_footer'=>false,
			'receipt'=>false,
			'scale'=>false,
			'udpmsg'=>false,
			'retry'=>false
			);
	}
}

/** @file 
    @brief Defines parser module and includes a couple utility functions
*/

/**
  Gather parse modules
  @return array of Parser class names

  Scan the parse directory for module files.
  Return an array of available modules.
*/
function get_parse_chain(){
	global $CORE_PATH;
	$PARSEROOT = $CORE_PATH."parser-class-lib";

	$parse_chain = array();
	$first = "";
	$dh = opendir($PARSEROOT."/parse");
	while (False !== ($file=readdir($dh))){
		if (is_file($PARSEROOT."/parse/".$file) &&
		    substr($file,-4)==".php"){

			$classname = substr($file,0,strlen($file)-4);
			if (!class_exists($classname))
				include_once($PARSEROOT."/parse/".$file);
			$instance = new $classname();
			if ($instance->isLast())
				array_push($parse_chain,$classname);
			elseif ($instance->isFirst())
				$first = $classname;
			else
				array_unshift($parse_chain,$classname);

		}
	}
	closedir($dh);
	if ($first != "")
		array_unshift($parse_chain,$first);

	return $parse_chain;
}

/**
  Gather preparse modules
  @return array of Parser class names

  Scan the preparse directory for module files.
  Return an array of available modules.
*/
function get_preparse_chain(){
	global $CORE_PATH;

	$PARSEROOT = $CORE_PATH."parser-class-lib";

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

/**
  @example HW_Parser.php

  The first two lines are standard path detection. With a Parser
  module something else <i>probably</i> set this already but
  better safe than sorry.

  The next block demonstrates standard include format
  (check first, use detected path). 
  $CORE_LOCAL is an important global containing session values

  check() looks for input the module can handle. In this case
  the module simply watches for the string "HW".

  parse() demonstrates a couple options when the correct input
  is detected. If a transaction is in progress, it displays
  an error message. Otherwise, it sends the browser to
  a different display script.

  N.B. the HelloWorld display module is just an example; that
  file does not exist in the gui-modules directory.
*/

?>
