<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

class drawerPage extends NoInputPage {

	var $is_admin;
	var $my_drawer;
	var $available;

	function preprocess(){
		global $CORE_LOCAL;

		$this->my_drawer = ReceiptLib::currentDrawer();
		$this->available = ReceiptLib::availableDrawers();
		$this->is_admin = False;
		$db = Database::pDataConnect();
		$chk = $db->query('SELECT frontendsecurity FROM employees 
				WHERE emp_no='.$CORE_LOCAL->get('CashierNo'));
		if ($db->num_rows($chk) > 0){
			$sec = array_pop($db->fetch_row($chk));
			if ($sec >= 30) $this->is_admin = True;
		}

		if (isset($_REQUEST['selectlist'])){
			if (empty($_REQUEST['selectlist'])){
				if (empty($this->available) && !$this->is_admin && $this->my_drawer == 0){
					// no drawer available and not admin
					// sign out and go back to main login screen
					Database::setglobalvalue("LoggedIn", 0);
					$CORE_LOCAL->set("LoggedIn",0);
					$CORE_LOCAL->set("training",0);
					$CORE_LOCAL->set("gui-scale","no");
					$this->change_page($this->page_url."gui-modules/login2.php");
				}
				else {
					$this->change_page($this->page_url."gui-modules/pos2.php");
				}
				return False;
			}
			if (substr($_REQUEST['selectlist'],0,2) == 'TO' && $this->is_admin){
				// take over a drawer
				$new_drawer = substr($_REQUEST['selectlist'],2);
				if ($this->my_drawer != 0){
					// free up the current drawer if it exists
					ReceiptLib::drawerKick();
					ReceiptLib::freeDrawer($this->my_drawer);
				}
				// switch to the requested drawer
				ReceiptLib::assignDrawer($CORE_LOCAL->get('CashierNo'),$new_drawer);
				ReceiptLib::drawerKick();
				$this->my_drawer = $new_drawer;
			}
			elseif (substr($_REQUEST['selectlist'],0,2) == 'SW'){
				// switch to available drawer	
				$new_drawer = substr($_REQUEST['selectlist'],2);
				foreach($this->available as $id){
					// verify the requested drawer is available
					if ($new_drawer == $id){
						if ($this->my_drawer != 0){
							// free up the current drawer if it exists
							ReceiptLib::drawerKick();
							ReceiptLib::freeDrawer($this->my_drawer);
						}
						// switch to the requested drawer
						ReceiptLib::assignDrawer($CORE_LOCAL->get('CashierNo'),$new_drawer);
						ReceiptLib::drawerKick();
						$this->my_drawer = $new_drawer;

						break;
					}
				}
			}
		}
		return True;
	}

	function head_content()
    {
		?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
		<?php
	} // END head() FUNCTION

	function body_content() {
		global $CORE_LOCAL;
		$msg = 'You are using drawer #'.$this->my_drawer;
		if ($this->my_drawer == 0)
			$msg = 'You do not have a drawer';
		$num_drawers = ($CORE_LOCAL->get('dualDrawerMode')===1) ? 2 : 1;
		$db = Database::pDataConnect();
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
			<span class="larger"><?php echo $msg; ?></span>
			<br />
		<form id="selectform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<select name="selectlist" id="selectlist" onblur="$('#selectlist').focus();">
		<option value=''>
		<?php 
		if ($this->is_admin){
			for($i=0;$i<$num_drawers;$i++){
				$nameQ = 'SELECT FirstName FROM drawerowner as d
					LEFT JOIN employees AS e ON e.emp_no=d.emp_no
					WHERE d.drawer_no='.($i+1);
				$name = $db->query($nameQ);
				if ($db->num_rows($name) > 0)
					$name = array_pop($db->fetch_row($name));
				if (empty($name)) $name = 'Unassigned';
				printf('<option value="TO%d">Take over drawer #%d (%s)</option>',
					($i+1),($i+1),$name);
			}
		}
		elseif (count($this->available) > 0){
			foreach($this->available as $num){
				printf('<option value="SW%d">Switch to drawer #%d</option>',
					$num,$num);
			}
		}
		else 
			echo '<option value="">No actions available</option>';
		?>
		</select>
		</form>
		<p>
		<span class="smaller"><?php echo _("clear to cancel"); ?></span>
		</p>
		</div>
		</div>
		<?php
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
		$this->add_onload_command("\$('#selectlist').focus();");
	} // END body_content() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new drawerPage();
?>
