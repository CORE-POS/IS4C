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
  @class WfcHtCuspingModel
*/
class WfcHtCuspingModel extends BasicModel
{

    protected $name = "cusping";
    protected $preferred_db = 'WfcHtDatabase';

    protected $columns = array(
    'empID' => array('type'=>'INT'),
    'cusp' => array('type'=>'VARCHAR(4)'),
    );

    public function create()
    {

        $query = "CREATE VIEW cusping AS 
            SELECT e.empID AS empID,
            (case 
                when e.empID IS NULL then NULL 
                when (((h.totalHours - 100) < p.HoursWorked) and (e.PTOLevel > 0)) then 'POST' 
                when (h.totalHours >= q.HoursWorked) then '!!!' 
                else 'PRE' 
            end) AS cusp 
            FROM hoursalltime h 
            LEFT JOIN employees e on h.empID = e.empID
            LEFT JOIN PTOLevels p on e.PTOLevel = p.LevelID
            LEFT JOIN PTOLevels q on e.PTOLevel + 1 = q.LevelID 
            WHERE 
                (h.totalHours >= p.HoursWorked and h.totalHours - 100 < p.HoursWorked and e.PTOLevel > 0) 
                OR 
                (h.totalHours <= q.HoursWorked and h.totalHours + 200 > q.HoursWorked) 
                OR 
                (h.totalHours >= q.HoursWorked)";
        $try = $this->connection->query($query);

        if ($try) {
            return true;
        } else {
            return false;
        }
    }
}

