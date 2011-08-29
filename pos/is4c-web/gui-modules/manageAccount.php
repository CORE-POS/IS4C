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
if (!function_exists('pDataConnect')) include($IS4C_PATH.'lib/connect.php');

class manageAccount extends BasicPage {

	function js_content(){
		?>
		$(document).ready(function(){
			$('#fullname').focus();
		});
		<?php
	}

	var $entries;
	var $logged_in_user;

	function main_content(){
		global $IS4C_PATH;
		?>
		<div id="loginTitle">Manage your Account<br />
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table cellspacing="4" cellpadding="4">
		<tr>
		<th>Full Name</th>
		<td><input id="fullname" type="text" name="fn" value="<?php echo $this->entries['name']; ?>" /></td>
		</tr><tr>
		<th>E-mail address</th>
		<td><input type="text" name="email" value="<?php echo $this->entries['email']; ?>" /></td>
		</tr><tr>
		<th>Member-Owner</th>
		<td><select name="owner">
			<option value="0" <?php echo $this->entries['owner']==0?'selected':''?>>No</option>
			<option value="1" <?php echo $this->entries['owner']==1?'selected':''?>>Yes</option>
		</select></td>
		</tr><tr>
		<th><input type="submit" value="Update Account" name="submit" /></th>
		<td><a href="<?php echo $IS4C_PATH;?>gui-modules/changePassword.php">Change Password</a></td>
		</tr>
		</table>
		</form>
		</div>
		<?php
	}

	function preprocess(){
		global $IS4C_PATH;
		$this->logged_in_user = checkLogin();

		$dbc = pDataConnect();

		$q = sprintf("SELECT name,real_name,owner FROM Users WHERE
			name='%s'",$dbc->escape($this->logged_in_user));
		$r = $dbc->query($q);
		if ($dbc->num_rows($r) == 0){
			// sanity check; shouldn't ever happen
			header("Location: {$IS4C_PATH}gui-modules/loginPage.php");
			return False;
		}
		$w = $dbc->fetch_row($r);

		$this->entries = array(
			'name'=>$w['real_name'],
			'email'=>$w['name'],
			'owner'=>$w['owner']
		);

		if (isset($_REQUEST['submit'])){
			// validate

			if ($_REQUEST['email'] != $this->entries['email']){
				if (!isEmail($_REQUEST['email'],FILTER_VALIDATE_EMAIL)){
					echo '<div class="errorMsg">';
					echo 'Not a valid e-mail address: '.$_REQUEST['email'];
					echo '</div>';
				}
				else {
					$newemail = $_REQUEST['email'];
					$upQ = sprintf("UPDATE Users SET name='%s' WHERE name='%s'",
						$dbc->escape($newemail),
						$dbc->escape($this->logged_in_user));
					$dbc->query($upQ);
					doLogin($newemail);
					$this->logged_in_user = $newemail;
					$this->entries['email'] = $newemail;
					echo '<div class="successMsg">';
					echo 'E-mail address has been updated';
					echo '</div>';
				}
			}

			if ($_REQUEST['fn'] != $this->entries['name']){
				if (empty($_REQUEST['fn'])){
					echo '<div class="errorMsg">';
					echo 'Name is required';
					echo '</div>';
				}
				else {
					$upQ = sprintf("UPDATE Users SET real_name='%s' WHERE name='%s'",
						$dbc->escape($_REQUEST['fn']),
						$dbc->escape($this->logged_in_user));
					$dbc->query($upQ);
					$this->entries['name'] = $_REQUEST['fn'];
					echo '<div class="successMsg">';
					echo 'Name has been updated';
					echo '</div>';
				}
			}

			if ($_REQUEST['owner'] != $this->entries['owner']){
				$upQ = sprintf("UPDATE Users SET owner=%d WHERE name='%s'",
					$dbc->escape($_REQUEST['owner']),
					$dbc->escape($this->logged_in_user));
				$dbc->query($upQ);
				$this->entries['owner'] = $_REQUEST['owner'];
				echo '<div class="successMsg">';
				echo 'Owner status has been updated';
				echo '</div>';
			}

		}

		return True;
	}
}

new manageAccount();

?>
