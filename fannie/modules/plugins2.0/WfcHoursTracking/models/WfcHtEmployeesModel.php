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
  @class WfcHtEmployeesModel
*/
class WfcHtEmployeesModel extends BasicModel
{

    protected $name = "employees";

    protected $columns = array(
    'empID' => array('type'=>'INT', 'primary_key'=>true),
    'name' => array('type'=>'VARCHAR(255)'),
    'adpID' => array('type'=>'INT'),
    'PTOLevel' => array('type'=>'INT'),
    'PTOCutoff' => array('type'=>'INT'),
    'department' => array('type'=>'INT'),
    'deleted' => array('type'=>'TINYINT'),
	);

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

    public function name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["name"])) {
                return $this->instance["name"];
            } elseif(isset($this->columns["name"]["default"])) {
                return $this->columns["name"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["name"] = func_get_arg(0);
        }
    }

    public function adpID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["adpID"])) {
                return $this->instance["adpID"];
            } elseif(isset($this->columns["adpID"]["default"])) {
                return $this->columns["adpID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["adpID"] = func_get_arg(0);
        }
    }

    public function PTOLevel()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PTOLevel"])) {
                return $this->instance["PTOLevel"];
            } elseif(isset($this->columns["PTOLevel"]["default"])) {
                return $this->columns["PTOLevel"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["PTOLevel"] = func_get_arg(0);
        }
    }

    public function PTOCutoff()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PTOCutoff"])) {
                return $this->instance["PTOCutoff"];
            } elseif(isset($this->columns["PTOCutoff"]["default"])) {
                return $this->columns["PTOCutoff"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["PTOCutoff"] = func_get_arg(0);
        }
    }

    public function department()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["department"])) {
                return $this->instance["department"];
            } elseif(isset($this->columns["department"]["default"])) {
                return $this->columns["department"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["department"] = func_get_arg(0);
        }
    }

    public function deleted()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["deleted"])) {
                return $this->instance["deleted"];
            } elseif(isset($this->columns["deleted"]["default"])) {
                return $this->columns["deleted"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["deleted"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

