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

ini_set('display_errors','1');

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class mgrlogin extends NoInputPage {

	function preprocess(){
		if (isset($_REQUEST['input'])){
			$arr = $this->mgrauthenticate($_REQUEST['input']);
			echo JsonLib::array_to_json($arr);
			return False;
		}
		return True;
	}

	function head_content(){
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
				},
				success: function(data){
					if (data.cancelOrder){
						$.ajax({
							url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-end.php',
							type: 'get',
							data: 'receiptType=cancelled',
							cache: false,
							success: function(data2){
								location = '<?php echo $this->page_url; ?>gui-modules/pos2.php';
							}
						});
					}
					else if (data.giveUp){
						location = '<?php echo $this->page_url; ?>gui-modules/pos2.php';
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
		$this->default_parsewrapper_js();
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->add_onload_command("\$('#reginput').focus();\n");
		$style = "style=\"background:#004080;\"";
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay" <?php echo $style; ?>>
		<span class="larger">
		<?php echo _("confirm cancellation"); ?>
		</span><br />
		<form name="form" id="formlocal" method="post" 
			autocomplete="off" onsubmit="return submitWrapper();">
		<input type="password" name="reginput" tabindex="0" 
			onblur="$('#reginput').focus();" id="reginput" />
		</form>
		<p>
		<span id="localmsg"><?php echo _("please enter manager password"); ?></span>
		</p>
		</div>
		</div>
		<?php
		$CORE_LOCAL->set("beep","noScan");
	} // END true_body() FUNCTION

	function mgrauthenticate($password){
		global $CORE_LOCAL;
		$CORE_LOCAL->set("away",1);

		$ret = array(
			'cancelOrder'=>false,
			'color'=>'#800000',
			'msg'=>_('password invalid'),
			'heading'=>_('re-enter manager password'),
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

		$db = Database::pDataConnect();
		$priv = sprintf("%d",$CORE_LOCAL->get("SecurityCancel"));
		$query = "select emp_no, FirstName, LastName from employees where EmpActive = 1 and frontendsecurity >= $priv "
		."and (CashierPassword = ".$password." or AdminPassword = ".$password.")";
		$result = $db->query($query);
		$num_rows = $db->num_rows($result);

		if ($num_rows != 0) {
			$this->cancelorder();
			$ret['cancelOrder'] = true;
		}

		return $ret;
	}

	function cancelorder() {
		global $CORE_LOCAL;

		$CORE_LOCAL->set("msg",2);
		$CORE_LOCAL->set("plainmsg",_("transaction cancelled"));
		$CORE_LOCAL->set("beep","rePoll");
		UdpComm::udpSend("rePoll");
		$CORE_LOCAL->set("ccTermOut","reset");
		$CORE_LOCAL->set("receiptType","cancelled");
	}
}

new mgrlogin();
?>
