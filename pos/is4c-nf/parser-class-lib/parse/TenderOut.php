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
$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists("Parser")) include_once($CORE_PATH."parser-class-lib/Parser.php");
if (!function_exists("boxMsg")) include_once($CORE_PATH."lib/drawscreen.php");
if (!function_exists("addchange")) include_once($CORE_PATH."lib/additem.php");
if (!function_exists("getsubtotals")) include_once($CORE_PATH."lib/connect.php");
if (!function_exists("printReceiptfooter")) include_once($CORE_PATH."lib/listitems.php");
if (!function_exists("ttl")) include_once($CORE_PATH."lib/prehkeys.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class TenderOut extends Parser {
	function check($str){
		if ($str == "TO")
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		if ($CORE_LOCAL->get("LastID") == 0){
			$ret = $this->default_json();
			$ret['output'] = boxMsg("no transaction in progress");
			return $ret;
		}
		else {
			return $this->tender_out("");
		}
	}

	function tender_out($asTender){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		getsubtotals();
		if ($CORE_LOCAL->get("amtdue") <= 0.005) {
			$CORE_LOCAL->set("change",-1 * $CORE_LOCAL->get("amtdue"));
			$cash_return = $CORE_LOCAL->get("change");
			if ($asTender != "FS") {
				addchange($cash_return);
			}
			if ($asTender == "CK" && $cash_return > 0) {
				$CORE_LOCAL->set("cashOverAmt",1); // apbw/cvr 3/5/05 cash back beep
			}
			$CORE_LOCAL->set("End",1);
			$ret['output'] = printReceiptfooter();
			$ret['redraw_footer'] = true;
			$ret['receipt'] = 'full';
		} else {
			$CORE_LOCAL->set("change",0);
			$CORE_LOCAL->set("fntlflag",0);
			ttl();
			$ret['output'] = lastpage();
		}
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>TO</td>
				<td>Tender out. Not a WFC function; just
				reproduced for compatibility</td>
			</tr>
			</table>";
	}
}

?>
