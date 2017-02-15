<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op.

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

namespace COREPOS\pos\lib\ReceiptBuilding\ThankYou;
use \CoreLocal;

/**
  @class DefaultReceiptThanks
  Prints thank you line(s)
*/
class DefaultReceiptThanks 
{
    protected $printHandler;

    public function setPrintHandler($phObj)
    {
        $this->printHandler = $phObj;
    }

    /**
      Generate a message for a given receipt
      @param $trans_num [string] transaction identifier
      @return [string] receipt line(s)
    */
    public function message($trans_num)
    {
        $thanks = _('thank you');
        if (trim(CoreLocal::get("memberID")) != CoreLocal::get("defaultNonMem")) {
            $thanks .= _(' - owner ') . trim(CoreLocal::get('memberID'));
        }
        $ret  = $this->printHandler->TextStyle(true,false,true);
        $ret .= $this->printHandler->centerString($thanks);
        $ret .= $this->printHandler->TextStyle(true);
        $ret .= "\n\n";

        return $ret;
    }
}

