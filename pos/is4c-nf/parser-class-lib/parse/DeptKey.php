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

class DeptKey extends Parser 
{
	public function check($str)
    {
		if (strstr($str,"DP") && strlen($str) > 3 &&
		    substr($str,0,2) != "VD") {
			return true;
        }

		return false;
	}

	public function parse($str)
    {
		global $CORE_LOCAL;
		$my_url = MiscLib::base_url();

		$split = explode("DP",$str);
		$dept = $split[1];
		$amt = $split[0];
        if (strstr($amt, '.')) {
            $amt = round($amt * 100);
        }
		$ret = $this->default_json();

		/**
		  This "if" is the new addition to trigger the
		  department select screen
		*/
		if (empty($split[1])) {
			// no department specified, just amount followed by DP
			
			// maintain refund if needed
			if ($CORE_LOCAL->get("refund")) {
				$amt = "RF" . $amt;
            }

			// save entered amount
			$CORE_LOCAL->set("departmentAmount",$amt);

			// go to the department select screen
			$ret['main_frame'] = $my_url.'gui-modules/deptlist.php';
		} else if ($CORE_LOCAL->get("refund")==1 && $CORE_LOCAL->get("refundComment") == "") {
			if ($CORE_LOCAL->get("SecurityRefund") > 20) {
				$ret['main_frame'] = $my_url."gui-modules/adminlogin.php?class=RefundAdminLogin";
			} else {
				$ret['main_frame'] = $my_url.'gui-modules/refundComment.php';
            }
			$CORE_LOCAL->set("refundComment",$CORE_LOCAL->get("strEntered"));
		}

		/* apply any appropriate special dept modules */
		$deptmods = $CORE_LOCAL->get('SpecialDeptMap');
		$index = (int)($dept/10);
		if (is_array($deptmods) && isset($deptmods[$index])) {
			foreach($deptmods[$index] as $mod) {
				$obj = new $mod();
				$ret = $obj->handle($dept,$amt/100,$ret);
			}
		}
		
		if (!$ret['main_frame']) {
			$ret = PrehLib::deptkey($amt, $dept, $ret);
        }

		return $ret;
	}

	public function doc()
    {
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

