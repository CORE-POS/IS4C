<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

class Parser {

	function check($str){
	
	}

	function parse($str){

	}

	function isLast(){
		return False;
	}

	function isFirst(){
		return False;
	}

	function doc(){
		return "Developer didn't document this module very well";
	}

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

function get_parse_chain(){
	global $IS4C_PATH;
	$PARSEROOT = $IS4C_PATH."parser-class-lib";

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

function get_preparse_chain(){
	global $IS4C_PATH;

	$PARSEROOT = $IS4C_PATH."parser-class-lib";

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

?>
