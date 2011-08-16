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

if (!class_exists('BasicPage')) include($IS4C_PATH.'gui-class-lib/BasicPage.php');
if (!function_exists('checkLogin')) include($IS4C_PATH.'auth/login.php');

class createAccount extends BasicPage {

	function js_content(){
		?>
		$(document).ready(function(){
			$('#fullname').focus();
		});
		<?php
	}

	var $entries;

	function main_content(){
		global $IS4C_PATH;
		?>
		<div id="loginTitle">Create an Account<br />
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table cellspacing="4" cellpadding="4">
		<tr>
		<th>Full Name</th>
		<td><input id="fullname" type="text" name="fn" value="<?php echo $this->entries['name']; ?>" /></td>
		</tr><tr>
		<th>E-mail address</th>
		<td><input type="text" name="email" value="<?php echo $this->entries['email']; ?>" /></td>
		</tr><tr>
		<th>Password</th>
		<td><input type="password" name="passwd" value="<?php echo $this->entries['passwd']; ?>" /></td>
		</tr><tr>
		<th>Re-Type Password</th>
		<td><input type="password" name="passwd2" value="<?php echo $this->entries['passwd']; ?>" /></td>
		</tr><tr>
		<th>Member-Owner</th>
		<td><select name="owner">
			<option value="0" <?php echo $this->entries['owner']==0?'selected':''?>>No</option>
			<option value="1" <?php echo $this->entries['owner']==1?'selected':''?>>Yes</option>
		</select></td>
		</tr><tr>
		<th><input type="submit" value="Create Account" name="submit" /></th>
		<td>&nbsp;</td>
		</tr>
		</table>
		</form>
		</div>
		<?php
	}

	function preprocess(){
		global $IS4C_PATH;
		$this->entries = array(
			'name'=>(isset($_REQUEST['fn'])?$_REQUEST['fn']:''),
			'email'=>(isset($_REQUEST['email'])?$_REQUEST['email']:''),
			'passwd'=>(isset($_REQUEST['passwd'])?$_REQUEST['passwd']:''),
			'owner'=>(isset($_REQUEST['owner'])?$_REQUEST['owner']:0)
		);

		if (isset($_REQUEST['submit'])){
			// validate
			$errors = False;

			if (!isEmail($this->entries['email'],FILTER_VALIDATE_EMAIL)){
				echo '<div class="errorMsg">';
				echo 'Not a valid e-mail address: '.$this->entries['email'];
				echo '</div>';
				$this->entries['email'] = '';
				$errors = True;
			}

			if ($_REQUEST['passwd'] !== $_REQUEST['passwd2']){
				echo '<div class="errorMsg">';
				echo 'Passwords do not match';
				echo '</div>';
				$this->entries['passwd'] = '';
				$errors = True;
			}

			if (empty($_REQUEST['passwd'])){
				echo '<div class="errorMsg">';
				echo 'Password is required';
				echo '</div>';
				$this->entries['passwd'] = '';
				$errors = True;
			}

			if (empty($this->entries['name'])){
				echo '<div class="errorMsg">';
				echo 'Name is required';
				echo '</div>';
				$this->entries['name'] = '';
				$errors = True;
			}

			if (!$errors){
				$created = createLogin($this->entries['email'],
					$this->entries['passwd'],
					$this->entries['name'],
					$this->entries['owner']);

				if ($created){
					login($this->entries['email'],$this->entries['passwd']);
					header("Location: {$IS4C_PATH}gui-modules/storefront.php");
					return False;
				}
				else {
					echo '<div class="errorMsg">';
					echo 'Account already exists: '.$this->entries['email'];
					echo '</div>';
					$this->entries['email'] = '';
				}
			}
		}
		return True;
	}
}

new createAccount();

?>
