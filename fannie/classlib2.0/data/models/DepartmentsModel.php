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
  @class DepartmentsModel
*/
class DepartmentsModel extends BasicModel 
{

    protected $name = "departments";

    protected $preferred_db = 'op';

    protected $columns = array(
    'dept_no' => array('type'=>'SMALLINT','primary_key'=>True),
    'dept_name' => array('type'=>'VARCHAR(30)','index'=>True),
    'dept_tax' => array('type'=>'TINYINT'),
    'dept_fs' => array('type'=>'TINYINT'),
    'dept_limit' => array('type'=>'MONEY'),
    'dept_minimum' => array('type'=>'MONEY'),
    'dept_discount' => array('type'=>'TINYINT'),
    'dept_see_id' => array('type'=>'TINYINT'),
    'modified' => array('type'=>'DATETIME'),
    'modifiedby' => array('type'=>'INT')
    );

    /* START ACCESSOR FUNCTIONS */

    public function dept_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_no"])) {
                return $this->instance["dept_no"];
            } elseif(isset($this->columns["dept_no"]["default"])) {
                return $this->columns["dept_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dept_no"] = func_get_arg(0);
        }
    }

    public function dept_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_name"])) {
                return $this->instance["dept_name"];
            } elseif(isset($this->columns["dept_name"]["default"])) {
                return $this->columns["dept_name"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dept_name"] = func_get_arg(0);
        }
    }

    public function dept_tax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_tax"])) {
                return $this->instance["dept_tax"];
            } elseif(isset($this->columns["dept_tax"]["default"])) {
                return $this->columns["dept_tax"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dept_tax"] = func_get_arg(0);
        }
    }

    public function dept_fs()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_fs"])) {
                return $this->instance["dept_fs"];
            } elseif(isset($this->columns["dept_fs"]["default"])) {
                return $this->columns["dept_fs"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dept_fs"] = func_get_arg(0);
        }
    }

    public function dept_limit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_limit"])) {
                return $this->instance["dept_limit"];
            } elseif(isset($this->columns["dept_limit"]["default"])) {
                return $this->columns["dept_limit"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dept_limit"] = func_get_arg(0);
        }
    }

    public function dept_minimum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_minimum"])) {
                return $this->instance["dept_minimum"];
            } elseif(isset($this->columns["dept_minimum"]["default"])) {
                return $this->columns["dept_minimum"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dept_minimum"] = func_get_arg(0);
        }
    }

    public function dept_discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_discount"])) {
                return $this->instance["dept_discount"];
            } elseif(isset($this->columns["dept_discount"]["default"])) {
                return $this->columns["dept_discount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dept_discount"] = func_get_arg(0);
        }
    }

    public function dept_see_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dept_see_id"])) {
                return $this->instance["dept_see_id"];
            } elseif(isset($this->columns["dept_see_id"]["default"])) {
                return $this->columns["dept_see_id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dept_see_id"] = func_get_arg(0);
        }
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } elseif(isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["modified"] = func_get_arg(0);
        }
    }

    public function modifiedby()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modifiedby"])) {
                return $this->instance["modifiedby"];
            } elseif(isset($this->columns["modifiedby"]["default"])) {
                return $this->columns["modifiedby"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["modifiedby"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

