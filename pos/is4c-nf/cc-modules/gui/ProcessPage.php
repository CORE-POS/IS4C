<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/* ProcessPage
 *
 * Credit Card handling page
 * Based on the structure of the BasicPage class in gui-class-lib
 * but more self-contained. Fewer extraneous files are included
 * and paths are hard coded rather than auto-detected to be
 * exactly sure what scripts are included
 */

include_once("../lib/LS_Access.php");
include_once("../lib/SigCapture.php");
$CORE_LOCAL = new LS_Access();
if (!class_exists("PaycardLib")) include_once("../lib/PaycardLib.php");

include("../../lib/AutoLoader.php");

class ProcessPage {

	var $errors;
	var $td;

	function ProcessPage(){
		$this->td = SigCapture::term_object();
		$this->errors = "";
		if ($this->preprocess()){
			/* clear any POST data; only the preprocess() method
			   should be able to access input */
			if(isset($_POST['reginput'])) unset($_POST['reginput']);
			if(isset($_REQUEST['reginput'])) unset($_REQUEST['reginput']);
			ob_start();
			$this->print_page();
			while (ob_get_level() > 0)
				ob_end_flush();
		}
	}

	function head_content(){
		/* simple background poll
		 * check to see if device has data available
		 * if so, submit form so data is collected on
		 * next page load */
		?>
		<script type="text/javascript">
		var xmlHttpReq = false;
		function callbackf(){
			if (self.xmlHttpReq.readyState == 4) {
				if (xmlHttpReq.responseText=='yes'){
					document.getElementById('reginput').value='';
					document.getElementById('formlocal').submit();
				}
				else {
					setTimeout('xmlhttpPost()',500);
				}
			}
		}
		<?php if (is_object($this->td)){ ?>
		function xmlhttpPost() {
			if (window.XMLHttpRequest) {
				xmlHttpReq = new XMLHttpRequest();
			}
			else if (window.ActiveXObject) {
				xmlHttpReq = new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlHttpReq.open('POST', 'ProcessPage.php', true);
			xmlHttpReq.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xmlHttpReq.onreadystatechange = callbackf;
			xmlHttpReq.send('poll=1');
		}
		<?php } else { ?>
		function xmlhttpPost(){}
		<?php } ?>
		</script>
		<?php
	}

	function body_content(){
		global $CORE_LOCAL;
		$this->input_header();
		?>
		<div class="baseHeight">
		<?php
		// generate message to print
		$type = $CORE_LOCAL->get("paycard_type");
		$mode = $CORE_LOCAL->get("paycard_mode");
		$amt = $CORE_LOCAL->get("paycard_amount");
		$due = $CORE_LOCAL->get("amtdue");
		if (!empty($this->errors)){
			if (is_array($this->errors)) echo $this->errors['output'];
			else echo PaycardLib::paycard_msgBox($type,$this->errors,"","[clear] to cancel");
		}
		elseif( !is_numeric($amt) || abs($amt) < 0.005) {
			echo PaycardLib::paycard_msgBox($type,"Invalid Amount: $amt $due",
				"Enter a different amount","[clear] to cancel");
		} else if( $amt > 0 && $due < 0) {
			echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
				"Enter a negative amount","[clear] to cancel");
		} else if( $amt < 0 && $due > 0) {
			echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
				"Enter a positive amount","[clear] to cancel");
		} else if( abs($amt) > abs($due)) {
			echo PaycardLib::paycard_msgBox($type,"Invalid Amount",
				"Enter a lesser amount","[clear] to cancel");
		} else if( $amt > 0) {
			echo PaycardLib::paycard_msgBox($type,"Tender ".PaycardLib::paycard_moneyFormat($amt)."?","","[swipe] to continue if correct<br>[enter] to read from device<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else if( $amt < 0) {
			echo PaycardLib::paycard_msgBox($type,"Refund ".PaycardLib::paycard_moneyFormat($amt)."?","","[swipe] to continue if correct<br>[enter] to read from device<br>Enter a different amount if incorrect<br>[clear] to cancel");
		} else {
			echo PaycardLib::paycard_errBox($type,"Invalid Entry",
				"Enter a different amount","[clear] to cancel");
		}
		$CORE_LOCAL->set("msgrepeat",2);
		?>
		</div>
		<?php
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
	}

