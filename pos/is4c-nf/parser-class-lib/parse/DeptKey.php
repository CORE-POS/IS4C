<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

if (!class_exists("Parser")) include_once($CORE_PATH."parser-class-lib/Parser.php");
if (!isset($CORE_LOCAL)) include_once($CORE_PATH."lib/LocalStorage/conf.php");
if (!function_exists("deptkey")) include_once($CORE_PATH."lib/prehkeys.php");

class DeptKey extends Parser {
	function check($str){
		if (strstr($str,"DP") && strlen($str) > 3 &&
		    substr($str,0,2) != "VD")
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL,$CORE_PATH;

		$split = explode("DP",$str);
		$dept = $split[1];
		$amt = $split[0];
		$ret = $this->default_json();

		if ($CORE_LOCAL->get("refund")==1 && $CORE_LOCAL->get("refundComment") == ""){
			$ret['main_frame'] = $CORE_PATH.'gui-modules/refundComment.php';
			$CORE_LOCAL->set("refundComment",$CORE_LOCAL->get("strEntered"));
		}
		elseif ($CORE_LOCAL->get("warned") == 1 and ($CORE_LOCAL->get("warnBoxType") == "warnEquity" or $CORE_LOCAL->get("warnBoxType") == "warnAR")){
			$CORE_LOCAL->set("warned",0);
			$CORE_LOCAL->set("warnBoxType","");
		}
		elseif ($dept == 991 || $dept == 992){
			$ref = trim($CORE_LOCAL->get("CashierNo"))."-"
				.trim($CORE_LOCAL->get("laneno"))."-"
				.trim($CORE_LOCAL->get("transno"));
			if ($CORE_LOCAL->get("LastEquityReference") != $ref){
				$CORE_LOCAL->set("warned",1);
				$CORE_LOCAL->set("warnBoxType","warnEquity");
				$CORE_LOCAL->set("endorseType","stock");
				$CORE_LOCAL->set("equityAmt",$price);
				$CORE_LOCAL->set("boxMsg","<b>Equity Sale</b><br>Insert paperwork and press<br><font size=-1>[enter] to continue, [clear] to cancel</font>");
				$ret['main_frame'] = $CORE_PATH.'gui-modules/boxMsg2.php';
			}
		}
		elseif ($dept == 990){
			$CORE_LOCAL->set("warned",1);
			$CORE_LOCAL->set("warnBoxType","warnAR");
			$CORE_LOCAL->set("boxMsg","<b>A/R Payment Sale</b><br>remember to retain you<br>reprinted receipt<br><font size=-1>[enter] to continue, [clear] to cancel</font>");
			$ret['main_frame'] = $CORE_PATH.'gui-modules/boxMsg2.php';
		}
		
		if (!$ret['main_frame'])
			$ret = deptkey($split[0],$split[1],$ret);
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>amount</i>DP<i>department</i>0</td>
				<td>Ring up <i>amount</i> to the specified
				<i>department</i>. The trailing zero is
				necessary for historical purposes</td>
			</tr>
			</table>";
	}
}

?>
