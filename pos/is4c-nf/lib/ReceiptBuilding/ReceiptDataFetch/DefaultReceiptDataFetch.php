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

/**
  @class DefaultReceiptDataFetch
  Module for fetching records from
  the database for a receipt
*/

class DefaultReceiptDataFetch {
	
	/**
	  Implementation function
	  @return SQL result object
	*/
	function fetch($empNo=False,$laneNo=False,$transNo=False){
		$query = 'SELECT l.upc,l.trans_type,l.description,
			l.total,l.percentDiscount,l.trans_status,
			l.charflag,l.scale,l.quantity,l.unitPrice,
			l.ItemQtty,l.matched,l.numflag,l.tax,
			l.foodstamp,l.trans_id,
			s.subdept_name AS category 
			FROM localtemptrans AS l LEFT JOIN
			subdepts AS s ON l.department=s.dept_ID
			WHERE trans_type <> \'L\'
			ORDER BY trans_id DESC';
		if ($empNo && $laneNo && $transNo){
			$query = sprintf("SELECT l.upc,l.trans_type,l.description,
				l.total,l.percentDiscount,l.trans_status,
				l.charflag,l.scale,l.quantity,l.unitPrice,
				l.ItemQtty,l.matched,l.numflag,l.tax,
				l.foodstamp,l.trans_id,
				s.subdept_name AS category 
				FROM localtranstoday as l LEFT JOIN subdepts AS s
				ON l.department=s.dept_ID
				WHERE trans_type <> 'L' AND
				emp_no=%d AND register_no=%d AND trans_no=%d
				ORDER BY trans_id DESC",$empNo,$laneNo,$transNo);
		}
		$sql = Database::tDataConnect();
		$result = $sql->query($query);
		return $result;
	}

}
