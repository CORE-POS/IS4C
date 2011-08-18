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

class forgotPassword extends BasicPage {

	var $reset;
	var $errors;

	function js_content(){
		?>
		$(document).ready(function(){
			$('#loginEmail').focus();
		});
		<?php
	}

	function main_content(){
		global $IS4C_PATH;
		if (!$this->reset){
		echo $this->errors;
		?>
		<div id="loginTitle">Password Reset<br />
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
		<table cellspacing="4" cellpadding="4">
		<tr>
		<th>E-mail address</th>
		<td><input id="loginEmail" type="text" name="email" /></td>
		</tr><tr>
		<th><input type="submit" value="Login" /></th>
		<td>&nbsp;</td>
		</tr>
		</table>
		</form>
		</div>
		<?php
		}
		else {
			echo '<div class="successMsg">';
			echo 'Your password has been reset. A copy of your new
				password has been e-mailed to '.$_REQUEST['email'];
			echo '</div>';
		}
	}

	function preprocess(){
		global $IS4C_PATH;
		$this->reset = False;
		$this->errors = '';

		if (isset($_REQUEST['email'])){
			if (!isEmail($_REQUEST['email'])){
				$this->errors .= '<div class="errorMsg">';
				$this->errors .= 'Not a valid e-mail address: '.$_REQUEST['email'];
				$this->errors .= '</div>';
				return True;
			}
			else if (!getUID($_REQUEST['email'])){
				$this->errors .= '<div class="errorMsg">';
				$this->errors .= 'No account found with e-mail address: '.$_REQUEST['email'];
				$this->errors .= '</div>';
				return True;
			}
			else {
				// generate a new random password, omitting
				// the ` character as a possibility
				srand((double) microtime() * 1000000);
				$pw = "";
				while(strlen($pw) < 12){
					$i = rand(40,125);
					if ($i != 96) $pw .= chr($i);
				}
				
				changeAnyPassword($_REQUEST['email'],$pw);
				$this->reset = True;
				$msg = "Your password has been reset\n";
				$msg .= "Your new password is: {$pw}\n";
				$headers = "From: useradmin@wholefoods.coop\r\n";

				mail($_REQUEST['email'],
					'Password Reset Request',
					$msg,
					$headers);
			}
		}
		return True;
	}
}

new forgotPassword();

?>
