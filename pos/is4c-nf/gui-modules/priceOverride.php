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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists("NoInputPage")) include_once($CORE_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("tDataConnect")) include_once($CORE_PATH."lib/connect.php");
if (!function_exists("tDataConnect")) include_once($CORE_PATH."lib/additem.php");
if (!isset($CORE_LOCAL)) include($CORE_PATH."lib/LocalStorage/conf.php");

class PriceOverride extends NoInputPage {

	var $description;
	var $price;

	function preprocess(){
		global $CORE_LOCAL, $CORE_PATH;
		$line_id = $CORE_LOCAL->get("currentid");
		$db = tDataConnect();
		
		$q = "SELECT description,total FROM localtemptrans
			WHERE trans_type IN ('I','D') AND trans_status = ''
			AND trans_id=".((int)$line_id);
		$r = $db->query($q);
		if ($db->num_rows($r)==0){
			// current record cannot be repriced
			header("Location: {$CORE_PATH}gui-modules/pos2.php");
			return False;
		}
		$w = $db->fetch_row($r);
		$this->description = $w['description'];
		$this->price = sprintf('$%.2f',$w['total']);

		if (isset($_REQUEST['reginput'])){
			$input = strtoupper($_REQUEST['reginput']);

			if ($input == "CL"){
				// override canceled; go home
				header("Location: {$CORE_PATH}gui-modules/pos2.php");
				return False;
			}
			else if (is_numeric($input)){
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
				
				$q = sprintf("UPDATE localtemptrans SET total=%.2f, charflag='PO'
					WHERE trans_id=%d",$ttl,$line_id);
				$r = $db->query($q);	

				header("Location: {$CORE_PATH}gui-modules/pos2.php");
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
		<p />
		<span class="smaller">[clear] to cancel</span>
		<p />
		</div>
		</div>	
		<?php
		$this->add_onload_command("\$('#reginput').focus();\n");
	} // END body_content() FUNCTION
}

new PriceOverride();
?>
