<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

use COREPOS\pos\lib\MemberLookup;
use COREPOS\pos\lib\Database;

/**
  @class ByPhoneMemberLookup
  
  Search for member using phone number

  Looks up phone + member number via meminfo table
*/

class ByPhoneMemberLookup extends MemberLookup {

    public function handle_text(){
        return False;
    }

    public function lookup_by_number($num){
        $db = Database::pDataConnect();
        $ret = $this->default_value();

        // need table for lookup
        if (!$db->table_exists('meminfo'))
            return $ret;

        $query = 'SELECT CardNo,personNum,
            LastName,FirstName,phone
            FROM custdata AS c LEFT JOIN
            meminfo AS m ON c.CardNo=m.card_no
            WHERE m.phone='.((int)$num);
        $result = $db->query($query);
        while($row = $db->fetch_row($result)){
            $key = $row['CardNo'].'::'.$row['personNum'];
            $label = $row['LastName'].', '.$row['FirstName']
                .' ('.$row['phone'].')';
            $ret['results'][$key] = $label;
        }
        return $ret;
    }
}

