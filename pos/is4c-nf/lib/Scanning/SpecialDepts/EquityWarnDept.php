<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

class EquityWarnDept extends SpecialDept {

	function handle($deptID,$amount,$json){
		global $CORE_LOCAL;

		if ($CORE_LOCAL->get("warned") == 1 and $CORE_LOCAL->get("warnBoxType") == "warnEquity"){
			$CORE_LOCAL->set("warned",0);
			$CORE_LOCAL->set("warnBoxType","");
		}
		else {
			$CORE_LOCAL->set("warned",1);
			$CORE_LOCAL->set("warnBoxType","warnEquity");
			$CORE_LOCAL->set("boxMsg","<b>Equity Sale</b><br>please confirm<br>
				<font size=-1>[enter] to continue, [clear] to cancel</font>");
			$json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';
		}

		return $json;
	}
}

?>
