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
  @class ProductUserSearch
  Use the productUser table to
  supplement searches
*/
class ProductUserSearch extends ProductSearch {

	public function search($str){
		$ret = array();
		$sql = Database::pDataConnect();
		if (!$sql->table_exists('productUser')) return array();
		$query = "SELECT p.upc,
			   CASE WHEN u.description IS NULL THEN p.description
				   ELSE u.description END as description,
				p.normal_price, p.special_price, p.advertised, p.scale
			   FROM products AS p
				LEFT JOIN productUser AS u ON p.upc=u.upc
			 WHERE (p.description LIKE '%$str%' OR
				 u.description LIKE '%$str%')
			 AND p.upc LIKE ('0000000%')
			 AND p.inUse='1'
			 ORDER BY description";
		$result = $sql->query($query);
		while($row = $sql->fetch_row($result)){
			$ret[$row['upc']] = $row;
		}
		return $ret;
	}
}

?>
