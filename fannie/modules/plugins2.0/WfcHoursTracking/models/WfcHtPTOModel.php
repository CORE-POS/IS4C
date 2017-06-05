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
  @class WfcHtPTOModel
*/
class WfcHtPTOModel extends BasicModel
{

    protected $name = "pto";
    protected $preferred_db = 'WfcHtDatabase';

    protected $columns = array(
    'empID' => array('type'=>'INT'),
    'PTORemaining' => array('type'=>'DOUBLE'),
    'totalPTO' => array('type'=>'DOUBLE'),
    );

    public function create()
    {
        $query = "CREATE VIEW pto AS 
            select i.empID AS empID,
            ROUND(max(p.PTOHours) - sum(case when i.periodID > e.PTOCutoff then i.PTOHours else 0 end), 2) AS PTORemaining,
            max(p.PTOHours) AS totalPTO 
            from ImportedHoursData i 
            LEFT JOIN employees e on i.empID = e.empID 
            LEFT JOIN PTOLevels p on p.LevelID = e.PTOLevel
            group by i.empID";
        $try = $this->connection->query($query);

        if ($try) {
            return true;
        } else {
            return false;
        }
    }
}

