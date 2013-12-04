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
  @class WfcHtPTOLevelsModel
*/
class WfcHtPTOLevelsModel extends BasicModel
{

    protected $name = "PTOLevels";

    protected $columns = array(
    'LevelID' => array('type'=>'SMALLINT', 'primary_key'=>true),
    'HoursWorked' => array('type'=>'DOUBLE'),
    'PTOHours' => array('type'=>'DOUBLE'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function LevelID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["LevelID"])) {
                return $this->instance["LevelID"];
            } elseif(isset($this->columns["LevelID"]["default"])) {
                return $this->columns["LevelID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["LevelID"] = func_get_arg(0);
        }
    }

    public function HoursWorked()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["HoursWorked"])) {
                return $this->instance["HoursWorked"];
            } elseif(isset($this->columns["HoursWorked"]["default"])) {
                return $this->columns["HoursWorked"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["HoursWorked"] = func_get_arg(0);
        }
    }

    public function PTOHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PTOHours"])) {
                return $this->instance["PTOHours"];
            } elseif(isset($this->columns["PTOHours"]["default"])) {
                return $this->columns["PTOHours"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["PTOHours"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

