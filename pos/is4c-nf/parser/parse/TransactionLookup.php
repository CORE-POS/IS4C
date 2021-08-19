<?php
/*******************************************************************************

    Copyright 2021 Whole Foods Co-op

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

namespace COREPOS\pos\parser\parse;
use COREPOS\pos\parser\Parser;
use COREPOS\pos\lib\MiscLib;

class TransactionLookup extends Parser
{
    function check($str)
    {
        if (substr($str, 0, 2) == 'CP' && is_numeric(substr($str, 2))) {
            return true;
        }
        return false;
    }

    function parse($str)
    {
        $ret = $this->default_json();

        $pos = 2;
        $year = '20' . substr($str, $pos, 2);
        $pos += 2;
        $month = substr($str, $pos, 2);
        $pos += 2;
        $day = substr($str, $pos, 2);
        $pos += 2;
        $emp = substr($str, $pos, 5);
        $pos += 5;
        $reg = substr($str, $pos, 3);
        $pos += 3;
        $trn = substr($str, $pos, 4);
        $date = $year . '-' . $month . '-' . $day;
        $trans_num = ltrim($emp, '0') . '-' . ltrim($reg, '0') . '-' . ltrim($trn, '0');
        $ret['main_frame'] = MiscLib::base_url().'gui-modules/RefundTransaction.php?date=' . $date . '&tn=' . $trans_num;

        return $ret;
    }

    function doc(){
        return "<table cellspacing=0 cellpadding=3 border=1>
            <tr>
                <th>Input</th><th>Result</th>
            </tr>
            <tr>
                <td>CP(number)</td>
                <td>Lookup transaction from barcode
                </td>
            </tr>
            </table>";
    }
}

