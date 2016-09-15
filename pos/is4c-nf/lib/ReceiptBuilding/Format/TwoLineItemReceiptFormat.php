<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op.

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

namespace COREPOS\pos\lib\ReceiptBuilding\Format;
use \CoreLocal;

/**
  @class TwoLineItemFormat
  Puts descriptin on its own line and the
  related price info on the next line
*/
class TwoLineItemReceiptFormat extends ItemReceiptFormat
{
    /**
      Pad fields into a standard width and alignment
    */
    protected function align($description, $comment, $amount, $flags="")
    {
        $amount = sprintf('%.2f',$amount);
        if ($amount=="0.00") $amount="";

        $ret = $description . "\n";
        $comment_width = $this->line_width - 8 - 4;
        $ret .= str_pad($comment, $comment_width,' ',STR_PAD_RIGHT);
        $ret .= str_pad($amount,8,' ',STR_PAD_LEFT);
        $ret .= str_pad($flags,4,' ',STR_PAD_LEFT);
        
        return $ret;
    }
}

