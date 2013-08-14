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

class EquityEndorseDept extends SpecialDept {

	function handle($deptID,$amount,$json){
		global $CORE_LOCAL;

		if ($CORE_LOCAL->get("memberID") == "0" || $CORE_LOCAL->get("memberID") == $CORE_LOCAL->get("defaultNonMem")){
			$CORE_LOCAL->set('strEntered','');
			$CORE_LOCAL->set('boxMsg','Equity requires member.<br />Apply member number first');
			$json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php';
			return $json;
		}

		if ($CORE_LOCAL->get('msgrepeat') == 0){
			$ref = trim($CORE_LOCAL->get("CashierNo"))."-"
				.trim($CORE_LOCAL->get("laneno"))."-"
				.trim($CORE_LOCAL->get("transno"));
			if ($CORE_LOCAL->get("LastEquityReference") != $ref){
				$CORE_LOCAL->set("equityAmt",$amount);
				$CORE_LOCAL->set("boxMsg","<b>Equity Sale</b><br>Insert paperwork and press<br>
						<font size=-1>[enter] to continue, [clear] to cancel</font>");
				$json['main_frame'] = MiscLib::base_url().'gui-modules/boxMsg2.php?endorse=stock&endorseAmt='.$amount;
			}
		}

		return $json;
	}

}

?>
