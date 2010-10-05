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

/* this module is intended for re-use. Just set 
 * $_SESSION["adminRequest"] to the module you want loaded
 * upon successful admin authentication. To be on the safe side,
 * that module should then unset (or clear to "") the session
 * variable
 */

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("NoInputPage")) include_once($IS4C_PATH."gui-class-lib/NoInputPage.php");
if (!function_exists("pDataConnect")) include_once($IS4C_PATH."lib/connect.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

class adminlogin extends NoInputPage {
	var $box_color;
	var $msg;

	function preprocess(){
		global $IS4C_LOCAL,$IS4C_PATH;
		$this->box_color="#004080";
		$this->msg = "enter admin password";

		if (isset($_REQUEST['reginput'])){
			$passwd = $_REQUEST['reginput'];
			if (strtoupper($passwd) == "CL"){
				header("Location: {$IS4C_PATH}gui-modules/pos2.php");
				return False;	
			}
			else if (!is_numeric($passwd) || $passwd > 9999 || $passwd < 1){
				$this->box_color="#800000";
				$this->msg = "re-enter admin password";
			}
			else {
				$query = "select emp_no, FirstName, LastName from employees 
					where empactive = 1 and frontendsecurity >= "
					.$IS4C_LOCAL->get("adminRequestLevel")
					." and (cashierpassword = ".$passwd
					." or adminpassword = ".$passwd.")";
				$db = pDataConnect();
				$result = $db->query($query);
				$num_rows = $db->num_rows($result);
				if ($num_rows != 0) {
					header("Location: ".$IS4C_LOCAL->get("adminRequest"));
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

	function body_content(){
		global $IS4C_LOCAL;
		$heading = $IS4C_LOCAL->get("adminLoginMsg");
		$style = "style=\"background:{$this->box_color};\"";
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay" <?php echo $style; ?>>
		<span class="larger">
		<?php echo $heading ?>
		</span><br />
		<form name="form" method="post" autocomplete="off" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="password" id="reginput" name="reginput" tabindex="0" onblur="$('#reginput').focus();" />
		</form>
		<p />
		<?php echo $this->msg ?>
		<p />
		</div>
		</div>
		<?php
		$this->add_onload_command("\$('#reginput').focus();");
		$IS4C_LOCAL->set("scan","noScan");
	} // END true_body() FUNCTION


}

new adminlogin();

?>
