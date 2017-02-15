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

/**
  @class TenderFormat
  Module for print-formatting 
  tender records.
*/
class TenderReceiptFormat extends DefaultReceiptFormat 
{

    /**
      Formatting function
      @param $row a single receipt record
      @return a formatted string
    */
    public function format(array $row)
    {
        $tender_width = $this->line_width - 12;
        $ret = str_pad($row['description'], $tender_width,' ',STR_PAD_LEFT);
        $ret .= str_pad(sprintf('%.2f',-1*$row['total']),8,' ',STR_PAD_LEFT);

        return $ret;
    }
}

