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

/**
  @class ReceiptMessage
*/
class BarcodeTransIdentifierMessage extends ReceiptMessage 
{

    public function select_condition()
    {
        return '1';
    }

    /**
      Generate the message
      @param $val the value returned by the object's select_condition()
      @param $ref a transaction reference (emp-lane-trans)
      @param $reprint boolean
      @return [string] message to print on receipt
    */
    public function message($val, $ref, $reprint=false)
    {
        list($emp, $reg, $trans) = ReceiptLib::parseRef($ref);

        // full identier:
        // YYYY-MM-DD emp_no-register_no-trans_no
        $identifier = date('Y-m-d') . ' '
                . $emp . '-'
                . $reg . '-'
                . $trans;
        

        return "\n" . ReceiptLib::code39($identifier) . "\n";
    }

    /**
      This message has to be printed on paper
    */
    public $paper_only = true;

}

