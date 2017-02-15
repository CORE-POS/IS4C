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

use COREPOS\pos\lib\Database;

class LookupByCard 
{

    /**
      This module handle numeric inputs
      @return boolean default True
    */
    public function handle_numbers(){
        return True;
    }

    /**
      This module handle text inputs
      @return boolean default True
    */
    public function handle_text(){
        return False;
    }

    /**
      Find member by membercards.upc
      @param $num the upc
      @return array. See default_value().
    */
    public function lookup_by_number($num){
        $dbc = Database::pDataConnect();
        $upc = str_pad($num,13,'0',STR_PAD_LEFT);
        $query = $dbc->prepare('SELECT CardNo, personNum,
            LastName, FirstName FROM custdata
            AS c LEFT JOIN memberCards AS m
            ON c.CardNo=m.card_no
            WHERE m.upc=?
            AND Type IN (\'PC\',\'REG\')
            ORDER BY personNum');
        $result = $dbc->execute($query, array($upc));

        return $this->listToArray($dbc, $result);
    }
}

