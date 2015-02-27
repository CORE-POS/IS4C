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
  @class MasterSuperDeptsModel
*/
class MasterSuperDeptsModel extends BasicModel
{

    protected $name = "MasterSuperDepts";

    protected $preferred_db = 'op';

    protected $columns = array(
    'superID' => array('type'=>'INT'),
    'super_name' => array('type'=>'VARCHAR(50)'),
    'dept_ID' => array('type'=>'INT'),
    );

    public function doc()
    {
        return '
Table: MasterSuperDepts

Columns:
    superID int
    super_name var_char
    dept_ID int

Depends on:
    SuperMinIdView (view)
    superDeptNames (table)

Use:
A department may belong to more than one superdepartment, but
has one "master" superdepartment. This avoids duplicating
rows in some reports. By convention, a department\'s
"master" superdepartment is the one with the lowest superID.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function superID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["superID"])) {
                return $this->instance["superID"];
            } elseif(isset($this->columns["superID"]["default"])) {
                return $this->columns["superID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["superID"] = func_get_arg(0);
        }
    }

    public function super_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["super_name"])) {
                return $this->instance["super_name"];
            } elseif(isset($this->columns["super_name"]["default"])) {
                return $this->columns["super_name"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["super_name"] = func_get_arg(0);
        }
    }

    public function dept_ID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_ID"])) {
                return $this->instance["dept_ID"];
            } elseif(isset($this->columns["dept_ID"]["default"])) {
                return $this->columns["dept_ID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dept_ID"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

