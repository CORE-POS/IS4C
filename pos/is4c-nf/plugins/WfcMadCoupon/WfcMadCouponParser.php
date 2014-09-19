<?php
/*******************************************************************************

    Copyright 2007,2013 Whole Foods Co-op

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

class WfcMadCouponParser extends Parser {

	function check($str){
		if ($str == "MA")
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		Database::getsubtotals();
		$amt = $CORE_LOCAL->get('runningTotal') - $CORE_LOCAL->get('transDiscount');
		$madCoup = number_format($amt * 0.05, 2);
		if ($madCoup > 2.50) $madCoup = 2.50;
		TransRecord::addRecord(array(
            'upc' => "MAD Coupon", 
            'description' => "Member Appreciation Coupon", 
            'trans_type' => "I", 
            'trans_subtype' => "CP", 
            'trans_status' => "C", 
            'quantity' => 1, 
            'ItemQtty' => 1, 
			'unitPrice' => -1*$madCoup,
			'total' => -1*$madCoup,
			'regPrice' => -1*$madCoup,
            'voided' => 17,
        ));
		$ret = $this->default_json();
		$ret['output'] = DisplayLib::lastpage();
		$ret['redraw_footer'] = true;
		return $ret;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>MA</td>
				<td>Add quarterly member coupon
				(WFC specific)</td>
			</tr>
			</table>";
	}

}

?>
