<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class ObfWeeksModel
*/
class ObfWeeksModel extends BasicModel
{

    protected $name = "ObfWeeks";

    protected $columns = array(
    'obfWeekID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'startDate' => array('type'=>'DATETIME'),
    'endDate' => array('type'=>'DATETIME'),
    'previousYear' => array('type'=>'DATETIME'),
    'growthTarget' => array('type'=>'DOUBLE'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function obfWeekID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["obfWeekID"])) {
                return $this->instance["obfWeekID"];
            } else if (isset($this->columns["obfWeekID"]["default"])) {
                return $this->columns["obfWeekID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["obfWeekID"]) || $this->instance["obfWeekID"] != func_get_args(0)) {
                if (!isset($this->columns["obfWeekID"]["ignore_updates"]) || $this->columns["obfWeekID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["obfWeekID"] = func_get_arg(0);
        }
    }

    public function startDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startDate"])) {
                return $this->instance["startDate"];
            } else if (isset($this->columns["startDate"]["default"])) {
                return $this->columns["startDate"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["startDate"]) || $this->instance["startDate"] != func_get_args(0)) {
                if (!isset($this->columns["startDate"]["ignore_updates"]) || $this->columns["startDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["startDate"] = func_get_arg(0);
        }
    }

    public function endDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["endDate"])) {
                return $this->instance["endDate"];
            } else if (isset($this->columns["endDate"]["default"])) {
                return $this->columns["endDate"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["endDate"]) || $this->instance["endDate"] != func_get_args(0)) {
                if (!isset($this->columns["endDate"]["ignore_updates"]) || $this->columns["endDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["endDate"] = func_get_arg(0);
        }
    }

    public function previousYear()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["previousYear"])) {
                return $this->instance["previousYear"];
            } else if (isset($this->columns["previousYear"]["default"])) {
                return $this->columns["previousYear"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["previousYear"]) || $this->instance["previousYear"] != func_get_args(0)) {
                if (!isset($this->columns["previousYear"]["ignore_updates"]) || $this->columns["previousYear"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["previousYear"] = func_get_arg(0);
        }
    }

    public function growthTarget()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["growthTarget"])) {
                return $this->instance["growthTarget"];
            } else if (isset($this->columns["growthTarget"]["default"])) {
                return $this->columns["growthTarget"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["growthTarget"]) || $this->instance["growthTarget"] != func_get_args(0)) {
                if (!isset($this->columns["growthTarget"]["ignore_updates"]) || $this->columns["growthTarget"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["growthTarget"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

