<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");

if (!class_exists('UserPage')) include($IS4C_PATH.'gui-class-lib/UserPage.php');
if (!function_exists('checkLogin')) include($IS4C_PATH.'auth/login.php');

class changePassword extends UserPage {

	function js_content(){
		?>
		$(document).ready(function(){
			$('#oldpasswd').focus();
		});
		<?php
	}

	var $logged_in_user;
	var $changed;

	function main_content(){
		global $IS4C_PATH;
		if (!$this->changed){
		?>
		<div id="loginTitle">Change Account Password<br />
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table cellspacing="4" cellpadding="4">
		<tr>
		<th>E-mail address</th>
		<td><?php echo $this->logged_in_user; ?></td>
		</tr><tr>
		<th>Old Password</th>
		<td><input type="password" name="old" id="oldpasswd" /></td>
		</tr><tr>
		<th>New Password</th>
		<td><input type="password" name="passwd" /></td>
		</tr><tr>
		<th>Re-Type New Password</th>
		<td><input type="password" name="passwd2" /></td>
		</tr><tr>
		<th><input type="submit" value="Change Password" name="submit" /></th>
		<td>&nbsp;</td>
		</tr>
		</table>
		</form>
		</div>
		<?php
		}
		else {
			echo '<div class="successMsg">';
			echo 'Your password has been changed';
			echo '</div>';
		}
	}

	function preprocess(){
		global $IS4C_PATH;
		$this->logged_in_user = checkLogin();
		$this->changed = False;

		$dbc = pDataConnect();

		$q = sprintf("SELECT name FROM Users WHERE name='%s'",
			$dbc->escape($this->logged_in_user));
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) == 0){
			// sanity check; shouldn't ever happen
			header("Location: {$IS4C_PATH}gui-modules/loginPage.php");
			return False;
		}

		if (isset($_REQUEST['submit'])){
			// validate

			if ($_REQUEST['passwd'] !== $_REQUEST['passwd2']){
				echo '<div class="errorMsg">';
				echo 'New passwords do not match';
				echo '</div>';
				return True;
			}
			else if (empty($_REQUEST['passwd'])){
				echo '<div class="errorMsg">';
				echo 'New passwords cannot be blank';
				echo '</div>';
				return True;
			}
			else {
				$this->changed = changePassword($this->logged_in_user,
					$_REQUEST['old'],$_REQUEST['passwd']);
				if (!$this->changed) {
					echo '<div class="errorMsg">';
					echo 'Old password is incorrect';
					echo '</div>';
					return True;
				}
			}
		}

		return True;
	}
}

new changePassword();

?>
