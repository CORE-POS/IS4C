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
  @class MemdefaultsModel
*/
class MemdefaultsModel extends BasicModel 
{

    protected $name = "memdefaults";

    protected $preferred_db = 'op';

    protected $columns = array(
    'memtype' => array('type'=>'TINYINT','primary_key'=>True,'default'=>0),
    'cd_type' => array('type'=>'VARCHAR(10)'),
    'discount' => array('type'=>'SMALLINT','default'=>0),    
    'staff' => array('type'=>'TINYINT','default'=>0),
    'SSI' => array('type'=>'TINYINT','default'=>0)
    );

    /* START ACCESSOR FUNCTIONS */

    public function memtype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memtype"])) {
                return $this->instance["memtype"];
            } elseif(isset($this->columns["memtype"]["default"])) {
                return $this->columns["memtype"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["memtype"] = func_get_arg(0);
        }
    }

    public function cd_type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cd_type"])) {
                return $this->instance["cd_type"];
            } elseif(isset($this->columns["cd_type"]["default"])) {
                return $this->columns["cd_type"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["cd_type"] = func_get_arg(0);
        }
    }

    public function discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discount"])) {
                return $this->instance["discount"];
            } elseif(isset($this->columns["discount"]["default"])) {
                return $this->columns["discount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["discount"] = func_get_arg(0);
        }
    }

    public function staff()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["staff"])) {
                return $this->instance["staff"];
            } elseif(isset($this->columns["staff"]["default"])) {
                return $this->columns["staff"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["staff"] = func_get_arg(0);
        }
    }

    public function SSI()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["SSI"])) {
                return $this->instance["SSI"];
            } elseif(isset($this->columns["SSI"]["default"])) {
                return $this->columns["SSI"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["SSI"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

