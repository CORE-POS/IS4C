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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 13Jan2013 Eric Lee Added MTL for Ontario Meal Tax Rebate

*/

class Totals extends Parser {

	function check($str){
		if ($str == "FNTL" || $str == "TETL" ||
		    $str == "FTTL" || $str == "TL" ||
			$str == "MTL" || $str == "WICTL" ||
		    substr($str,0,2) == "FN")
			return True;
		return False;
	}

	function parse($str){
		global $CORE_LOCAL;
		$ret = $this->default_json();
		if ($str == "FNTL"){
			$ret['main_frame'] = MiscLib::base_url().'gui-modules/fsTotalConfirm.php';
		}
		elseif ($str == "TETL"){
			$ret['main_frame'] = MiscLib::base_url().'gui-modules/requestInfo.php?class=Totals';
		}
		elseif ($str == "FTTL")
			PrehLib::finalttl();
		elseif ($str == "TL"){
            $CORE_LOCAL->set('End', 0);
			$chk = PrehLib::ttl();
			if ($chk !== True)
				$ret['main_frame'] = $chk;
		}
		elseif ($str == "MTL"){
			$chk = PrehLib::omtr_ttl();
			if ($chk !== True)
				$ret['main_frame'] = $chk;
		} elseif ($str == "WICTL") {
            $ttl = PrehLib::wicableTotal();
            $ret['output'] = DisplayLib::boxMsg(
                _('WIC Total') . sprintf(': $%.2f', $ttl), 
                '', 
                true
            );

            // return early since output has been set
            return $ret;
        }

		if (!$ret['main_frame']){
			$ret['output'] = DisplayLib::lastpage();
			$ret['redraw_footer'] = True;
		}
		return $ret;
	}

	static $requestInfoHeader = 'tax exempt';
	static $requestInfoMsg = 'Enter the tax exempt ID';
	static function requestInfoCallback($info){
		TransRecord::addTaxExempt();
		TransRecord::addcomment("Tax Ex ID# ".$info);
		return True;
	}

	function doc(){
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>FNTL</td>
				<td>Foodstamp eligible total</td>
			</tr>
			<tr>
				<td>TETL</td>
				<td>Tax exempt total</td>
			</tr>
			<tr>
				<td>FTTL</td>
				<td>Final total</td>
			</tr>
			<tr>
				<td>TL</td>
				<td>Re-calculate total</td>
			</tr>
			<tr>
				<td>MTL</td>
				<td>Ontario (Canada) Meal Tax Rebate
				<br />Remove Provincial tax on food up to \$4 to this point in the transaction.</td>
			</tr>
			</table>";
	}
}

?>
