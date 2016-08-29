<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op.

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

namespace COREPOS\pos\lib\ReceiptBuilding\CustMessages;

/**
  @class CustomerReceiptMessage

  Class for handling per-customer messages
  defined in the opdata.custReceiptMessage
  table.
*/
class CustomerReceiptMessage 
{
    protected $print_handler;

    public function setPrintHandler($ph)
    {
        $this->print_handler = $ph;
    }

    /**
      Create or modify the message
      @param $str the contents of custReceiptMessage.msg_text
      @return [string] message to print on receipt

      This method provides an opportunity to alter the 
      message specified in the database.

      It's not necessary to modify the message at all or even
      to provide a modifier_module in custReceiptMessage.
    */
    public function message($str)
    {
        return $str;
    }
}

