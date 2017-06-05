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
  @class WfcHtUTOModel
*/
class WfcHtUTOModel extends BasicModel
{

    protected $name = "uto";
    protected $preferred_db = 'WfcHtDatabase';

    protected $columns = array(
    'empID' => array('type'=>'INT'),
    'hours' => array('type'=>'DOUBLE'),
    );

    public function create()
    {
        $query = "CREATE VIEW uto AS 
            select e.empID AS empID,
            round(40 
                - case when year(curdate()) <> s.year then 0 else (40.0 / 12.0) * (s.month - 1) end 
                - sum(case when i.UTOHours IS NULL or i.year <> year(curdate()) then 0 else i.UTOHours end)
            ), 2) AS hours 
            from employees e 
            left join ImportedHoursData i on e.empID = i.empID 
            left join startyearmonth s on e.empID = s.empID 
            group by e.empID,s.year,s.month";
        $try = $this->connection->query($query);

        if ($try) {
            return true;
        } else {
            return false;
        }
    }
}

