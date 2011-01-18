<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

if (!class_exists("NoInputPage")) include_once($IS4C_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("addItem")) include_once($IS4C_PATH."lib/additem.php");
if (!function_exists("setMember")) include_once($IS4C_PATH."lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class undo extends NoInputPage {
	var $box_color;
	var $msg;

	function body_content(){
		global $IS4C_LOCAL;
		$style = "style=\"background:{$this->box_color};\"";
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay" <?php echo $style; ?>>
		<span class="larger">
		<?php echo $this->msg ?>
		</span><br />
		<form name="form" method='post' autocomplete="off" action="<?php echo $_SERVER["PHP_SELF"; ?>">
		<input type="text" name="reginput" id="reginput" tabindex="0" onblur="($'#reginput').focus();" >
		</form>
		<p />
		Enter transaction number<br />[clear to cancel]
		<p />
		</div>
		</div>
		<?php
		$this->add_onload_command("\$('#reginput').focus();");
		$IS4C_LOCAL->set("beep","noScan");
	}

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;
		$this->box_color = "#004080";
		$this->msg = "Undo transaction";

		if (isset($_REQUEST['reginput'])){
			$trans_num = strtoupper($_REQUEST['reginput']);

			// clear/cancel undo attempt
			if ($trans_num == "" || $trans_num == "CL"){
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;
			}

			// error: malformed transaction number
			if (!strpos($trans_num,"-")){
				$this->box_color="#800000";
				$this->msg = "Transaction not found";
				return True;
			}

			$temp = explode("-",$trans_num);
			// error: malformed transaction number (2)
			if (count($temp) != 3){
				$this->box_color="#800000";
				$this->msg = "Transaction not found";
				return True;
			}

			$emp_no = $temp[0];
			$register_no = $temp[1];
			$old_trans_no = $temp[2];
			// error: malformed transaction number (3)
			if (!is_numeric($emp_no) || !is_numeric($register_no)
			    || !is_numeric($old_trans_no)){
				$this->box_color="#800000";
				$this->msg = "Transaction not found";
				return True;
			}

			$db = 0;
			$query = "";
			if ($register_no == $IS4C_LOCAL->get("laneno")){
				// look up transation locally
				$db = tDataConnect();
				$query = "select upc, description, trans_type, trans_subtype,
					trans_status, department, quantity, scale, unitPrice,
					total, regPrice, tax, foodstamp, discount, memDiscount,
					discountable, discounttype, voided, PercentDiscount,
					ItemQtty, volDiscType, volume, VolSpecial, mixMatch,
					matched, card_no, trans_id
					from localtranstoday where register_no = $register_no
					and emp_no = $emp_no and trans_no = $old_trans_no
					and ".$db->datediff($db->now(),'datetime')." = 0
					and trans_status <> 'X'
					order by trans_id";
			}
			else if ($IS4C_LOCAL->get("standalone") == 1){
				// error: remote lookups won't work in standalone
				$this->box_color="#800000";
				$this->msg = "Transaction not found";
				return True;
			}
			else {
				// look up transaction remotely
				$db = mDataConnect();
				$query = "select upc, description, trans_type, trans_subtype,
					trans_status, department, quantity, Scale, unitPrice,
					total, regPrice, tax, foodstamp, discount, memDiscount,
					discountable, discounttype, voided, PercentDiscount,
					ItemQtty, volDiscType, volume, VolSpecial, mixMatch,
					matched, card_no, trans_id
					from dTransToday where register_no = $register_no
					and emp_no = $emp_no and trans_no = $old_trans_no
					and ".$db->datediff($db->now(),'datetime')." = 0
					and trans_status <> 'X'
					order by trans_id";
			}

			$result = $db->query($query);
			// transaction not found
			if ($db->num_rows($result) < 1){
				$this->box_color="#800000";
				$this->msg = "Transaction not found";
				return True;
			}

			/* change the cashier to the original transaction's cashier */
			$prevCashier = $IS4C_LOCAL->get("CashierNo");
			$IS4C_LOCAL->set("CashierNo",$emp_no);
			$IS4C_LOCAL->set("transno",gettransno($emp_no));	

			/* rebuild the transaction, line by line, in reverse */
			$card_no = 0;
			addcomment("VOIDING TRANSACTION $trans_num");
			while ($row = $db->fetch_array($result)){
				$card_no = $row["card_no"];

				if ($row["upc"] == "TAX"){
					//addTax();
				}
				elseif ($row["trans_type"] ==  "T"){
					if ($row["description"] == "Change")
						addchange(-1*$row["total"]);
					elseif ($row["description"] == "FS Change")
						addfsones(-1*$row["total"]);
					else
						addtender($row["description"],$row["trans_subtype"],-1*$row["total"]);
				}
				elseif (strstr($row["description"],"** YOU SAVED")){
					$temp = explode("$",$row["description"]);
					adddiscount(substr($temp[1],0,-3),$row["department"]);
				}
				elseif ($row["upc"] == "FS Tax Exempt")
					addfsTaxExempt();
				elseif (strstr($row["description"],"% Discount Applied")){
					$temp = explode("%",$row["description"]);	
					discountnotify(substr($temp[0],3));
				}
				elseif ($row["description"] == "** Order is Tax Exempt **")
					addTaxExempt();
				elseif ($row["description"] == "** Tax Excemption Reversed **")
					reverseTaxExempt();
				elseif ($row["description"] == " * Manufacturers Coupon")
					addCoupon($row["upc"],$row["department"],-1*$row["total"]);
				elseif (strstr($row["description"],"** Tare Weight")){
					$temp = explode(" ",$row["description"]);
					addTare($temp[3]*100);
				}
				elseif ($row["upc"] == "MAD Coupon")
					addMadCoup();
				elseif ($row["upc"] == "DISCOUNT"){
					//addTransDiscount();
				}
				elseif ($row["trans_status"] != "M" && $row["upc"] != "0" &&
					(is_numeric($row["upc"]) || strstr($row["upc"],"DP"))) {
					$row["trans_status"] = "V";
					$row["total"] *= -1;
					$row["discount"] *= -1;
					$row["memDiscount"] *= -1;
					$row["quantity"] *= -1;
					$row["ItemQtty"] *= -1;
					addItem($row["upc"],$row["description"],$row["trans_type"],$row["trans_subtype"],
						$row["trans_status"],$row["department"],$row["quantity"],
						$row["unitPrice"],$row["total"],$row["regPrice"],
						$row["Scale"],$row["tax"],$row["foodstamp"],$row["discount"],
						$row["memDiscount"],$row["discountable"],$row["discounttype"],
						$row["ItemQtty"],$row["volDiscType"],$row["volume"],$row["VolSpecial"],
						$row["mixMatch"],$row["matched"],$row["voided"]);
				}
			}
			setMember($card_no,1);
			$IS4C_LOCAL->set("autoReprint",0);

			/* restore the logged in cashier */
			$IS4C_LOCAL->set("CashierNo",$prevCashier);
			$IS4C_LOCAL->set("transno",gettransno($prevCashier));
		}
		return True;
	}
}

new undo();
