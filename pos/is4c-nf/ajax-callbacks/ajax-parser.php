<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

ini_set('display_errors','1');

if (!isset($IS4C_LOCAL))
	include($IS4C_PATH.'lib/LocalStorage/conf.php');
if (!function_exists('array_to_json'))
	include($IS4C_PATH.'lib/array_to_json.php');
if (!function_exists('inputUnknown'))
	include($IS4C_PATH.'lib/drawscreen.php');
if (!function_exists('scaleObject'))
	include($IS4C_PATH.'lib/lib.php');

$sd = scaleObject();

/*
 * MAIN PARSING BEGINS
 */
$entered = "";
if (isset($_REQUEST["input"])) {
	$entered = strtoupper(trim($_REQUEST["input"]));
}

if (substr($entered, -2) == "CL") $entered = "CL";

if ($entered == "RI") $entered = $IS4C_LOCAL->get("strEntered");

if ($IS4C_LOCAL->get("msgrepeat") == 1 && $entered != "CL") {
	$entered = $IS4C_LOCAL->get("strRemembered");
}
$IS4C_LOCAL->set("strEntered",$entered);

$json = "";

if ($entered != ""){
	/* this breaks the model a bit, but I'm putting
	 * putting the CC parser first manually to minimize
	 * code that potentially handles the PAN */
	include_once($IS4C_PATH."cc-modules/lib/paycardEntered.php");
	$pe = new paycardEntered();
	if ($pe->check($entered)){
		$valid = $pe->parse($entered);
		$entered = "paycard";
		$IS4C_LOCAL->set("strEntered","");
		$json = array_to_json($valid);
	}

	$IS4C_LOCAL->set("quantity",0);
	$IS4C_LOCAL->set("multiple",0);

	/* FIRST PARSE CHAIN:
	 * Objects belong in the first parse chain if they
	 * modify the entered string, but do not process it
	 * This chain should be used for checking prefixes/suffixes
	 * to set up appropriate $IS4C_LOCAL variables.
	 */
	$parser_lib_path = $IS4C_PATH."parser-class-lib/";
	if (!is_array($IS4C_LOCAL->get("preparse_chain")))
		$IS4C_LOCAL->set("preparse_chain",get_preparse_chain());

	foreach ($IS4C_LOCAL->get("preparse_chain") as $cn){
		if (!class_exists("cn"))
			include_once($parser_lib_path."preparse/".$cn.".php");
		$p = new $cn();
		if ($p->check($entered))
			$entered = $p->parse($entered);
			if (!$entered || $entered == "")
				break;
	}

	if ($entered != "" && $entered != "paycard"){
		/* 
		 * SECOND PARSE CHAIN
		 * these parser objects should process any input
		 * completely. The return value of parse() determines
		 * whether to call lastpage() [list the items on screen]
		 */
		if (!is_array($IS4C_LOCAL->get("parse_chain")))
			$IS4C_LOCAL->set("parse_chain",get_parse_chain());

		$result = False;
		foreach ($IS4C_LOCAL->get("parse_chain") as $cn){
			if (!class_exists($cn))
				include_once($parser_lib_path."parse/".$cn.".php");
			$p = new $cn();
			if ($p->check($entered)){
				$result = $p->parse($entered);
				break;
			}
		}
		if ($result && is_array($result)){
			$json = array_to_json($result);
			if (isset($result['udpmsg']) && $result['udpmsg'] !== False){
				if (is_object($sd))
					$sd->WriteToScale($result['udpmsg']);
			}
		}
		else {
			$arr = array(
				'main_frame'=>false,
				'target'=>'.baseHeight',
				'output'=>inputUnknown());
			$json = array_to_json($arr);
			if (is_object($sd))
				$sd->WriteToScale('errorBeep');
		}
	}
}

$IS4C_LOCAL->set("msgrepeat",0);

if (empty($json)) $json = "{}";
echo $json;

?>
