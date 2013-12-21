<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

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
  @class WfcHtSalaryPTOYTDModel
*/
class WfcHtSalaryPTOYTDModel extends BasicModel
{

    protected $name = "salarypto_ytd";

    protected $columns = array(
    'empID' => array('type'=>'INT'),
    'totalTaken' => array('type'=>'INT'),
	);

    public function create()
    {
        $query = "CREATE VIEW salarypto_ytd AS 
            select empID AS empID,
            sum(case when year(dstamp) = year(curdate()) then daysUsed else 0 end) AS totalTaken 
            from salaryHours group by empID";
        $try = $this->connection->query($query);

        if ($try) {
            return true;
        } else {
            return false;
        }
    }

    /* START ACCESSOR FUNCTIONS */

    public function empID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empID"])) {
                return $this->instance["empID"];
            } elseif(isset($this->columns["empID"]["default"])) {
                return $this->columns["empID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["empID"] = func_get_arg(0);
        }
    }

    public function totalTaken()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalTaken"])) {
                return $this->instance["totalTaken"];
            } elseif(isset($this->columns["totalTaken"]["default"])) {
                return $this->columns["totalTaken"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["totalTaken"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

