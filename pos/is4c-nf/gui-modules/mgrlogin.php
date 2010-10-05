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

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

ini_set('display_errors','1');

if (!class_exists("NoInputPage")) include_once($IS4C_PATH."gui-class-lib/NoInputPage.php");
if (!isset($IS4C_LOCAL)) include($IS4C_PATH."lib/LocalStorage/conf.php");
if (!function_exists("array_to_json")) include($IS4C_PATH."lib/array_to_json.php");
if (!function_exists("pDataConnect")) include($IS4C_PATH."lib/connect.php");
if (!function_exists("udpSend")) include($IS4C_PATH."lib/udpSend.php");

class mgrlogin extends NoInputPage {

	function preprocess(){
		if (isset($_REQUEST['input'])){
			$arr = $this->mgrauthenticate($_REQUEST['input']);
			echo array_to_json($arr);
			return False;
		}
		return True;
	}

	function head_content(){
		global $IS4C_PATH;
		?>
		<script type="text/javascript">
		function submitWrapper(){
			var passwd = $('#reginput').val();
			$.ajax({
				url: '<?php echo $_SERVER['PHP_SELF']; ?>',
				data: 'input='+passwd,
				type: 'get',
				cache: false,
				dataType: 'json',
				error: function(data,st,xmlro){
					alert(st);
				},
				success: function(data){
					if (data.cancelOrder){
						$.ajax({
							url: '<?php echo $IS4C_PATH; ?>ajax-callbacks/ajax-end.php',
							type: 'get',
							data: 'receiptType=cancelled',
							cache: false,
							success: function(data2){
								location = '<?php echo $IS4C_PATH; ?>gui-modules/pos2.php';
							}
						});
					}
					else if (data.giveUp){
						location = '<?php echo $IS4C_PATH; ?>gui-modules/pos2.php';
					}
					else {
						$('div.colored').css('background',data.color);
						$('span.larger').html(data.heading);
						$('span#localmsg').html(data.msg);
						$('#reginput').val('');
						$('#reginput').focus();
					}
				}
			});
			return false;
		}
		</script>
		<?php
	}

	function body_content(){
		global $IS4C_LOCAL;
		$this->add_onload_command("\$('#reginput').focus();\n");
		$style = "style=\"background:#004080;\"";
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay" <?php echo $style; ?>>
		<span class="larger">
		confirm cancellation
		</span><br />
		<form name="form" method="post" autocomplete="off" onsubmit="return submitWrapper();">
		<input type="password" name="reginput" tabindex="0" 
			onblur="$('#reginput').focus();" id="reginput" />
		</form>
		<p />
		<span id="localmsg">please enter manager password</span>
		<p />
		</div>
		</div>
		<?php
		$IS4C_LOCAL->set("beep","noScan");
	} // END true_body() FUNCTION

	function mgrauthenticate($password){
		global $IS4C_LOCAL;
		$IS4C_LOCAL->set("away",1);

		$ret = array(
			'cancelOrder'=>false,
			'color'=>'#800000',
			'msg'=>'password invalid',
			'heading'=>'re-enter manager password',
			'giveUp'=>false
		);

		$password = strtoupper($password);
		$password = str_replace("'","",$password);

		if (!isset($password) || strlen($password) < 1 || $password == "CL") {
			$ret['giveUp'] = true;
			return $ret;
		}
		elseif (!is_numeric($password)) {
			return $ret;
		}
		elseif ($password > 9999 || $password < 1) {
			return $ret;
		}

		$db = pDataConnect();
		$query = "select emp_no, FirstName, LastName from employees where empactive = 1 and frontendsecurity >= 11 "
		."and (cashierpassword = ".$password." or adminpassword = ".$password.")";
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);

		if ($num_rows != 0) {
			$this->cancelorder();
			$ret['cancelOrder'] = true;
		}
		$db->close();

		return $ret;
	}

	function cancelorder() {
		global $IS4C_LOCAL;

		$IS4C_LOCAL->set("msg",2);
		$IS4C_LOCAL->set("plainmsg","transaction cancelled");
		$IS4C_LOCAL->set("beep","rePoll");
		udpSend("rePoll");
		$IS4C_LOCAL->set("ccTermOut","reset");
		$IS4C_LOCAL->set("receiptType","cancelled");
	}
}

new mgrlogin();
?>
