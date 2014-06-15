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

class undo extends NoInputPage {
	var $msg;

	function body_content(){
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="<?php echo $this->box_color; ?> centeredDisplay">
		<span class="larger">
		<?php echo $this->msg ?>
		</span><br />
		<form name="form" method='post' autocomplete="off" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
		<input type="text" name="reginput" id="reginput" tabindex="0" onblur="($'#reginput').focus();" >
		</form>
		<p>
		Enter transaction number<br />[clear to cancel]
		</p>
		</div>
		</div>
		<?php
		$this->add_onload_command("\$('#reginput').focus();");
	}

	function preprocess(){
		global $CORE_LOCAL;
		$this->box_color = "coloredArea";
		$this->msg = "Undo transaction";

		if (isset($_REQUEST['reginput'])){
			$trans_num = strtoupper($_REQUEST['reginput']);

			// clear/cancel undo attempt
			if ($trans_num == "" || $trans_num == "CL"){
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}

			// error: malformed transaction number
			if (!strpos($trans_num,"-")){
				$this->box_color="errorColoredArea";
				$this->msg = "Transaction not found";
				return True;
			}

			$temp = explode("-",$trans_num);
			// error: malformed transaction number (2)
			if (count($temp) != 3){
				$this->box_color="errorColoredArea";
				$this->msg = "Transaction not found";
				return True;
			}

			$emp_no = $temp[0];
			$register_no = $temp[1];
			$old_trans_no = $temp[2];
			// error: malformed transaction number (3)
			if (!is_numeric($emp_no) || !is_numeric($register_no)
			    || !is_numeric($old_trans_no)){
				$this->box_color="errorColoredArea";
				$this->msg = "Transaction not found";
				return True;
			}

			$db = 0;
			$query = "";
			if ($register_no == $CORE_LOCAL->get("laneno")){
				// look up transation locally
				$db = Database::tDataConnect();
				$query = "select upc, description, trans_type, trans_subtype,
					trans_status, department, quantity, scale, unitPrice,
					total, regPrice, tax, foodstamp, discount, memDiscount,
					discountable, discounttype, voided, PercentDiscount,
					ItemQtty, volDiscType, volume, VolSpecial, mixMatch,
					matched, card_no, trans_id
					from localtranstoday where register_no = $register_no
					and emp_no = $emp_no and trans_no = $old_trans_no
					and datetime >= " . $db->curdate() . "
					and trans_status <> 'X'
					order by trans_id";
			}
			else if ($CORE_LOCAL->get("standalone") == 1){
				// error: remote lookups won't work in standalone
				$this->box_color="errorColoredArea";
				$this->msg = "Transaction not found";
				return True;
			}
			else {
				// look up transaction remotely
				$db = Database::mDataConnect();
				$query = "select upc, description, trans_type, trans_subtype,
					trans_status, department, quantity, scale, unitPrice,
					total, regPrice, tax, foodstamp, discount, memDiscount,
					discountable, discounttype, voided, PercentDiscount,
					ItemQtty, volDiscType, volume, VolSpecial, mixMatch,
					matched, card_no, trans_id
					from dtransactions where register_no = $register_no
					and emp_no = $emp_no and trans_no = $old_trans_no
					and datetime >= " . $db->curdate() . "
					and trans_status <> 'X'
					order by trans_id";
			}

			$result = $db->query($query);
			// transaction not found
			if ($db->num_rows($result) < 1){
				$this->box_color="errorColoredArea";
				$this->msg = "Transaction not found";
				return True;
			}

			/* change the cashier to the original transaction's cashier */
			$prevCashier = $CORE_LOCAL->get("CashierNo");
			$CORE_LOCAL->set("CashierNo",$emp_no);
			$CORE_LOCAL->set("transno",Database::gettransno($emp_no));	

			/* rebuild the transaction, line by line, in reverse */
			$card_no = 0;
			TransRecord::addcomment("VOIDING TRANSACTION $trans_num");
			while ($row = $db->fetch_array($result)){
				$card_no = $row["card_no"];

				if ($row["upc"] == "TAX"){
					//TransRecord::addtax();
				}
				elseif ($row["trans_type"] ==  "T"){
					if ($row["description"] == "Change")
						TransRecord::addchange(-1*$row["total"]);
					elseif ($row["description"] == "FS Change")
						TransRecord::addfsones(-1*$row["total"]);
					else
						TransRecord::addtender($row["description"],$row["trans_subtype"],-1*$row["total"]);
				}
				elseif (strstr($row["description"],"** YOU SAVED")){
					$temp = explode("$",$row["description"]);
					TransRecord::adddiscount(substr($temp[1],0,-3),$row["department"]);
				}
				elseif ($row["upc"] == "FS Tax Exempt")
					TransRecord::addfsTaxExempt();
				elseif (strstr($row["description"],"% Discount Applied")){
					$temp = explode("%",$row["description"]);	
					TransRecord::discountnotify(substr($temp[0],3));
				}
				elseif ($row["description"] == "** Order is Tax Exempt **")
					TransRecord::addTaxExempt();
				elseif ($row["description"] == "** Tax Excemption Reversed **")
					TransRecord::reverseTaxExempt();
				elseif ($row["description"] == " * Manufacturers Coupon")
					TransRecord::addCoupon($row["upc"],$row["department"],-1*$row["total"]);
				elseif (strstr($row["description"],"** Tare Weight")){
					$temp = explode(" ",$row["description"]);
					TransRecord::addTare($temp[3]*100);
				}
				elseif ($row["upc"] == "DISCOUNT"){
					//TransRecord::addTransDiscount();
				}
				elseif ($row["trans_status"] != "M" && $row["upc"] != "0" &&
					(is_numeric($row["upc"]) || strstr($row["upc"],"DP"))) {
					$row["trans_status"] = "V";
					$row["total"] *= -1;
					$row["discount"] *= -1;
					$row["memDiscount"] *= -1;
					$row["quantity"] *= -1;
					$row["ItemQtty"] *= -1;
					TransRecord::addRecord($row);
				}
			}

			$op = Database::pDataConnect();
			$query = "select CardNo,personNum,LastName,FirstName,CashBack,Balance,Discount,
				ChargeOk,WriteChecks,StoreCoupons,Type,memType,staff,
				SSI,Purchases,NumberOfChecks,memCoupons,blueLine,Shown,id from custdata 
				where CardNo = '".$card_no."'";
			$res = $op->query($query);
			$row = $op->fetch_row($res);
			PrehLib::setMember($card_no,1,$row);
			$CORE_LOCAL->set("autoReprint",0);

			/* do NOT restore logged in cashier until this transaction is complete */
			
			$this->change_page($this->page_url."gui-modules/undo_confirm.php");
			return False;
		}
		return True;
	}
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new undo();
