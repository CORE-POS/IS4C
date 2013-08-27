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

/* this module is intended for re-use. 
 * Pass the name of a class with the
 * static properties: 
 *  - adminLoginMsg (message to display)
 *  - adminLoginLevel (employees.frontendsecurity requirement)
 * and static method:
 *  - adminLoginCallback(boolean $success)
 *
 * The callback should return a URL or True (for pos2.php)
 * when $success is True. When $success is False, the return
 * value is irrelevant. That call is provided in case any
 * cleanup is necessary after a failed login.
 */

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class adminlogin extends NoInputPage {
	var $box_color;
	var $msg;
	var $heading;

	function preprocess(){
		global $CORE_LOCAL;
		$this->box_color="coloredArea";
		$this->msg = _("enter admin password");

		// get calling class (required)
		$class = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
		$pos_home = MiscLib::base_url().'gui-modules/pos2.php';
		if ($class === '' || !class_exists($class)){
			$this->change_page($pos_home);
			return False;
		}
		// make sure calling class implements required
		// method and properties
		try {
			$method = new ReflectionMethod($class, 'adminLoginCallback');
			if (!$method->isStatic() || !$method->isPublic())
				throw new Exception('bad method adminLoginCallback');
			$property = new ReflectionProperty($class, 'adminLoginMsg');
			if (!$property->isStatic() || !$property->isPublic())
				throw new Exception('bad property adminLoginMsg');
			$property = new ReflectionProperty($class, 'adminLoginLevel');
			if (!$property->isStatic() || !$property->isPublic())
				throw new Exception('bad property adminLoginLevel');
		}
		catch (Exception $e){
			$this->change_page($pos_home);
			return False;
		}

		$this->heading = $class::$adminLoginMsg;

		if (isset($_REQUEST['reginput'])){
			$passwd = $_REQUEST['reginput'];
			if (strtoupper($passwd) == "CL"){
				$class::adminLoginCallback(False);
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;	
			}
			else if (!is_numeric($passwd) || $passwd > 9999 || $passwd < 1){
				$this->box_color="errorColoredArea";
				$this->msg = _("re-enter admin password");
			}
			else {
				$query = "select emp_no, FirstName, LastName from employees 
					where EmpActive = 1 and frontendsecurity >= "
					.$class::$adminLoginLevel
					." and (CashierPassword = ".$passwd
					." or AdminPassword = ".$passwd.")";
				$db = Database::pDataConnect();
				$result = $db->query($query);
				$num_rows = $db->num_rows($result);
				if ($num_rows != 0) {
					$row = $db->fetch_row($result);
					TransRecord::add_log_record(array(
						'upc' => $passwd,
						'description' => substr($class::$adminLoginMsg,0,30),
						'charflag' => 'PW',
						'num_flag' => $row['emp_no']
					));

					$result = $class::adminLoginCallback(True);
					if ($result === True)
						$this->change_page(MiscLib::base_url().'gui-modules/pos2.php');
					else
						$this->change_page($result);
					return False;
				}
				else {
					$this->box_color="errorColoredArea";
					$this->msg = _("re-enter admin password");

					TransRecord::add_log_record(array(
						'upc' => $passwd,
						'description' => substr($class::$adminLoginMsg,0,30),
						'charflag' => 'PW'
					));
				}
			}
		}
		return True;
	}

	function head_content(){
		$this->default_parsewrapper_js();
		$this->scanner_scale_polling(True);
	}

	function body_content(){
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="<?php echo $this->box_color; ?> centeredDisplay">
		<span class="larger">
		<?php echo $this->heading ?>
		</span><br />
		<form name="form" id="formlocal" method="post" 
			autocomplete="off" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<input type="password" id="reginput" name="reginput" tabindex="0" onblur="$('#reginput').focus();" />
		<input type="hidden" name="class" value="<?php echo $_REQUEST['class']; ?>" />
		</form>
		<p>
		<?php echo $this->msg ?>
		</p>
		</div>
		</div>
		<?php
		$this->add_onload_command("\$('#reginput').focus();");
	} // END true_body() FUNCTION


}

new adminlogin();

?>
