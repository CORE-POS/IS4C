<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\lib\ReceiptBuilding\Messages;
use COREPOS\pos\lib\ReceiptLib;
use \CoreLocal;

/**
  @class EquitySoldReceiptMessage
*/
class EquitySoldReceiptMessage extends ReceiptMessage {

    public function select_condition(){
        return "SUM(CASE WHEN department=991 THEN total ELSE 0 END)";
    }

    public function message($val, $ref, $reprint=False)
    {
        if ($val <= 0) return '';

        $slip = ReceiptLib::centerString("................................................")."\n\n";
        $slip .= ReceiptLib::biggerFont("Class B Equity Purchase")."\n\n";
        $slip .= ReceiptLib::biggerFont(sprintf('Amount: $%.2f',$val))."\n";
        $slip .= "\n";
        $slip .= "Proof of purchase for owner equity\n";
        $slip .= "Please retain receipt for your records\n\n";
        $slip .= ReceiptLib::centerString("................................................")."\n\n";

        CoreLocal::set("equityNoticeAmt",$val);

        return $slip;
    }
}

