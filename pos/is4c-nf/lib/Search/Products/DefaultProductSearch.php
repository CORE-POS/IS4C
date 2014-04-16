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

/**
  @class DefaultProductSearch
  Look up products in the database
*/
class DefaultProductSearch extends ProductSearch 
{

	public function search($str)
    {
		$ret = array();
		$sql = Database::pDataConnect();
        $safestr = $sql->escape($str);
        $table = $sql->table_definition('products');
        $string_search = "(description LIKE '%$safestr%')";
        // new coluumns 16Apr14
        // search in products.brand and products.formatted_name
        // if those columns are available
        if (isset($table['brand']) && isset($table['formatted_name'])) {
            $string_search = "(
                                description LIKE '%$safestr%'
                                OR brand LIKE '%$safestr%'
                                OR formatted_name LIKE '%$safestr%'
                              )";
        }
		$query = "SELECT upc, 
                    description, 
                    normal_price, 
                    special_price,
        			advertised, 
                    scale 
                  FROM products 
                  WHERE $string_search
                    AND upc LIKE '0000000%'
                    AND inUse=1
			      ORDER BY description";
		$result = $sql->query($query);
		while($row = $sql->fetch_row($result)){
			$ret[$row['upc']] = $row;
		}

		return $ret;
	}
}

