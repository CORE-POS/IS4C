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
  @class ShiftsModel
*/
class ShiftsModel extends BasicModel
{

    protected $name = "shifts";

    protected $columns = array(
    'ShiftName' => array('type'=>'VARCHAR(25)'),
    'NiceName' => array('type'=>'VARCHAR(255)'),
    'ShiftID' => array('type'=>'INT', 'primary_key'=>true),
    'visible' => array('type'=>'TINYINT'),
    'ShiftOrder' => array('type'=>'INT'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function ShiftName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ShiftName"])) {
                return $this->instance["ShiftName"];
            } else if (isset($this->columns["ShiftName"]["default"])) {
                return $this->columns["ShiftName"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["ShiftName"]) || $this->instance["ShiftName"] != func_get_args(0)) {
                if (!isset($this->columns["ShiftName"]["ignore_updates"]) || $this->columns["ShiftName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ShiftName"] = func_get_arg(0);
        }
    }

    public function NiceName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["NiceName"])) {
                return $this->instance["NiceName"];
            } else if (isset($this->columns["NiceName"]["default"])) {
                return $this->columns["NiceName"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["NiceName"]) || $this->instance["NiceName"] != func_get_args(0)) {
                if (!isset($this->columns["NiceName"]["ignore_updates"]) || $this->columns["NiceName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["NiceName"] = func_get_arg(0);
        }
    }

    public function ShiftID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ShiftID"])) {
                return $this->instance["ShiftID"];
            } else if (isset($this->columns["ShiftID"]["default"])) {
                return $this->columns["ShiftID"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["ShiftID"]) || $this->instance["ShiftID"] != func_get_args(0)) {
                if (!isset($this->columns["ShiftID"]["ignore_updates"]) || $this->columns["ShiftID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ShiftID"] = func_get_arg(0);
        }
    }

    public function visible()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["visible"])) {
                return $this->instance["visible"];
            } else if (isset($this->columns["visible"]["default"])) {
                return $this->columns["visible"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["visible"]) || $this->instance["visible"] != func_get_args(0)) {
                if (!isset($this->columns["visible"]["ignore_updates"]) || $this->columns["visible"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["visible"] = func_get_arg(0);
        }
    }

    public function ShiftOrder()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ShiftOrder"])) {
                return $this->instance["ShiftOrder"];
            } else if (isset($this->columns["ShiftOrder"]["default"])) {
                return $this->columns["ShiftOrder"]["default"];
            } else {
                return null;
            }
        } else {
            if (!isset($this->instance["ShiftOrder"]) || $this->instance["ShiftOrder"] != func_get_args(0)) {
                if (!isset($this->columns["ShiftOrder"]["ignore_updates"]) || $this->columns["ShiftOrder"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ShiftOrder"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

