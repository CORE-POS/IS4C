<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

if (!class_exists("Parser")) include_once($IS4C_PATH."parser-class-lib/Parser.php");
if (!isset($IS4C_LOCAL)) include_once($IS4C_PATH."lib/LocalStorage/conf.php");
if (!function_exists("deptkey")) include_once($IS4C_PATH."lib/prehkeys.php");

class DeptKey extends Parser {
	function check($str){
		if (strstr($str,"DP") && strlen($str) > 3 &&
		    substr($str,0,2) != "VD")
			return True;
		return False;
	}

	function parse($str){
		global $IS4C_LOCAL,$IS4C_PATH;

		$split = explode("DP",$str);
		$dept = $split[1];
		$amt = $split[0];
		$ret = $this->default_json();

		if ($IS4C_LOCAL->get("refund")==1 && $IS4C_LOCAL->get("refundComment") == ""){
			$ret['main_frame'] = $IS4C_PATH.'gui-modules/refundComment.php';
			$IS4C_LOCAL->set("refundComment",$IS4C_LOCAL->get("strEntered"));
		}
		elseif ($IS4C_LOCAL->get("warned") == 1 and ($IS4C_LOCAL->get("warnBoxType") == "warnEquity" or $IS4C_LOCAL->get("warnBoxType") == "warnAR")){
			$IS4C_LOCAL->set("warned",0);
			$IS4C_LOCAL->set("warnBoxType","");
		}
		elseif ($dept == 991 || $dept == 992){
			$ref = trim($IS4C_LOCAL->get("CashierNo"))."-"
				.trim($IS4C_LOCAL->get("laneno"))."-"
				.trim($IS4C_LOCAL->get("transno"));
			if ($IS4C_LOCAL->get("LastEquityReference") != $ref){
				$IS4C_LOCAL->set("warned",1);
				$IS4C_LOCAL->set("warnBoxType","warnEquity");
				$IS4C_LOCAL->set("endorseType","stock");
				$IS4C_LOCAL->set("equityAmt",$price);
				$IS4C_LOCAL->set("boxMsg","<b>Equity Sale</b><br>Insert paperwork and press<br><font size=-1>[enter] to continue, [clear] to cancel</font>");
				$ret['main_frame'] = $IS4C_PATH.'gui-modules/boxMsg2.php';
			}
		}
		elseif ($dept == 990){
			$IS4C_LOCAL->set("warned",1);
			$IS4C_LOCAL->set("warnBoxType","warnAR");
			$IS4C_LOCAL->set("boxMsg","<b>A/R Payment Sale</b><br>remember to retain you<br>reprinted receipt<br><font size=-1>[enter] to continue, [clear] to cancel</font>");
			$ret['main_frame'] = $IS4C_PATH.'gui-modules/boxMsg2.php';
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
