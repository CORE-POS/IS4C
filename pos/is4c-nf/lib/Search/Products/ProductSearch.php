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

namespace COREPOS\pos\lib\Search\Products;

/**
  @class ProductSearch
  Base class for providing product search results
*/
class ProductSearch {

    /**
      Set how big the result box is (in rows)
    */    
    public $result_size = 15;

    /**
      Do the actual search
      @param $str a search string
      @return array

      The return value is an array of product records.
      Each record should have the following keys:
        upc
        description
        normal_price
        scale
      The records can simply be query results or
      you can use some custom construction

      The outer array should be of the form
      upc => record so returns from multiple searches
      can be merged together easily.
    */
    public function search($str){
        return array();
    }
}

