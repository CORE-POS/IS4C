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
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("Parser")) include_once($IS4C_PATH."parser-class-lib/Parser.php");
if (!function_exists("tender")) include_once($IS4C_PATH."lib/prehkeys.php");
if (!function_exists("boxMsg")) include_once($IS4C_PATH."lib/drawscreen.php");
if (!function_exists("boxMsgscreen")) include_once($IS4C_PATH."lib/clientscripts.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class SimpleTender extends Parser {
	var $stored_ret;
	function check($str){
		global $IS4C_LOCAL,$IS4C_PATH;
		$this->stored_ret = $this->default_json();
		switch($str){
		case "TA":
			$this->stored_ret = tender("TA", 100 * $IS4C_LOCAL->get("runningTotal"));
			return True;
		case "EC":
			$this->stored_ret = tender("EC", 100 * $IS4C_LOCAL->get("runningTotal"));
			return True;
		case "FS":
			$this->stored_ret['output'] = boxMsg("EBT tender must specify amount");
			return True;
		case "EF":
			$this->stored_ret = tender("EF", 100 * $IS4C_LOCAL->get("fsEligible"));
			return True;
		case "TB":
		case "CC":
			/* if just CC is entered without an amount, assume 
			 * the entire purchase will be CC. Throw up a 
			 * proceed / cancel box, then do the tender */
			if ($IS4C_LOCAL->get("LastID") == 0)
				$this->stored_ret['output'] = boxMsg("no transaction in progress");
			elseif ($IS4C_LOCAL->get("warned") == 1 and $IS4C_LOCAL->get("warnBoxType")== "warnCC"){
				$IS4C_LOCAL->set("warnBoxType","");
				$IS4C_LOCAL->set("warned",0);
				$this->stored_ret = tender("CC", "".$IS4C_LOCAL->get("runningTotal") * 100);
			}
			elseif ($IS4C_LOCAL->get("ttlflag") == 1){
				$IS4C_LOCAL->set("warnBoxType","warnCC");
				$IS4C_LOCAL->set("warned",1);
				$IS4C_LOCAL->set("boxMsg","<BR>charge $".$IS4C_LOCAL->get("runningTotal")." to credit card</B><BR>press [enter] to continue<P><FONT size='-1'>[clear] to cancel</FONT>");
				$this->stored_ret['main_frame'] = $IS4C_PATH.'gui-modules/boxMsg2.php';
			}
			else 
				$this->stored_ret['output'] = boxMsg("transaction must be totaled<br>before tender can be<br>accepted"); 
			return True;
		case "CK":
			/* same as CC above but for CK
			   set endorseType for check franking to work */
			if ($IS4C_LOCAL->get("LastID") == 0)
				$this->stored_ret['output'] = boxMsg("no transaction in progress");
			elseif ($IS4C_LOCAL->get("warned") == 1 and $IS4C_LOCAL->get("warnBoxType") == "warnCK"){
				$IS4C_LOCAL->set("warnBoxType","");
				$IS4C_LOCAL->set("warned",0);
			}
			elseif ($IS4C_LOCAL->get("ttlflag") == 1){
				$ref = trim($IS4C_LOCAL->get("CashierNo"))."-"
					.trim($IS4C_LOCAL->get("laneno"))."-"
					.trim($IS4C_LOCAL->get("transno"));
				$msg = "<BR>insert check for $".$IS4C_LOCAL->get("runningTotal")."</B><BR>press [enter] to endorse<P><FONT size='-1'>[clear] to cancel</FONT>";
				if ($IS4C_LOCAL->get("LastEquityReference") == $ref){
					$msg .= "<div style=\"background:#993300;color:#ffffff;
						margin:3px;padding: 3px;\">
						There was an equity sale on this transaction. Did it get
						endorsed yet?</div>";
				}
				$IS4C_LOCAL->set("boxMsg",$msg);
				$IS4C_LOCAL->set("endorseType","check");
				$IS4C_LOCAL->set("strEntered",$IS4C_LOCAL->get("runningTotal")*100);
				$IS4C_LOCAL->set("tenderamt",$IS4C_LOCAL->get("runningTotal"));
				$IS4C_LOCAL->set("strEntered",$IS4C_LOCAL->get("strEntered")."CK");
				$this->stored_ret['main_frame'] = $IS4C_PATH.'gui-modules/boxMsg2.php';
			}
			else 
				$this->stored_ret['output'] = boxMsg("transaction must be totaled<br>before tender can be<br>accepted");
			return True;
		case "CX":
			$this->stored_ret = tender("CX", $IS4C_LOCAL->get("runningTotal") * 100);
			return True;
		case "SC":
			if ($IS4C_LOCAL->get("LastID") == 0 ) 
				$this->stored_ret['output'] = boxMsg("no transaction in progress");
			elseif ($IS4C_LOCAL->get("ttlflag") == 1) 
				$this->stored_ret = tender("SC", "".$IS4C_LOCAL->get("runningTotal") * 100);
			else 
				$this->stored_ret['output'] = boxMsg("transaction must be totaled<br>before tender can be<br>accepted"); 
			return True;	
		case "MI":
			if ($IS4C_LOCAL->get("LastID") == 0 ) 
				$this->stored_ret['output'] = boxMsg("no transaction in progress");
			elseif ($IS4C_LOCAL->get("ttlflag") == 1) 
				$this->stored_ret = tender("MI", "".$IS4C_LOCAL->get("runningTotal") * 100);
			elseif ($IS4C_LOCAL->get("memberID") != "0" && $IS4C_LOCAL->get("isStaff") == 1) {
				$this->stored_ret = tender("MI", "".$IS4C_LOCAL->get("runningTotal") * 100);
			}
			elseif ($IS4C_LOCAL->get("memberID") != "0" && $IS4C_LOCAL->get("isStaff") == 0)
				$this->stored_ret = xboxMsg("member ".$IS4C_LOCAL->get("memberID")."<BR>is not authorized to make employee charges");
			else {
				$IS4C_LOCAL->set("mirequested",1);
				$IS4C_LOCAL->set("away",1);
				$IS4C_LOCAL->set("search_or_list",1);
				$this->stored_ret['main_frame'] = $IS4C_PATH.'gui-modules/memlist.php';
			}
			return True;
		}
		return False;
	}

	function parse($str){
		return $this->stored_ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>tender code</i></td>
				<td>Try to tender the current total
				to the specified tender (e.g., CC)</td>
			</tr>
			</table>";
	}
}

?>
