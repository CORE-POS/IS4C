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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class PriceOverride extends NoInputPage {

	var $description;
	var $price;

	function preprocess(){
		global $CORE_LOCAL;
		$line_id = $CORE_LOCAL->get("currentid");
		$db = Database::tDataConnect();
		
		$q = "SELECT description,total,department FROM localtemptrans
			WHERE trans_type IN ('I','D') AND trans_status IN ('', ' ', '0')
			AND trans_id=".((int)$line_id);
		$r = $db->query($q);
		if ($db->num_rows($r)==0){
			// current record cannot be repriced
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return False;
		}
		$w = $db->fetch_row($r);
		$this->description = $w['description'];
		$this->price = sprintf('$%.2f',$w['total']);

		if (isset($_REQUEST['reginput'])){
			$input = strtoupper($_REQUEST['reginput']);

			if ($input == "CL"){
				if ($this->price == "$0.00"){
					$q = sprintf("UPDATE localtemptrans SET trans_type='L',
                                trans_subtype='OG',charflag='PO',total=0
                                WHERE trans_id=".(int)$line_id);
					$r = $db->query($q);
				}
				// override canceled; go home
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			} else if (is_numeric($input) && $input != 0){
				$cents = 0;
				$dollars = 0;
				if (strlen($input)==1 || strlen($input)==2)
					$cents = $input;
				else {
					$cents = substr($input,-2);
					$dollars = substr($input,0,strlen($input)-2);
				}
				$ttl = ((int)$dollars) + ((int)$cents / 100.0);
				$ttl = number_format($ttl,2);
				if ($w['department'] == $CORE_LOCAL->get("BottleReturnDept"))
					$ttl = $ttl * -1;
					
				$q = sprintf("UPDATE localtemptrans SET unitPrice=%.2f, regPrice=%.2f,
                    total = quantity*%.2f, charflag='PO'
					WHERE trans_id=%d",$ttl,$ttl,$ttl,$line_id);
				$r = $db->query($q);	

				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
		}

		return True;
	}
	
	function body_content() {
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
		<span class="larger">price override</span>
		<form name="overrideform" method="post" 
			id="overrideform" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="text" id="reginput" name='reginput' tabindex="0" onblur="$('#reginput').focus()" />
		</form>
		<span><?php echo $this->description; ?> - <?php echo $this->price; ?></span>
		<p>
		<span class="smaller">[clear] to cancel</span>
		</p>
		</div>
		</div>	
		<?php
		$this->add_onload_command("\$('#reginput').focus();\n");
	} // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new PriceOverride();
?>
