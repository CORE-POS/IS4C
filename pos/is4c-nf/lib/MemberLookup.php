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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\Database;

/**
  @class MemberLookup
*/

class MemberLookup {

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
        return True;
    }

    /**
      Get return-value formatted array
      @return an array with keys:
       - url => redirect to URL, default False
       - results => matching member records

      The results array is used to populate the
      member <select> box. The key for each record
      should be CardNo::personNum. The value is what's
      displayed for the cashier and can be whatever
      you like.
    */
    protected function default_value(){
        return array(
            'url' => False,
            'results' => array()
        );
    }

    /**
      Find member by custdata.CardNo
      @param $num the member number
      @return array. See default_value().
    */
    public function lookup_by_number($num){
        $dbc = Database::pDataConnect();
        $query = $dbc->prepare('SELECT CardNo, personNum,
            LastName, FirstName FROM custdata
            WHERE CardNo=? 
            AND Type IN (\'PC\',\'REG\')
            ORDER BY personNum');
        $result = $dbc->execute($query, array($num));

        return $this->listToArray($dbc, $result);
    }

    /**
      Find member by last name.
      @param $num the search string.
      @return array. See default_value().
    */
    public function lookup_by_text($text){
        $dbc = Database::pDataConnect();
        $query = $dbc->prepare('SELECT CardNo, personNum,
            LastName, FirstName FROM custdata
            WHERE LastName LIKE ? 
            AND Type IN (\'PC\',\'REG\')
            ORDER BY LastName, FirstName');
        $result = $dbc->execute($query, array($text.'%'));    

        return $this->listToArray($dbc, $result);
    }

    protected function listToArray($dbc, $result)
    {
        $ret = $this->default_value();
        while ($row = $dbc->fetch_row($result)) {
            $key = $row['CardNo'].'::'.$row['personNum'];
            $val = $row['CardNo'].' '.$row['LastName'].', '.$row['FirstName'];
            $ret['results'][$key] = $val;
        }
        return $ret;
    }

}
