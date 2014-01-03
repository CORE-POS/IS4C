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
  @class UsageStatsModel
*/
class UsageStatsModel extends BasicModel
{

    protected $name = "usageStats";

    protected $columns = array(
    'usageID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'tdate' => array('type'=>'DATETIME'),
    'pageName' => array('type'=>'VARCHAR(100)'),
    'referrer' => array('type'=>'VARCHAR(100)'),
    'userHash' => array('type'=>'VARCHAR(40)'),
    'ipHash' => array('type'=>'VARCHAR(40)'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function usageID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["usageID"])) {
                return $this->instance["usageID"];
            } elseif(isset($this->columns["usageID"]["default"])) {
                return $this->columns["usageID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["usageID"] = func_get_arg(0);
        }
    }

    public function tdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tdate"])) {
                return $this->instance["tdate"];
            } elseif(isset($this->columns["tdate"]["default"])) {
                return $this->columns["tdate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["tdate"] = func_get_arg(0);
        }
    }

    public function pageName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pageName"])) {
                return $this->instance["pageName"];
            } elseif(isset($this->columns["pageName"]["default"])) {
                return $this->columns["pageName"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["pageName"] = func_get_arg(0);
        }
    }

    public function referrer()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["referrer"])) {
                return $this->instance["referrer"];
            } elseif(isset($this->columns["referrer"]["default"])) {
                return $this->columns["referrer"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["referrer"] = func_get_arg(0);
        }
    }

    public function userHash()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["userHash"])) {
                return $this->instance["userHash"];
            } elseif(isset($this->columns["userHash"]["default"])) {
                return $this->columns["userHash"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["userHash"] = func_get_arg(0);
        }
    }

    public function ipHash()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ipHash"])) {
                return $this->instance["ipHash"];
            } elseif(isset($this->columns["ipHash"]["default"])) {
                return $this->columns["ipHash"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["ipHash"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

