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

class EndOfShift extends Parser 
{
	function check($str)
    {
		if ($str == "ES") {
			return true;
        }

		return false;
	}

	function parse($str)
    {
		$json = $this->default_json();

        CoreLocal::set("memberID", CoreLocal::get('defaultNonMem'));
        CoreLocal::set("memMsg","End of Shift");
        TransRecord::addRecord(array(
            'upc' => 'ENDOFSHIFT',
            'description' => 'End of Shift',
            'trans_type' => 'S',
        ));
        Database::getsubtotals();
        $chk = self::ttl();
        if ($chk !== true) {
            $json['main_frame'] = $chk;
            return $json;
        }
        CoreLocal::set("runningtotal",CoreLocal::get("amtdue"));

        return PrehLib::tender("CA", CoreLocal::get("runningtotal") * 100);
	}

	function doc()
    {
		return "<table cellspacing=0 cellpadding=3 border=1>
			<tr>
				<th>Input</th><th>Result</th>
			</tr>
			<tr>
				<td>ES</td>
				<td>Runs an end of shift, whatever
				that is. Wedge function I think.</td>
			</tr>
			</table>";
	}

}

