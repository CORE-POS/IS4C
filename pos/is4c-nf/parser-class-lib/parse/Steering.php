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

if (!class_exists("Parser")) include_once($_SERVER["DOCUMENT_ROOT"]."/parser-class-lib/Parser.php");
if (!function_exists("boxMsg")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/drawscreen.php");
if (!function_exists("tDataConnect")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/connect.php");
if (!function_exists("drawerKick")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/printLib.php");
if (!function_exists("setglobalvalue")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/loadconfig.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

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
		global $IS4C_LOCAL;
		
		$this->dest_input_page = "";
		$this->dest_main_page = "";
		$this->dest_scale = False;
		$this->ret = $this->default_json();

		switch($str){
			
		case 'CAB':
			if ($IS4C_LOCAL->get("LastID") != "0")
				$this->ret['output'] = boxMsg("transaction in progress");
			else {
				$this->ret['main_frame'] = "/gui-modules/cablist.php";
			}
			return True;
		case "PV":
			$IS4C_LOCAL->set("pvsearch","");
			$IS4C_LOCAL->set("away",1);
			$this->ret['main_frame'] = "/gui-modules/productlist.php";
			return True;
		/*
		case "PV2":
			$IS4C_LOCAL->set("pvsearch","");
			$IS4C_LOCAL->set("away",1);
			$this->ret['main_frame'] = "/gui-modules/smartItemList.php";
			return True;
		*/
		/*
		case "PROD":
			$this->ret['main_frame'] = "/gui-modules/productDump.php";
			return True;
		 */
		case "UNDO":
			if ($IS4C_LOCAL->get("LastID") != "0")
				$this->ret['output'] = boxMsg("transaction in progress");
			else {
				$IS4C_LOCAL->set("adminRequest","/gui-modules/undo.php");
				$IS4C_LOCAL->set("adminRequestLevel","30");
				$IS4C_LOCAL->set("adminLoginMsg","Login to void transactions");
				$IS4C_LOCAL->set("away",1);
				$this->ret['main_frame'] = "/gui-modules/adminlogin.php";
			}
			return True;
		case "DDD":
			$IS4C_LOCAL->set("adminRequest","/ddd.php");
			$IS4C_LOCAL->set("adminLoginMsg","DDD these items?");
			$IS4C_LOCAL->set("adminRequestLevel","10");
			$IS4C_LOCAL->set("away",1);
			$this->ret['main_frame'] = "/gui-modules/adminlogin.php";
			return True;
		case 'MG':
			$IS4C_LOCAL->set("away",1);
			$this->ret['main_frame'] = "/gui-modules/adminlist.php";
			return True;
		case 'RP':
			if ($IS4C_LOCAL->get("LastID") != "0")
				$this->ret['output'] = boxMsg("transaction in progress");
			else {
				$query = "select register_no, emp_no, trans_no, "
					."sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
					."from localtranstoday where register_no = ".$IS4C_LOCAL->get("laneno")
					." and emp_no = ".$IS4C_LOCAL->get("CashierNo")
					." group by register_no, emp_no, trans_no order by 1000 - trans_no";
				$db = tDataConnect();
				$result = $db->query($query);
				$num_rows = $db->num_rows($result);
				$db->close();

				if ($num_rows == 0) 
					$this->ret['output'] = boxMsg("no receipt found");
				else {
					$this->ret['main_frame'] = "/gui-modules/rplist.php";
				}
			}				
			return True;
		case 'ID':
			$IS4C_LOCAL->set("away",1);
			$IS4C_LOCAL->set("search_or_list",1);
			$this->ret['main_frame'] = "/gui-modules/memlist.php";
			return True;
		case 'SO':
			if ($IS4C_LOCAL->get("LastID") != 0) 
				$this->ret['output'] = boxMsg("Transaction in Progress");
			else {
				setglobalvalue("LoggedIn", 0);
				$IS4C_LOCAL->set("LoggedIn",0);
				drawerKick();
				$IS4C_LOCAL->set("training",0);
				$IS4C_LOCAL->set("gui-scale","no");
				$IS4C_LOCAL->set("away",1);
				$this->ret['main_frame'] = "/gui-modules/login2.php";
			}
			return True;
		case 'NS':
			if ($IS4C_LOCAL->get("LastID") != 0) 
				$this->ret['output'] = boxMsg("Transaction in Progress");
			else {
				$IS4C_LOCAL->set("away",1);
				$this->ret['main_frame'] = "/gui-modules/nslogin.php";
			}
			return True;
		case 'GD':
			$IS4C_LOCAL->set("msgrepeat",0);
			$this->ret['main_frame'] = "/gui-modules/giftcardlist.php";
			return True;
		/*
		case 'CCM':
			$IS4C_LOCAL->set("msgrepeat",0);
			$this->ret['main_frame'] = "/gui-modules/cclist.php";
			return True;
		*/
		case "CN":
			if ($IS4C_LOCAL->get("runningTotal") == 0) {
				$IS4C_LOCAL->set("receiptType","cancelled");
				$IS4C_LOCAL->set("msg",2);
				$this->ret['receipt'] = 'cancelled';
				$this->ret['output'] = printheaderb();
				$this->ret['output'] .= plainmsg("transaction cancelled");
			}
			else {
				$IS4C_LOCAL->set("away",1);
				$this->ret['main_frame'] = "/gui-modules/mgrlogin.php";
			}
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
