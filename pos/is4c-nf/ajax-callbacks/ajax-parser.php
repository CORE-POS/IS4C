<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

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

ini_set('display_errors','Off');
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

$sd = MiscLib::scaleObject();
$st = MiscLib::sigTermObject();

/*
 * MAIN PARSING BEGINS
 */
$entered = "";
if (isset($_REQUEST["input"])) {
	$entered = strtoupper(trim($_REQUEST["input"]));
}

if (substr($entered, -2) == "CL") $entered = "CL";

if ($entered == "RI") $entered = $CORE_LOCAL->get("strEntered");

if ($CORE_LOCAL->get("msgrepeat") == 1 && $entered != "CL") {
	$entered = $CORE_LOCAL->get("strRemembered");
}
$CORE_LOCAL->set("strEntered",$entered);

$json = "";

if ($entered != ""){
	/* this breaks the model a bit, but I'm putting
	 * putting the CC parser first manually to minimize
	 * code that potentially handles the PAN */
	if (in_array("Paycards",$CORE_LOCAL->get("PluginList"))){
		/* this breaks the model a bit, but I'm putting
		 * putting the CC parser first manually to minimize
		 * code that potentially handles the PAN */
		if($CORE_LOCAL->get("PaycardsCashierFacing")=="1" && substr($entered,0,9) == "PANCACHE:"){
			/* cashier-facing device behavior; run card immediately */
			$entered = substr($entered,9);
			$CORE_LOCAL->set("CachePanEncBlock",$entered);
		}

		$pe = new paycardEntered();
		if ($pe->check($entered)){
			$valid = $pe->parse($entered);
			$entered = "PAYCARD";
			$CORE_LOCAL->set("strEntered","");
			$json = $valid;
		}
	}

	$CORE_LOCAL->set("quantity",0);
	$CORE_LOCAL->set("multiple",0);

	/* FIRST PARSE CHAIN:
	 * Objects belong in the first parse chain if they
	 * modify the entered string, but do not process it
	 * This chain should be used for checking prefixes/suffixes
	 * to set up appropriate $CORE_LOCAL variables.
	 */
	$parser_lib_path = MiscLib::base_url()."parser-class-lib/";
	if (!is_array($CORE_LOCAL->get("preparse_chain")))
		$CORE_LOCAL->set("preparse_chain",PreParser::get_preparse_chain());

	foreach ($CORE_LOCAL->get("preparse_chain") as $cn){
		if (!class_exists($cn)) continue;
		$p = new $cn();
		if ($p->check($entered))
			$entered = $p->parse($entered);
			if (!$entered || $entered == "")
				break;
	}

	if ($entered != "" && $entered != "PAYCARD"){
		/* 
		 * SECOND PARSE CHAIN
		 * these parser objects should process any input
		 * completely. The return value of parse() determines
		 * whether to call lastpage() [list the items on screen]
		 */
		if (!is_array($CORE_LOCAL->get("parse_chain")))
			$CORE_LOCAL->set("parse_chain",Parser::get_parse_chain());

		$result = False;
		foreach ($CORE_LOCAL->get("parse_chain") as $cn){
			if (!class_exists($cn)) continue;
			$p = new $cn();
			if ($p->check($entered)){
				$result = $p->parse($entered);
				break;
			}
		}
		if ($result && is_array($result)) {

            // postparse chain: modify result
            if (!is_array($CORE_LOCAL->get("postparse_chain"))) {
                $CORE_LOCAL->set("postparse_chain",PostParser::getPostParseChain());
            }
            foreach ($CORE_LOCAL->get('postparse_chain') as $class) {
                if (!class_exists($class)) {
                    continue;
                }
                $obj = new $class();
                $result = $obj->parse($result);
            }

			$json = $result;
			if (isset($result['udpmsg']) && $result['udpmsg'] !== False){
				if (is_object($sd))
					$sd->WriteToScale($result['udpmsg']);
				if (is_object($st))
					$st->WriteToScale($result['udpmsg']);
			}
		}
		else {
			$arr = array(
				'main_frame'=>false,
				'target'=>'.baseHeight',
				'output'=>DisplayLib::inputUnknown());
			$json = $arr;
			if (is_object($sd))
				$sd->WriteToScale('errorBeep');
		}
	}
}

$CORE_LOCAL->set("msgrepeat",0);

if (empty($json)) echo "{}";
else {
	if (isset($json['redraw_footer']) && $json['redraw_footer'] !== False){
		$json['redraw_footer'] = DisplayLib::printfooter();
	}
	if (isset($json['scale']) && $json['scale'] !== False){
		$display = DisplayLib::scaledisplaymsg($json['scale']);
		if (is_array($display))
			$json['scale'] = $display['display'];
		else
			$json['scale'] = $display;
		$term_display = DisplayLib::drawNotifications();
		if (!empty($term_display))
			$json['term'] = $term_display;
	}
	echo JsonLib::array_to_json($json);
}

?>
