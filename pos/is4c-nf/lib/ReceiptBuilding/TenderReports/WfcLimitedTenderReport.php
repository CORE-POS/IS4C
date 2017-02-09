<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

namespace COREPOS\pos\lib\ReceiptBuilding\TenderReports;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;

/**
  @class WfcLimitedTenderReport
  Generate an extremely simplified tender report
  for the current lane only
*/
class WfcLimitedTenderReport extends TenderReport 
{
    static public function get($session)
    {
        $blank = self::standardBlank();
        $fieldNames = self::standardFieldNames();
        $time = ReceiptLib::build_time(time()); 

        $receipt = ReceiptLib::biggerFont(ReceiptLib::centerBig('R E G I S T E R  ' . $session->get('laneno')))."\n\n";
        $receipt .= ReceiptLib::biggerFont(ReceiptLib::centerBig($time)) . "\n\n";
        $receipt .=    ReceiptLib::centerString("------------------------------------------------------") . "\n";
        $dba = Database::mDataConnect();
        $receipt .= ReceiptLib::centerString('T E L E C H E C K') . "\n";
        $receipt .=    ReceiptLib::centerString("------------------------------------------------------") . "\n";
        $receipt .= $fieldNames;

        $query = $dba->prepare("select tdate,register_no,trans_no,-total AS tender
                   from dlog where register_no=?
                   and trans_type='T' AND trans_subtype='TK'
                  ORDER BY tdate");
        $res = $dba->execute($query, array($session->get('laneno')));
        $numRows = $dba->numRows($res);

        $sum = 0;
        while ($row = $dba->fetchRow($res)) {
            $receipt .= self::standardLine($row['tdate'], $row['register_no'], $row['trans_no'], $row['tender']);
            $sum += $row["tender"];
        }

        $receipt .= substr($blank.$blank.$blank."Count: ".$numRows."  Total: ".number_format($sum,2), -56)."\n";
        $receipt .= str_repeat("\n", 4);
        $receipt .= chr(27).chr(105);

        return $receipt;
    }
}

