<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class UnpaidAR extends BasicPage {

	function preprocess(){
		global $CORE_LOCAL,$CORE_PATH;
		if (isset($_REQUEST['reginput'])){
			$dec = $_REQUEST['reginput'];
			$amt = $CORE_LOCAL->get("old_ar_balance");

			$CORE_LOCAL->set("msgrepeat",1);
			$CORE_LOCAL->set("strRemembered","");

			if (strtoupper($dec) == "CL"){
				if ($CORE_LOCAL->get('inactMem') == 1){
					PrehLib::setMember($CORE_LOCAL->get("defaultNonMem"),1);
				}
				$this->change_page($CORE_PATH."gui-modules/pos2.php");
				return False;
			}
			elseif ($dec == "" || strtoupper($dec) == "BQ"){
				$CORE_LOCAL->set('warned',1);
				$CORE_LOCAL->set('warnBoxType','warnAR');
				if (strtoupper($dec)=="BQ")
					$amt = $CORE_LOCAL->get("balance");
				PrehLib::deptkey($amt*100,9900,True);
				$memtype = $CORE_LOCAL->get("memType");
				$type = $CORE_LOCAL->get("Type");
				if ($memtype == 1 || $memtype == 3 || $type == "INACT"){
					$CORE_LOCAL->set("isMember",1);
					PrehLib::ttl();
				}
				$this->change_page($CORE_PATH."gui-modules/pos2.php");
				return False;
			}
		}
		return True;
	}
	
	function body_content(){
		global $CORE_LOCAL;
		$amt = $CORE_LOCAL->get("old_ar_balance");
		$this->input_header();
		?>
		<div class="baseHeight">

		<?php
		if ($amt == $CORE_LOCAL->get("balance")){
			DisplayLib::boxMsg(sprintf("Old A/R Balance: $%.2f<br />
				[Enter] to pay balance now<br />
				[Clear] to leave balance",$amt));
		}
		else {
			DisplayLib::boxMsg(sprintf("Old A/R Balance: $%.2f<br />
				Total A/R Balance: $%.2f<br />
				[Enter] to pay old balance<br />
				[Balance] to pay the entire balance<br />
				[Clear] to leave the balance",
				$amt,$CORE_LOCAL->get("balance")));
		}
		echo "</div>";
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
		$CORE_LOCAL->set("msgrepeat",2);
		$CORE_LOCAL->set("beep","noBeep");
	} // END body_content() FUNCTION
}

new UnpaidAR();

?>
