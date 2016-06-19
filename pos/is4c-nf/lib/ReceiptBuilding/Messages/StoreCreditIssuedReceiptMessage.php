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
  @class StoreCreditIssuedReceiptMessage
*/
class StoreCreditIssuedReceiptMessage extends ReceiptMessage{

    public function select_condition(){
        return "SUM(CASE WHEN trans_type='T' AND trans_subtype='SC' THEN total ELSE 0 END)";
    }

    public function message($val, $ref, $reprint=False)
    {
        if ($val <= 0) return '';
        
        $slip = '';
        if ($reprint || CoreLocal::get('autoReprint') == 0) {

            $slip .= ReceiptLib::centerString("................................................")."\n\n";
            $slip .= ReceiptLib::centerString("( C U S T O M E R   C O P Y )")."\n";
            $slip .= ReceiptLib::biggerFont("Store credit issued")."\n\n";
            $slip .= ReceiptLib::biggerFont(sprintf("Amount \$%.2f",$val))."\n\n";

            if ( CoreLocal::get("fname") != "" && CoreLocal::get("lname") != ""){
                $slip .= "Name: ".CoreLocal::get("fname")." ".CoreLocal::get("lname")."\n\n";
            } else {
                $slip .= "Name: ____________________________________________\n\n";
            }
            $slip .= "Ph #: ____________________________________________\n\n";

            $slip .= " * no cash back on store credit refunds\n";
            $slip .= " * change amount is not transferable to\n   another store credit\n";
            $slip .= ReceiptLib::centerString("................................................")."\n";
        }

        return $slip;
    }

    public $paper_only = True;
}

