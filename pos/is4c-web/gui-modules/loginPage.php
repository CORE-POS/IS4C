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
include($IS4C_PATH.'ini.php');


if (!class_exists('BasicPage')) include($IS4C_PATH.'gui-class-lib/BasicPage.php');
if (!function_exists('checkLogin')) include($IS4C_PATH.'auth/login.php');

class loginPage extends BasicPage {

	function js_content(){
		?>
		$(document).ready(function(){
			$('#loginEmail').focus();
		});
		<?php
	}

	function main_content(){
		global $IS4C_PATH;
		?>
		<div id="loginTitle"><!--IS4C Online Version 1.0--><br />
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table cellspacing="4" cellpadding="4">
		<tr>
		<th>E-mail address</th>
		<td><input id="loginEmail" type="text" name="email" /></td>
		</tr><tr>
		<th>Password</th>
		<td><input type="password" name="passwd" /></td>
		</tr><tr>
		<th><input type="submit" value="Login" /></th>
		<td><a href="<?php echo $IS4C_PATH;?>gui-modules/forgotPassword.php">Forget your password</a>?</td>
		</tr>
		</table>
		<br />
		<a href="<?php echo $IS4C_PATH;?>gui-modules/createAccount.php">Create an account</a>
		</form>
		</div>
		<?php
	}

	function preprocess(){
		global $IS4C_PATH;
		if (isset($_REQUEST['logout'])){
			logout();
			return True;
		}

		if (isset($_REQUEST['email'])){
			if (!isEmail($_REQUEST['email'])){
				echo '<div class="errorMsg">';
				echo 'Not a valid e-mail address: '.$_REQUEST['email'];
				echo '</div>';
				return True;
			}
			else if (!login($_REQUEST['email'],$_REQUEST['passwd'])){
				echo '<div class="errorMsg">';
				echo 'Incorrect e-mail address or password';
				echo '</div>';
				return True;
			}
			else {
				header("Location: {$IS4C_PATH}gui-modules/storefront.php");
				return False;
			}
		}
		return True;
	}
}

new loginPage();

?>
