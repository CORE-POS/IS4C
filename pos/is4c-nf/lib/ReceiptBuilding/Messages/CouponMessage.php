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
use COREPOS\pos\lib\PrintHandlers\PrintHandler;

class CouponMessage extends ReceiptMessage 
{
    /**
      This message has to be printed on paper
    */
    public $paper_only = true;

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
        $upc = '499999000182';
        $msg = 'Save $2 on your next purchase at the Denfeld store. Limit one per purchase.'
            . ' Expires ' . date('m/d/Y', strtotime('+10 days')); 

        return 
            str_repeat('.', 55) . "\n" .
            $this->printHandler->TextStyle(true,false,true) .
            $this->printHandler->centerString('SAVE $2.00') .
            $this->printHandler->TextStyle(true) .
            "\n\n" .
            $this->printHandler->BarcodeHeight(81) .
            $this->printHandler->LeftMargin(100) .
            $this->printHandler->printBarcode(PrintHandler::BARCODE_UPCA, $upc) . "\n" .
            $this->printHandler->LeftMargin(0) .
            wordwrap($msg, 55);
    }
}

