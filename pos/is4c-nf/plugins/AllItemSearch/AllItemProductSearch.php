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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\Search\Products\ProductSearch;

class AllItemProductSearch extends ProductSearch {

    public function search($str){
        $ret = array();
        $sql = Database::pDataConnect();
        $query = "select upc, description, normal_price, special_price, "
            ."scale from products where "
            ."description like '%".$str."%' "
            ."and inUse='1' "
            ."order by description";
        $result = $sql->query($query);
        while($row = $sql->fetch_row($result)){
            $ret[$row['upc']] = $row;
        }
        return $ret;
    }
}

