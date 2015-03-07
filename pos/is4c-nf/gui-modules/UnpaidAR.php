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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class UnpaidAR extends BasicPage {

	function preprocess()
    {
    		$AR_department = '990';
    		if (CoreLocal::get("store") == 'WEFC_Toronto') {
    			$AR_department = '1005';
    		}
		if (isset($_REQUEST['reginput'])){
			$dec = $_REQUEST['reginput'];
			$amt = CoreLocal::get("old_ar_balance");

			CoreLocal::set("msgrepeat",0);
			CoreLocal::set("strRemembered","");

			if (strtoupper($dec) == "CL"){
				if (CoreLocal::get('memType') == 0){
					PrehLib::setMember(CoreLocal::get("defaultNonMem"), 1);
				}
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
			elseif ($dec == "" || strtoupper($dec) == "BQ"){
				if (strtoupper($dec)=="BQ")
					$amt = CoreLocal::get("balance");
				CoreLocal::set("strRemembered", ($amt*100)."DP{$AR_department}0");
				CoreLocal::set("msgrepeat",1);
				$memtype = CoreLocal::get("memType");
				$type = CoreLocal::get("Type");
				if ($memtype == 1 || $memtype == 3 || $type == "INACT"){
					CoreLocal::set("isMember",1);
					PrehLib::ttl();
				}
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
		}
		return True;
	}

    function head_content()
    {
        $this->noscan_parsewrapper_js();
    }
	
	function body_content()
    {
		$amt = CoreLocal::get("old_ar_balance");
		$this->input_header();
		?>
		<div class="baseHeight">

		<?php
		if ($amt == CoreLocal::get("balance")){
			echo DisplayLib::boxMsg(sprintf("Old A/R Balance: $%.2f<br />
				[Enter] to pay balance now<br />
				[Clear] to leave balance",$amt));
		}
		else {
			echo DisplayLib::boxMsg(sprintf("Old A/R Balance: $%.2f<br />
				Total A/R Balance: $%.2f<br />
				[Enter] to pay old balance<br />
				[Balance] to pay the entire balance<br />
				[Clear] to leave the balance",
				$amt,CoreLocal::get("balance")));
		}
		echo "</div>";
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
		CoreLocal::set("msgrepeat",2);
	} // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new UnpaidAR();

?>
