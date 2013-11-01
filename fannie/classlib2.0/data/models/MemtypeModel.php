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
  @class MemtypeModel
*/
class MemtypeModel extends BasicModel 
{

    protected $name = "memtype";

    protected $preferred_db = 'op';

    protected $columns = array(
    'memtype' => array('type'=>'TINYINT','primary_key'=>True,'default'=>0),
    'memDesc' => array('type'=>'VARCHAR(20)')
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

    public function memDesc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memDesc"])) {
                return $this->instance["memDesc"];
            } elseif(isset($this->columns["memDesc"]["default"])) {
                return $this->columns["memDesc"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["memDesc"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

