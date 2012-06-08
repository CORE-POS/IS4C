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

/* this module is intended for re-use. Just set 
 * $_SESSION["adminRequest"] to the module you want loaded
 * upon successful admin authentication. To be on the safe side,
 * that module should then unset (or clear to "") the session
 * variable
 */

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class adminlogin extends NoInputPage {
	var $box_color;
	var $msg;

	function preprocess(){
		global $CORE_LOCAL;
		$this->box_color="#004080";
		$this->msg = "enter admin password";

		if (isset($_REQUEST['reginput'])){
			$passwd = $_REQUEST['reginput'];
			if (strtoupper($passwd) == "CL"){
				$CORE_LOCAL->set("refundComment","");
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;	
			}
			else if (!is_numeric($passwd) || $passwd > 9999 || $passwd < 1){
				$this->box_color="#800000";
				$this->msg = "re-enter admin password";
			}
			else {
				$query = "select emp_no, FirstName, LastName from employees 
					where EmpActive = 1 and frontendsecurity >= "
					.$CORE_LOCAL->get("adminRequestLevel")
					." and (CashierPassword = ".$passwd
					." or AdminPassword = ".$passwd.")";
				$db = Database::pDataConnect();
				$result = $db->query($query);
				$num_rows = $db->num_rows($result);
				if ($num_rows != 0) {
					$this->change_page($CORE_LOCAL->get("adminRequest"));
					return False;
				}
				else {
					$this->box_color="#800000";
					$this->msg = "re-enter admin password";
				}
			}
		}
		return True;
	}

	function head_content(){
		$this->default_parsewrapper_js();
	}

	function body_content(){
		global $CORE_LOCAL;
		$heading = $CORE_LOCAL->get("adminLoginMsg");
		$style = "style=\"background:{$this->box_color};\"";
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay" <?php echo $style; ?>>
		<span class="larger">
		<?php echo $heading ?>
		</span><br />
		<form name="form" id="formlocal" method="post" 
			autocomplete="off" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="password" id="reginput" name="reginput" tabindex="0" onblur="$('#reginput').focus();" />
		</form>
		<p />
		<?php echo $this->msg ?>
		<p />
		</div>
		</div>
		<?php
		$this->add_onload_command("\$('#reginput').focus();");
		$CORE_LOCAL->set("scan","noScan");
	} // END true_body() FUNCTION


}

new adminlogin();

?>
