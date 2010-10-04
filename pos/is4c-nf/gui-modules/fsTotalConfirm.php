<?php

if (!class_exists("NoInputPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/NoInputPage.php");
if (!function_exists("ttl")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/prehkeys.php");
if (!isset($IS4C_LOCAL)) include($_SERVER["DOCUMENT_ROOT"]."/lib/LocalStorage/conf.php");

class fsTotalConfirm extends NoInputPage {

	var $tendertype;

	function preprocess(){
		$this->tendertype = "";
		if (isset($_REQUEST["selectlist"])){
			$choice = $_REQUEST["selectlist"];
			if ($choice == "EF"){
				fsEligible();
				$this->tendertype = 'EF';
			}
			elseif ($choice == "EC"){
				ttl();
				$this->tendertype = 'EC';
			}
			else if ($choice == ''){
				header("Location: /gui-modules/pos2.php");
				return False;
			}
		}
		elseif (isset($_REQUEST['tendertype'])){
			$this->tendertype = $_REQUEST['tendertype'];
			$valid_input = False;
			$in = $_REQUEST['tenderamt'];
			if (empty($in)){
				if ($this->tendertype == 'EF')
					$IS4C_LOCAL->set("strRemembered",100*$IS4C_LOCAL->get("fsEligible")."EF");		
				else
					$IS4C_LOCAL->set("strRemembered",100*$IS4C_LOCAL->get("runningTotal")."EC");		
				$IS4C_LOCAL->set("msgrepeat",1);
				$valid_input = True;
			}
			elseif (is_numeric($in)){
				$IS4C_LOCAL->set("strRemembered",$in.$this->tendertype);
				$IS4C_LOCAL->set("msgrepeat",1);
				$valid_input = True;
			}
			elseif (strtoupper($in) == "CL"){
				$IS4C_LOCAL->set("strRemembered","");
				$IS4C_LOCAL->set("msgrepeat",0);
				$valid_input = True;
			}

			if ($valid_input){
				header("Location: /gui-modules/pos2.php");
				return False;
			}
		}	
		return True;
	}
	
	function head_content(){
		?>
		<script type="text/javascript" >
		var prevKey = -1;
		var prevPrevKey = -1;
		function processkeypress(e) {
			var jsKey;
			if (e.keyCode) // IE
				jsKey = e.keyCode;
			else if(e.which) // Netscape/Firefox/Opera
				jsKey = e.which;
			if (jsKey==13) {
				if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
				(prevKey == 108 || prevKey == 76) ){ //CL<enter>
					$('#selectlist :selected').val('');
				}
				$('#selectform').submit();
			}
			prevPrevKey = prevKey;
			prevKey = jsKey;
		}
		</script> 
		<?php
	} // END head() FUNCTION

	function body_content() {
		global $IS4C_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
		<span class="larger">Customer is using the</span>
		<form id="selectform" method="post" action="/gui-modules/fsTotalConfirm.php">

		<?php if (empty($this->tendertype)){ ?>
			<select size="2" name="selectlist" 
				id="selectlist" onblur="$('#selectlist').focus();">
			<option value='EC' selected>Cash Portion
			<option value='EF'>Food Portion
			</select>
		<?php } else { ?>
			<input type="text" id="tenderamt" 
				name="tenderamt" onblur="$('#tenderamt').focus();" />
			<br />
			<span class="larger">Press [enter] to tender 
			$<?php printf("%.2f",($this->tendertype=='EF'?$IS4C_LOCAL->get("fsEligible"):$IS4C_LOCAL->get("runningTotal"))); ?>
			as <?php echo ($this->tendertype=="EF"?"EBT Food":"EBT Cash") ?>
			or input a different amount</span>
			<br />
			<input type="hidden" name="tendertype" value="<?php echo $this->tendertype?>" />
		<?php } ?>
		</form>
		<span class="smaller">[clear] to cancel</span>
		<p />
		</div>
		</div>
		<?php
		if (empty($this->tendertype)){
			$this->add_onload_command("\$('#selectlist').focus();\n");
			$this->add_onload_command("\$('#selectlist').keypress(processkeypress);\n");
		}
		else
			$this->add_onload_command("\$('#tenderamt').focus();\n");
		$IS4C_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION
}

new fsTotalConfirm();
?>
