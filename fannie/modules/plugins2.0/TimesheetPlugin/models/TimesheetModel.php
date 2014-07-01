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
  @class TimesheetModel
*/
class TimesheetModel extends BasicModel
{

    protected $name = "timesheet";

    protected $columns = array(
    'emp_no' => array('type'=>'INT', 'index'=>true),
    'hours' => array('type'=>'DOUBLE'),
    'area' => array('type'=>'INT'),
    'tdate' => array('type'=>'DATETIME'),
    'periodID' => array('type'=>'INT', 'index'=>true),
    'ID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'VACATION' => array('type'=>'DOUBLE'),
    'tstamp' => array('type'=>'TIMESTAMP'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function emp_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emp_no"])) {
                return $this->instance["emp_no"];
            } else if (isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["emp_no"]) || $this->instance["emp_no"] != func_get_args(0)) {
                if (!isset($this->columns["emp_no"]["ignore_updates"]) || $this->columns["emp_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["emp_no"] = func_get_arg(0);
        }
    }

    public function hours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hours"])) {
                return $this->instance["hours"];
            } else if (isset($this->columns["hours"]["default"])) {
                return $this->columns["hours"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["hours"]) || $this->instance["hours"] != func_get_args(0)) {
                if (!isset($this->columns["hours"]["ignore_updates"]) || $this->columns["hours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hours"] = func_get_arg(0);
        }
    }

    public function area()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["area"])) {
                return $this->instance["area"];
            } else if (isset($this->columns["area"]["default"])) {
                return $this->columns["area"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["area"]) || $this->instance["area"] != func_get_args(0)) {
                if (!isset($this->columns["area"]["ignore_updates"]) || $this->columns["area"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["area"] = func_get_arg(0);
        }
    }

    public function tdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tdate"])) {
                return $this->instance["tdate"];
            } else if (isset($this->columns["tdate"]["default"])) {
                return $this->columns["tdate"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["tdate"]) || $this->instance["tdate"] != func_get_args(0)) {
                if (!isset($this->columns["tdate"]["ignore_updates"]) || $this->columns["tdate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tdate"] = func_get_arg(0);
        }
    }

    public function periodID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["periodID"])) {
                return $this->instance["periodID"];
            } else if (isset($this->columns["periodID"]["default"])) {
                return $this->columns["periodID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["periodID"]) || $this->instance["periodID"] != func_get_args(0)) {
                if (!isset($this->columns["periodID"]["ignore_updates"]) || $this->columns["periodID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["periodID"] = func_get_arg(0);
        }
    }

    public function ID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ID"])) {
                return $this->instance["ID"];
            } else if (isset($this->columns["ID"]["default"])) {
                return $this->columns["ID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["ID"]) || $this->instance["ID"] != func_get_args(0)) {
                if (!isset($this->columns["ID"]["ignore_updates"]) || $this->columns["ID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ID"] = func_get_arg(0);
        }
    }

    public function VACATION()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["VACATION"])) {
                return $this->instance["VACATION"];
            } else if (isset($this->columns["VACATION"]["default"])) {
                return $this->columns["VACATION"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["VACATION"]) || $this->instance["VACATION"] != func_get_args(0)) {
                if (!isset($this->columns["VACATION"]["ignore_updates"]) || $this->columns["VACATION"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["VACATION"] = func_get_arg(0);
        }
    }

    public function tstamp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tstamp"])) {
                return $this->instance["tstamp"];
            } else if (isset($this->columns["tstamp"]["default"])) {
                return $this->columns["tstamp"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["tstamp"]) || $this->instance["tstamp"] != func_get_args(0)) {
                if (!isset($this->columns["tstamp"]["ignore_updates"]) || $this->columns["tstamp"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tstamp"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

