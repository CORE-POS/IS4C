<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class PriceCheckPage extends NoInputPage {

	var $upc;
	var $found;
	var $pricing;

	function preprocess(){
		global $CORE_LOCAL;

		$this->upc = "";
		$this->found = False;
		$this->pricing = array('sale'=>False,'price'=>'','memPrice'=>'',
			'description','department');

		if (isset($_REQUEST['reginput']) && strtoupper($_REQUEST['reginput'])=="CL"){
			// cancel
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}
		else if (isset($_REQUEST['reginput']) || isset($_REQUEST['upc'])){
			// use reginput as UPC unless it's empty
			$this->upc = isset($_REQUEST['reginput']) ? $_REQUEST['reginput'] : '';
			if ($this->upc == '' && isset($_REQUEST['upc']))
				$this->upc = $_REQUEST['upc'];
			$this->upc = str_pad($this->upc,13,'0',STR_PAD_LEFT);

			$db = Database::pDataConnect();
			$query = "select inUse,upc,description,normal_price,scale,deposit,
				qttyEnforced,department,local,cost,tax,foodstamp,discount,
				discounttype,specialpricemethod,special_price,groupprice,
				pricemethod,quantity,specialgroupprice,specialquantity,
				mixmatchcode,idEnforced,tareweight
				from products where upc = '".$db->escape($this->upc)."'";
			$result = $db->query($query);
			$num_rows = $db->num_rows($result);

			// lookup item info
			if ($num_rows > 0){
				$this->found = True;
				$row = $db->fetch_row($result);

				$discounttype = MiscLib::nullwrap($row["discounttype"]);
				$DTClasses = $CORE_LOCAL->get("DiscountTypeClasses");
				$DiscountObject = new $DTClasses[$discounttype];

				if ($DiscountObject->isSale())
					$this->pricing['sale'] = True;
				$info = $DiscountObject->priceInfo($row,1);
				$this->pricing['price'] = sprintf('$%.2f%s',
					$info['unitPrice'],($row['scale']>0?' /lb':''));
				if ($info['memDiscount'] > 0){
					$this->pricing['memPrice'] = sprintf('$%.2f%s',
						($info['unitPrice']-$info['memDiscount']),
						($row['scale']>0?' /lb':''));
				}
				$this->pricing['description'] = $row['description'];
				$this->pricing['department'] = $row['department'];
			}

			// user hit enter and there is a valid UPC present
			if (isset($_REQUEST['reginput']) && $_REQUEST['reginput']=='' && $this->found){
				$CORE_LOCAL->set("msgrepeat",1);
				$CORE_LOCAL->set("strRemembered",$this->upc);
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
		}

		return True;
	}

	function head_content(){
		$this->default_parsewrapper_js();
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->add_onload_command("\$('#reginput').focus();\n");
		$style = "style=\"background:#004080;\"";
		$info = _("price check");
		$inst = array(
			_("[scan] item"),
			_("[clear] to cancel"),
		);
		if (!empty($this->upc)){
			if (!$this->found){
				$info = _("not a valid item");
				$inst = array(
					_("[scan] another item"),
					_("[clear] to cancel"),
				);
				$this->upc = "";
			}
			else {
				$info = $this->pricing['description'].' :: '.$this->pricing['department'].'<br />';
				$info .= _("Price").": ".$this->pricing['price'];
				if (!empty($this->pricing['memPrice'])){
					$info .= "<br />("._("Member Price").": ".$this->pricing['memPrice'].")";
				}
				
				$inst = array(
					_("[scan] another item"),
					_("[enter] to ring this item"),
					_("[clear] to cancel"),
				);
			}
		}
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay" <?php echo $style; ?>>
		<span class="larger">
		<?php echo $info ?>
		</span><br />
		<form name="form" id="formlocal" method="post" 
			autocomplete="off" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="text" name="reginput" tabindex="0" 
			onblur="$('#reginput').focus();" id="reginput" />
		<input type="hidden" name="upc" value="<?php echo $this->upc; ?>" />
		</form>
		<p>
		<span id="localmsg"><?php foreach($inst as $i) echo $i."<br />" ?></span>
		</p>
		</div>
		</div>
		<?php
		$CORE_LOCAL->set("beep","noScan");
	} // END true_body() FUNCTION

	function mgrauthenticate($password){
		global $CORE_LOCAL;
		$CORE_LOCAL->set("away",1);

		$ret = array(
			'cancelOrder'=>false,
			'color'=>'#800000',
			'msg'=>_('password invalid'),
			'heading'=>_('re-enter manager password'),
			'giveUp'=>false
		);

		$password = strtoupper($password);
		$password = str_replace("'","",$password);

		if (!isset($password) || strlen($password) < 1 || $password == "CL") {
			$ret['giveUp'] = true;
			return $ret;
		}
		elseif (!is_numeric($password)) {
			return $ret;
		}
		elseif ($password > 9999 || $password < 1) {
			return $ret;
		}

		$db = Database::pDataConnect();
		$priv = sprintf("%d",$CORE_LOCAL->get("SecurityCancel"));
		$query = "select emp_no, FirstName, LastName from employees where EmpActive = 1 and frontendsecurity >= $priv "
		."and (CashierPassword = ".$password." or AdminPassword = ".$password.")";
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);

		if ($num_rows != 0) {
			$this->cancelorder();
			$ret['cancelOrder'] = true;
		}

		return $ret;
	}

}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__))
	new PriceCheckPage();
?>
