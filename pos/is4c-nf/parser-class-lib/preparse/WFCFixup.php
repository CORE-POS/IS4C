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

class WFCFixup extends PreParser {
	var $remainder;
	
	function check($str){
		global $CORE_LOCAL;
        $as_upc = str_pad($str, 13, '0', STR_PAD_LEFT);
		if (substr($str,-3) == "QK9"){
			$this->remainder = str_replace("QK9","QM9",$str);
			return True;
		} else if (substr($str,-4) == "QK10"){
			$this->remainder = str_replace("QK10","QM10",$str);
			return True;
		} else if (strstr($str, '59070000087') || strstr($str, '59070000087') || strstr($str, '59070000087')) {
            // stupid Herb Pharm coupon. Expires 30Apr14
            $this->remainder = '59070099287';
            return true;
        } else if ($str == 'MA' || $str == 'OB') {
            // re-write old WFC quarterly coupon as houseCoupon UPC
            $this->remainder = '0049999900001';
            return true;
        } else if ($str == 'AD') {
            // re-write WFC access coupon as houseCoupon UPC
            $this->remainder = '0049999900002';
            return true;
        } else if (($as_upc == '0000000001112' || $as_upc == '0000000001113') && $CORE_LOCAL->get('msgrepeat') == 0) {
            $this->remainder = 'QM708';
            return true;
        }
		return False;
	}

	function parse($str){
		return $this->remainder;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td><i>discount</i>DI<i>item</i></td>
				<td>Set a percent discount <i>discount</i>
				for just one item <i>item</i></td>
			</tr>
			<tr>
				<td><i>discount</i>PD<i>item</i></td>
				<td>Same as DI above</td>
			</tr>
			</table>";
	}
}

?>
