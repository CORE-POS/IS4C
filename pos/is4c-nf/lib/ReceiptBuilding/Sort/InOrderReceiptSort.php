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

namespace COREPOS\pos\lib\ReceiptBuilding\Sort;

/**
  @class InOrderReceiptSort
  Does nothing. Leave items in the order they
  were entered.
*/
class InOrderReceiptSort extends DefaultReceiptSort 
{

    /**
      Sorting function
      @param $rowset an array of records
      @return an array of records
    */
    public function sort(array $rowset)
    {
        $nontenders = array();
        $tenders = array();
        foreach($rowset as $row) {
            if ($row['trans_type'] == 'T' && $row['department'] == 0) {
                $tenders[] = $row;
            } else {
                $nontenders[] = $row;
            }
        }

        $returnset = array();
        foreach($nontenders as $row) {
            $returnset[] = $row;
        }
        foreach($tenders as $row) {
            $returnset[] = $row;
        }

        return $returnset;
    }

}    

