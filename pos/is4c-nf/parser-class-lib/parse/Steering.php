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
		global $CORE_LOCAL;
		$my_url = MiscLib::base_url();
		
		$this->dest_input_page = "";
		$this->dest_main_page = "";
		$this->dest_scale = False;
		$this->ret = $this->default_json();

		switch($str){
			
		case 'CAB':
			if ($CORE_LOCAL->get("LastID") != "0")
				$this->ret['output'] = DisplayLib::boxMsg("transaction in progress");
			else {
				$this->ret['main_frame'] = $my_url."gui-modules/cablist.php";
			}
			return True;
		case "PV":
			$CORE_LOCAL->set("pvsearch","");
			$CORE_LOCAL->set("away",1);
			$this->ret['main_frame'] = $my_url."gui-modules/productlist.php";
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
				$this->ret['output'] = DisplayLib::boxMsg("transaction in progress");
			else {
				$CORE_LOCAL->set("adminRequest",$my_url."gui-modules/undo.php");
				$CORE_LOCAL->set("adminRequestLevel","30");
				$CORE_LOCAL->set("adminLoginMsg",_("Login to void transactions"));
				$CORE_LOCAL->set("away",1);
				$this->ret['main_frame'] = $my_url."gui-modules/adminlogin.php";
			}
			return True;
		case "DDD":
			$CORE_LOCAL->set("adminRequest",$my_url."ajax-callbacks/ddd.php");
			$CORE_LOCAL->set("adminLoginMsg","DDD these items?");
			$CORE_LOCAL->set("adminRequestLevel","10");
			$CORE_LOCAL->set("away",1);
			$this->ret['main_frame'] = $my_url."gui-modules/adminlogin.php";
			return True;
		case 'MG':
			if ($CORE_LOCAL->get("SecuritySR") > 20){
				$CORE_LOCAL->set("adminRequest",$my_url."gui-modules/adminlist.php");
				$CORE_LOCAL->set("adminRequestLevel",$CORE_LOCAL->get("SecuritySR"));
				$CORE_LOCAL->set("adminLoginMsg",_("Login to suspend/resume transactions"));
				$CORE_LOCAL->set("away",1);
				$this->ret['main_frame'] = $my_url."gui-modules/adminlogin.php";
			}
			else
				$this->ret['main_frame'] = $my_url."gui-modules/adminlist.php";

			$CORE_LOCAL->set("away",1);
			return True;
		case 'RP':
			if ($CORE_LOCAL->get("LastID") != "0"){
				//$this->ret['output'] = DisplayLib::boxMsg("transaction in progress");
				$tr = $CORE_LOCAL->get("receiptToggle");
				if ($tr == 1) $CORE_LOCAL->set("receiptToggle",0);
				else $CORE_LOCAL->set("receiptToggle",1);
				$this->ret['main_frame'] = $my_url."gui-modules/pos2.php";
			}
			else {
				$query = "select register_no, emp_no, trans_no, "
					."sum((case when trans_type = 'T' then -1 * total else 0 end)) as total "
					."from localtranstoday where register_no = ".$CORE_LOCAL->get("laneno")
					." and emp_no = ".$CORE_LOCAL->get("CashierNo")
					." group by register_no, emp_no, trans_no order by 1000 - trans_no";
				$db = Database::tDataConnect();
				$result = $db->query($query);
				$num_rows = $db->num_rows($result);
				$db->close();

				if ($num_rows == 0) 
					$this->ret['output'] = DisplayLib::boxMsg("no receipt found");
				else {
					$this->ret['main_frame'] = $my_url."gui-modules/rplist.php";
				}
			}				
			return True;
		case 'ID':
			$CORE_LOCAL->set("away",1);
			$CORE_LOCAL->set("search_or_list",1);
			$this->ret['main_frame'] = $my_url."gui-modules/memlist.php";
			return True;
		case 'SO':
			if ($CORE_LOCAL->get("LastID") != 0) 
				$this->ret['output'] = DisplayLib::boxMsg(_("Transaction in Progress"));
			else {
				Database::setglobalvalue("LoggedIn", 0);
				$CORE_LOCAL->set("LoggedIn",0);
				ReceiptLib::drawerKick();
				$CORE_LOCAL->set("training",0);
				$CORE_LOCAL->set("gui-scale","no");
				$CORE_LOCAL->set("away",1);
				$this->ret['main_frame'] = $my_url."gui-modules/login2.php";
			}
			return True;
		case 'NS':
			if ($CORE_LOCAL->get("LastID") != 0) 
				$this->ret['output'] = DisplayLib::boxMsg(_("Transaction in Progress"));
			else {
				$CORE_LOCAL->set("away",1);
				$this->ret['main_frame'] = $my_url."gui-modules/nslogin.php";
			}
			return True;
		case 'GD':
			$CORE_LOCAL->set("msgrepeat",0);
			$this->ret['main_frame'] = $my_url."gui-modules/giftcardlist.php";
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
				$this->ret['output'] = DisplayLib::printheaderb();
				$this->ret['output'] .= DisplayLib::plainmsg(_("transaction cancelled"));
			}
			else {
				$CORE_LOCAL->set("away",1);
				$this->ret['main_frame'] = $my_url."gui-modules/mgrlogin.php";
			}
			return True;
		case "CC":
			if ($CORE_LOCAL->get("ttlflag") != 1){
				$this->ret['output'] = DisplayLib::boxMsg(_("transaction must be totaled")."<br />".
					_("before tender can be accepted"));
			}
			else
				$this->ret['main_frame'] = $my_url."cc-modules/gui/ProcessPage.php";
			return True;
		case "PO":
			$CORE_LOCAL->set("adminRequest",$my_url."gui-modules/priceOverride.php");
			$CORE_LOCAL->set("adminRequestLevel","30");
			$CORE_LOCAL->set("adminLoginMsg",_("Login to alter price"));
			$CORE_LOCAL->set("away",1);
			$this->ret['main_frame'] = $my_url."gui-modules/adminlogin.php";
			return True;
		case "HC":
			$module = new HostedCheckout();
			$test = $module->entered(False,array());
			var_dump($test);
			if (isset($test['main_frame']))
				$this->ret['main_frame'] = $test['main_frame'];
			elseif (isset($test['output']))
				$this->ret['output'] = $test['output'];
			else
				$this->ret['output'] = DisplayLib::boxMsg(_("processor error"));
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
