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
  @class GenericSigSlipMessage
  Attach a generic signature slip to a receipt.
  Can be subclassed with a different select_condition()
  to attach to various store-specific tender codes.
*/
class GenericSigSlipMessage extends ReceiptMessage
{
    public $paper_only = true;

    public function select_condition()
    {
        return "SUM(CASE WHEN trans_type='T' AND trans_subtype='ST' THEN total ELSE 0 END)";
    }

    public function message($val, $ref, $reprint=false)
    {
        if ($val == 0) return '';
        
        $slip = '';
        // reprints always include. if the original transaction
        // has an automatic reprtint, only include one slip
        if ($reprint || CoreLocal::get('autoReprint') == 0) {

            $slip .= ReceiptLib::centerString("................................................")."\n\n";
            $slip .= ReceiptLib::centerString("( S T O R E   C O P Y )")."\n";
            $slip .= ReceiptLib::biggerFont(sprintf("Amount \$%.2f",$val))."\n\n";

            if ( CoreLocal::get("fname") != "" && CoreLocal::get("lname") != ""){
                $slip .= "Name: ".CoreLocal::get("fname")." ".CoreLocal::get("lname")."\n\n";
            } else {
                $slip .= "Name: ____________________________________________\n\n";
            }
            $slip .= "X: ____________________________________________\n";

            $slip .= ReceiptLib::centerString('(please sign)')."\n\n";
            $slip .= ReceiptLib::centerString("................................................")."\n";
        }

        return $slip;
    }

}