	function preprocess(){
		global $CORE_LOCAL;

		// handle AJAX calls
		if (isset($_REQUEST['poll'])){
			$chk = "";
			if (is_object($this->td)){
				$chk = $this->td->poll("getmag");	
			}
			if (!empty($chk)) echo "yes";
			else echo "no";
			return False;
		}

		$this->errors = "";
		// check for posts before drawing anything, so we can redirect
		if(isset($_REQUEST['reginput'])) {
			$input = $_REQUEST['reginput'];

			if (strlen($input) == 0){
				if (is_object($this->td)){
					$res = $this->td->poll("getmag");
					$input = $res;
				}
			}

			// CL always exits
			if( strtoupper($input) == "CL") {
				$CORE_LOCAL->set("msgrepeat",0);
				$CORE_LOCAL->set("toggletax",0);
				$CORE_LOCAL->set("endorseType","");
				$CORE_LOCAL->set("togglefoodstamp",0);
				$CORE_LOCAL->set("ccTermOut","resettotal:".
					str_replace(".","",sprintf("%.2f",$CORE_LOCAL->get("amtdue"))));
				PaycardLib::paycard_reset();
				header("Location: ../../gui-modules/pos2.php");
				return False;
			}
			else if (strlen($input)>0 && ($input[0] == "?" || strlen($input) >= 18)){
				/* card data was entered
				   extract the pan, expiration, and/or track data
					
				   PAN and track data are NOT stored in PHP session.
				   They only exist in memory and will be gone when
				   this script finishes executing
				*/
				$pan = array();
				if ($input[0] != "%" && $input[0] != ";" && $input[0] != "T"){
					$CORE_LOCAL->set("paycard_manual",1);
					if (!ctype_digit($input)){
						$this->errors = "Entry unknown. Please enter data like:<br>
							CCCCCCCCCCCCCCCCMMYY";
						return True;
					}
					$pan['pan'] = substr($input,0,-4);
					$CORE_LOCAL->set("paycard_exp",substr($input,-4,4));
				}
				else {
					$stripe = PaycardLib::paycard_magstripe($input);
					if (!is_array($stripe)){
						$this->errors = "Bad swipe. Please try again or type in manually";
						return True;
					}
					$pan['pan'] = $stripe['pan'];
					$pan['tr1'] = $stripe['tr1'];
					$pan['tr2'] = $stripe['tr2'];
					$pan['tr3'] = $stripe['tr3'];
					$CORE_LOCAL->set("paycard_exp",$stripe["exp"]);
					$CORE_LOCAL->set("paycard_name",$stripe["name"]);
				}
				$CORE_LOCAL->set("paycard_type",PaycardLib::paycard_type($pan['pan']));
				$CORE_LOCAL->set("paycard_issuer",PaycardLib::paycard_issuer($pan['pan']));
				/* find the module for this card type */
				$ccMod = null;
				foreach($CORE_LOCAL->get("RegisteredPaycardClasses") as $rpc){
					if (!class_exists($rpc)) include_once("../$rpc.php");
					$ccMod = new $rpc();
					if ($ccMod->handlesType($CORE_LOCAL->get("paycard_type")))
						break;
				}
				if ($ccMod == null){
					$this->errors = "Unknown or unsupported card type";
					$fp = fopen('../../is4c-nf/badSwipeLog.log','a');
					fwrite($fp,date('r').': type : '.$CORE_LOCAL->get("paycard_type")."\n");
					fwrite($fp,'Read length: '.strlen($pan['pan'])."\n");
					fwrite($fp,'Track 1: '.($pan['tr1']?'Yes':'No')."\n");
					fwrite($fp,'Track 2: '.($pan['tr2']?'Yes':'No')."\n");
					fclose($fp);
					return True;
				}
				/* module performs additional validation */
				$ccMod->setPAN($pan);
				$chk = $ccMod->entered(True,array());
				if(isset($chk['output']) && !empty($chk['output'])){
					$this->errors = $chk;
					return True;
				}

				/* submit the transaction to the gateway */
				$json = array();
				$json['main_frame'] = '../../gui-modules/paycardSuccess.php';
				$json['receipt'] = false;
				$result = $ccMod->doSend($CORE_LOCAL->get("paycard_mode"));
				if (is_object($this->td)){
					var_dump($CORE_LOCAL->get("ccTermOut"));
					$this->td->WriteToScale($CORE_LOCAL->get("ccTermOut"));
					$CORE_LOCAL->set("ccTermOut","");
				}

				if ($result == PaycardLib::PAYCARD_ERR_OK){
					$json = $ccMod->cleanup($json);
					$CORE_LOCAL->set("strRemembered","");
					$CORE_LOCAL->set("msgrepeat",0);
				}
				else {
					PaycardLib::paycard_reset();
					$CORE_LOCAL->set("msgrepeat",0);
					$json['main_frame'] = '../../gui-modules/boxMsg2.php';
				}
				/* transaction complete; go to success or error page */
				header("Location: ".$json['main_frame']);
				return False;
			}
			else if(strlen($input) > 0 && substr(strtoupper($input),-2) != "CL") {
				// any other input is an alternate amount
				$CORE_LOCAL->set("paycard_amount","invalid");
				if(is_numeric($input)){
					$CORE_LOCAL->set("paycard_amount",$input/100);
					if (is_object($this->td)){
						$this->td->WriteToScale("resettotal:".$input);
					}	
				}
			}
		} // end form post to self
		else {
			PaycardLib::paycard_reset();
			$CORE_LOCAL->set("paycard_mode",PaycardLib::PAYCARD_MODE_AUTH);
			$CORE_LOCAL->set("paycard_type",PaycardLib::PAYCARD_TYPE_CREDIT);
			$CORE_LOCAL->set("paycard_amount",$CORE_LOCAL->get("amtdue"));		
			$CORE_LOCAL->set("paycard_manual",0);
			if (is_object($this->td)){
				$this->td->WriteToScale("total:".($CORE_LOCAL->get("paycard_amount")*100));
			}
		} 

		return True;
	}

