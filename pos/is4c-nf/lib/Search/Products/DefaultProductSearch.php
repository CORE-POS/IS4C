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
use \CoreLocal;
use COREPOS\pos\lib\Database;

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
        $args = array('%' . $str . '%');
        $string_search = "(description LIKE ?)";
        // new coluumns 16Apr14
        // search in products.brand and products.formatted_name
        // if those columns are available
        if (CoreLocal::get('NoCompat') == 1) {
            $string_search = "(
                                description LIKE ?
                                OR brand LIKE ?
                                OR formatted_name LIKE ?
                              )";
            $args = array(
                '%' . $str . '%',
                '%' . $str . '%',
                '%' . $str . '%',
            );
        } else {
            $table = $sql->tableDefinition('products');
            if (isset($table['brand']) && isset($table['formatted_name'])) {
                $string_search = "(
                                    description LIKE ?
                                    OR brand LIKE ?
                                    OR formatted_name LIKE ?
                                  )";
                $args = array(
                    '%' . $str . '%',
                    '%' . $str . '%',
                    '%' . $str . '%',
                );
            }
        }
        $query = "SELECT upc, 
                    description, 
                    normal_price, 
                    special_price,
                    scale 
                  FROM products 
                  WHERE $string_search
                    AND upc LIKE '0000000%'
                    AND inUse=1
                  ORDER BY description";
        $prep = $sql->prepare($query);
        $result = $sql->execute($prep, $args);
        while ($row = $sql->fetch_row($result)) {
            $ret[$row['upc']] = $row;
        }

        return $ret;
    }
}

