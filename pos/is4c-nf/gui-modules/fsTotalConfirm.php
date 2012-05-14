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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

ini_set('display_errors','1');

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class fsTotalConfirm extends NoInputPage {

	var $tendertype;

	function preprocess(){
		global $CORE_PATH,$CORE_LOCAL;
		$this->tendertype = "";
		if (isset($_REQUEST["selectlist"])){
			$choice = $_REQUEST["selectlist"];
			if ($choice == "EF"){
				$chk = PrehLib::fsEligible();
				if ($chk !== True){
					$this->change_page($chk);
					return False;
				}
				$this->tendertype = 'EF';
			}
			elseif ($choice == "EC"){
				$chk = PrehLib::ttl();
				if ($chk !== True){
					$this->change_page($chk);
					return False;
				}
				$this->tendertype = 'EC';
			}
			else if ($choice == ''){
				$this->change_page($CORE_PATH."gui-modules/pos2.php");
				return False;
			}
		}
		elseif (isset($_REQUEST['tendertype'])){
			$this->tendertype = $_REQUEST['tendertype'];
			$valid_input = False;
			$in = $_REQUEST['tenderamt'];
			if (empty($in)){
				if ($this->tendertype == 'EF')
					$CORE_LOCAL->set("strRemembered",100*$CORE_LOCAL->get("fsEligible")."EF");		
				else
					$CORE_LOCAL->set("strRemembered",100*$CORE_LOCAL->get("runningTotal")."EC");		
				$CORE_LOCAL->set("msgrepeat",1);
				$valid_input = True;
			}
			elseif (is_numeric($in)){
				if ($this->tendertype == 'EF' && $in > (100*$CORE_LOCAL->get("fsEligible")))
					$valid_input = False;
				else {
					$CORE_LOCAL->set("strRemembered",$in.$this->tendertype);
					$CORE_LOCAL->set("msgrepeat",1);
					$valid_input = True;
				}
			}
			elseif (strtoupper($in) == "CL"){
				$CORE_LOCAL->set("strRemembered","");
				$CORE_LOCAL->set("msgrepeat",0);
				$valid_input = True;
			}

			if ($valid_input){
				$this->change_page($CORE_PATH."gui-modules/pos2.php");
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
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
		<?php if (empty($this->tendertype)){ ?>
		<span class="larger">Customer is using the</span>
		<?php } ?>
		<form id="selectform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">

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
			$<?php printf("%.2f",($this->tendertype=='EF'?$CORE_LOCAL->get("fsEligible"):$CORE_LOCAL->get("runningTotal"))); ?>
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
		$CORE_LOCAL->set("scan","noScan");
	} // END body_content() FUNCTION
}

new fsTotalConfirm();
?>
