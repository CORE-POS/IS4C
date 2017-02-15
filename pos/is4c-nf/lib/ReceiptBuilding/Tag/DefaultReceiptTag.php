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

namespace COREPOS\pos\lib\ReceiptBuilding\Tag;

/**
  @class DefaultReceiptTag
  Module for marking receipt records
  as certain types. Subclasses can
  override the tag() method
*/
class DefaultReceiptTag 
{

    /**
      Tagging function
      @param $rowset an array of records
      @return an array of records
    */
    public function tag(array $rowset)
    {
        for($i=0;$i<count($rowset);$i++) {
            switch($rowset[$i]['trans_type']) {
                case 'T':
                    if ($rowset[$i]['department'] == 0) {
                        $rowset[$i]['tag'] = 'Tender';
                    } else {
                        $rowset[$i]['tag'] = 'Item';
                    }
                    break;
                case 'I':
                case 'D':
                    $rowset[$i]['tag'] = 'Item';
                    break;
                case 'H':
                case '0':
                    $rowset[$i]['tag'] = 'Other';
                    break;
                default:
                    $rowset[$i]['tag'] = 'Total';
                    break;
            }
        }
        
        return $rowset;
    }
}

