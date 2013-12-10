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
  @class WfcHtWeeklyHoursModel
*/
class WfcHtWeeklyHoursModel extends BasicModel
{

    protected $name = "weeklyHours";

    protected $columns = array(
    'weekStart' => array('type'=>'DATETIME'),
    'weekEnd' => array('type'=>'DATETIME'),
    'empID' => array('type'=>'INT', 'index'=>true),
    'hours' => array('type'=>'DOUBLE'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function weekStart()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["weekStart"])) {
                return $this->instance["weekStart"];
            } elseif(isset($this->columns["weekStart"]["default"])) {
                return $this->columns["weekStart"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["weekStart"] = func_get_arg(0);
        }
    }

    public function weekEnd()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["weekEnd"])) {
                return $this->instance["weekEnd"];
            } elseif(isset($this->columns["weekEnd"]["default"])) {
                return $this->columns["weekEnd"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["weekEnd"] = func_get_arg(0);
        }
    }

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

    public function hours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hours"])) {
                return $this->instance["hours"];
            } elseif(isset($this->columns["hours"]["default"])) {
                return $this->columns["hours"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["hours"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

