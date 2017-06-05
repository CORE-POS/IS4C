<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of CORE-POS.

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
  @class WfcHtStartYearMonthModel
*/
class WfcHtStartYearMonthModel extends BasicModel
{

    protected $name = "startyearmonth";
    protected $preferred_db = 'WfcHtDatabase';

    protected $columns = array(
    'empID' => array('type'=>'INT'),
    'year' => array('type'=>'INT'),
    'month' => array('type'=>'INT'),
    );

    public function create()
    {
        $query = "CREATE VIEW startyearmonth AS 
            select f.empID AS empID,
            p.year AS year,
            case 
                when p.dateStr = 'pre-2008' then 5 
                when locate('/',p.dateStr) = 0 then 1 
                else left(p.dateStr, locate('/',p.dateStr) - 1) 
            end) AS month,
            p.dateStr AS dateStr 
            from firstpayperiod f 
            left join PayPeriods p on f.periodID = p.periodID";
        $try = $this->connection->query($query);

        if ($try) {
            return true;
        } else {
            return false;
        }
    }
}

