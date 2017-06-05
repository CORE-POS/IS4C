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
  @class WfcHtHoursYTDModel
*/
class WfcHtHoursYTDModel extends BasicModel
{

    protected $name = "hoursytd";
    protected $preferred_db = 'WfcHtDatabase';

    protected $columns = array(
    'empID' => array('type'=>'INT'),
    'regularHours' => array('type'=>'DOUBLE'),
    'overtimeHours' => array('type'=>'DOUBLE'),
    'emergencyHours' => array('type'=>'DOUBLE'),
    'rateHours' => array('type'=>'DOUBLE'),
    'totalHours' => array('type'=>'DOUBLE'),
    );

    public function create()
    {
        $query = "CREATE VIEW hoursytd AS 
            select empID AS empID,
            sum(hours) AS regularHours,
            sum(OTHours) AS overtimeHours,
            sum(EmergencyHours) AS emergencyHours,
            sum(SecondRateHours) AS rateHours,
            sum(hours) + sum(OTHours) + sum(SecondRateHours) + sum(EmergencyHours) AS totalHours 
            from ImportedHoursData where year = year(curdate())) group by empID";
        $try = $this->connection->query($query);

        if ($try) {
            return true;
        } else {
            return false;
        }
    }
}

