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
if (!function_exists("tDataConnect")) include_once($CORE_PATH."lib/connect.php");
if (!function_exists("drawerKick")) include_once($CORE_PATH."lib/printLib.php");
if (!function_exists("setglobalvalue")) include_once($CORE_PATH."lib/loadconfig.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

/* 
 * This class is for any input designed to set processing
 * to an alternate gui module. That's how the particular
 * olio of seemingly unrelated inputs gets caught here
 */
class Steering extends Parser {
	var $dest_input_page;
	var $dest_main_page;
	var $dest_scale;
	var $ret;

	function check($str){
		global $CORE_LOCAL,$CORE_PATH;
		
		$this->dest_input_page = "";
		$this->dest_main_page = "";
		$this->dest_scale = False;
		$this->ret = $this->default_json();

		switch($str){
			
		case 'CAB':
			if ($CORE_LOCAL->get("LastID") != "0")
				$this->ret['output'] = boxMsg("transaction in progress");
			else {
				$this->ret['main_frame'] = $CORE_PATH."gui-modules/cablist.php";
			}
			return True;
		case "PV":
			$CORE_LOCAL->set("pvsearch","");
			$CORE_LOCAL->set("away",1);
			$this->ret['main_frame'] = $CORE_PATH."gui-modules/productlist.php";
			return True;
		/*
		case "PV2":
			$CORE_LOCAL->set("pvsearch","");
			$CORE_LOCAL->set("away",1);
			$this->ret['main_frame'] = "/gui-modules/smartItemList.php";
			return True;
		*/
		/*
		case "PROD":
			$this->ret['main_frame'] = "/gui-modules/productDump.php";
			return True;
		 */
		case "UNDO":
			if ($CORE_LOCAL->get("LastID") != "0")
				$this->ret['output'] = boxMsg("transaction in progress");
			else {
				$CORE_LOCAL->set("adminRequest",$CORE_PATH."gui-modules/undo.php");
				$CORE_LOCAL->set("adminRequestLevel","30");
				$CORE_LOCAL->set("adminLoginMsg","Login to void transactions");
				$CORE_LOCAL->set("away",1);
				$this->ret['main_frame'] = $CORE_PATH."gui-modules/adminlogin.php";
			}
			return True;
		case "DDD":
			$CORE_LOCAL->set("adminRequest",$CORE_PATH."ajax-callbacks/ddd.php");
			$CORE_LOCAL->set("adminLoginMsg","DDD these items?");
			$CORE_LOCAL->set("adminRequestLevel","10");
			$CORE_LOCAL->set("away",1);
			$this->ret['main_frame'] = $CORE_PATH."gui-modules/adminlogin.php";
			return True;
		case 'MG':
			$CORE_LOCAL->set("away",1);
			$this->ret['main_frame'] = $CORE_PATH."gui-modules/adminlist.php";
			return True;
		case 'RP':
			if ($CORE_LOCAL->get("LastID") != "0")
				$this->ret['output'] = boxMsg("transaction in progress");
			else {
				$query = "select register_no, emp_no, trans_no, "
					."sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
					."from localtranstoday where register_no = ".$CORE_LOCAL->get("laneno")
					." and emp_no = ".$CORE_LOCAL->get("CashierNo")
					." group by register_no, emp_no, trans_no order by 1000 - trans_no";
				$db = tDataConnect();
				$result = $db->query($query);
				$num_rows = $db->num_rows($result);
				$db->close();

				if ($num_rows == 0) 
					$this->ret['output'] = boxMsg("no receipt found");
				else {
					$this->ret['main_frame'] = $CORE_PATH."gui-modules/rplist.php";
				}
			}				
			return True;
		case 'ID':
			$CORE_LOCAL->set("away",1);
			$CORE_LOCAL->set("search_or_list",1);
			$this->ret['main_frame'] = $CORE_PATH."gui-modules/memlist.php";
			return True;
		case 'SO':
			if ($CORE_LOCAL->get("LastID") != 0) 
				$this->ret['output'] = boxMsg("Transaction in Progress");
			else {
				setglobalvalue("LoggedIn", 0);
				$CORE_LOCAL->set("LoggedIn",0);
				drawerKick();
				$CORE_LOCAL->set("training",0);
				$CORE_LOCAL->set("gui-scale","no");
				$CORE_LOCAL->set("away",1);
				$this->ret['main_frame'] = $CORE_PATH."gui-modules/login2.php";
			}
			return True;
		case 'NS':
			if ($CORE_LOCAL->get("LastID") != 0) 
				$this->ret['output'] = boxMsg("Transaction in Progress");
			else {
				$CORE_LOCAL->set("away",1);
				$this->ret['main_frame'] = $CORE_PATH."gui-modules/nslogin.php";
			}
			return True;
		case 'GD':
			$CORE_LOCAL->set("msgrepeat",0);
			$this->ret['main_frame'] = $CORE_PATH."gui-modules/giftcardlist.php";
			return True;
		/*
		case 'CCM':
			$CORE_LOCAL->set("msgrepeat",0);
			$this->ret['main_frame'] = "/gui-modules/cclist.php";
			return True;
		*/
		case "CN":
			if ($CORE_LOCAL->get("runningTotal") == 0) {
				$CORE_LOCAL->set("receiptType","cancelled");
				$CORE_LOCAL->set("msg",2);
				$this->ret['receipt'] = 'cancelled';
				$this->ret['output'] = printheaderb();
				$this->ret['output'] .= plainmsg("transaction cancelled");
			}
			else {
				$CORE_LOCAL->set("away",1);
				$this->ret['main_frame'] = $CORE_PATH."gui-modules/mgrlogin.php";
			}
			return True;
		case "CE":
			$this->ret['main_frame'] = $CORE_PATH."cc-modules/gui/ProcessPage.php";
			return True;
		}
		return False;
	}

	function parse($str){
		return $this->ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<td colspan=2>This module gets used
				for a lot of seemingly disparate things.
				What they have in common is they all involve
				going to a different display page</td>
			</tr>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>PV</td>
				<td>Search for a product</td>
			</tr>
			<tr>
				<td>PROD</td>
				<td>Dump status of a product</td>
			</tr>
			<tr>
				<td>UNDO</td>
				<td>Reverse an entire transaction</td>
			</tr>
			<tr>
				<td>MG</td>
				<td>Suspend/resume transactions,
				print tender reports</td>
			</tr>
			<tr>
				<td>RP</td>
				<td>Reprint a receipt</td>
			</tr>
			<tr>
				<td>ID</td>
				<td>Search for a member</td>
			</tr>
			<tr>
				<td>SO</td>
				<td>Sign out register</td>
			</tr>
			<tr>
				<td>NS</td>
				<td>No sale</td>
			</tr>
			<tr>
				<td>GD</td>
				<td>Integrated gift card menu</td>
			</tr>
			<tr>
				<td>CN</td>
				<td>Cancel transaction</td>
			</tr>
			</table>";
	}
}

?>
