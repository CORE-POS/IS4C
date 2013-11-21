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
  @class DateRestrictModel
*/
class DateRestrictModel extends BasicModel
{

    protected $name = "dateRestrict";
    protected $preferred_db = 'op';

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)','primary_key'=>true),
    'dept_ID' => array('type'=>'INT','primary_key'=>true),
    'restrict_date' => array('type'=>'DATE'),
    'restrict_dow' => array('type'=>'SMALLINT'),
    'restrict_start' => array('type'=>'TIME'),
    'restrict_end' => array('type'=>'TIME'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } elseif(isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["upc"] = func_get_arg(0);
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

    public function restrict_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["restrict_date"])) {
                return $this->instance["restrict_date"];
            } elseif(isset($this->columns["restrict_date"]["default"])) {
                return $this->columns["restrict_date"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["restrict_date"] = func_get_arg(0);
        }
    }

    public function restrict_dow()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["restrict_dow"])) {
                return $this->instance["restrict_dow"];
            } elseif(isset($this->columns["restrict_dow"]["default"])) {
                return $this->columns["restrict_dow"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["restrict_dow"] = func_get_arg(0);
        }
    }

    public function restrict_start()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["restrict_start"])) {
                return $this->instance["restrict_start"];
            } elseif(isset($this->columns["restrict_start"]["default"])) {
                return $this->columns["restrict_start"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["restrict_start"] = func_get_arg(0);
        }
    }

    public function restrict_END()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["restrict_END"])) {
                return $this->instance["restrict_END"];
            } elseif(isset($this->columns["restrict_END"]["default"])) {
                return $this->columns["restrict_END"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["restrict_END"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