	function print_page(){
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html>
		<?php
		echo "<head>";
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"../../pos.css\">";
		$this->head_content();
		echo "</head>";
		if(empty($this->errors))
			echo "<body onload=\"betterDate();setTimeout('xmlhttpPost()',500);document.getElementById('reginput').focus();\">";
		else
			echo "<body onload=\"betterDate();\">";
		echo "<div id=\"boundingBox\">";
		$this->body_content();	
		echo "</div>";
		echo "</body>";
		echo "</html>";
	}

	function input_header(){
		global $CORE_LOCAL;
		
		// this needs to be configurable; just fixing
		// a giant PHP warning for the moment
		$time = strftime("%m/%d/%y %I:%M %p", time());

		$CORE_LOCAL->set("repeatable",0);
		?>
		<script type="text/javascript">
		function betterDate() {
			var myNow = new Date();
			var ampm = 'AM';
			var hour = myNow.getHours();
			var minute = myNow.getMinutes();
			if (hour >= 12){
				ampm = 'PM';
				hour = hour - 12;
			}
			if (hour == 0) hour = 12;

			var year = myNow.getYear() % 100;
			var month = myNow.getMonth()+1;
			var day = myNow.getDate();
			if (year < 10) year = '0'+year;
			if (month < 10) month = '0'+month;
			if (day < 10) day ='0'+day;
			if (minute < 10) minute = '0'+minute;

			var timeStr = month+'/'+day+'/'+year+' ';
			timeStr += hour+':'+minute+' '+ampm;
			document.getElementById('timeSpan').innerHTML = timeStr;
			setTimeout(betterDate,20000);
		}
		</script>
		<div id="inputArea">
			<div class="inputform <?php echo ($CORE_LOCAL->get("training")==1?'training':''); ?>">
				<form name="form" id="formlocal" method="post" autocomplete="off"
					action="ProcessPage.php">
				<input name="reginput" value="" onblur="document.getElementById('reginput').focus();"
					type="password" id="reginput"  />
				</form>
			</div>
			<div class="notices <?php echo ($CORE_LOCAL->get("training")==1?'training':''); ?>">
			<?php
			if ($CORE_LOCAL->get("training") == 1) {
				echo "<span class=\"text\">training </span>"
				     ."<img src='../../graphics/BLUEDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			elseif ($CORE_LOCAL->get("standalone") == 0) {
				echo "<img src='../../graphics/GREENDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			else {
				echo "<span class=\"text\">stand alone</span>"
				     ."<img src='../../graphics/REDDOT.GIF'>&nbsp;&nbsp;&nbsp;";
			}
			if($CORE_LOCAL->get("CCintegrate") == 1 && 
				$CORE_LOCAL->get("ccLive") == 1 && $CORE_LOCAL->get("training") == 0){
			   echo "<img src='../../graphics/ccIn.gif'>&nbsp;";
			}elseif($CORE_LOCAL->get("CCintegrate") == 1 && 
				($CORE_LOCAL->get("training") == 1 || $CORE_LOCAL->get("ccLive") == 0)){
			   echo "<img src='../../graphics/ccTest.gif'>&nbsp;";
			}

			echo "<span id=\"timeSpan\" class=\"time\">".$time."</span>\n";
			?>

			</div>
		</div>
		<div id="inputAreaEnd"></div>
		<?php
	}
}

new ProcessPage();

?>
