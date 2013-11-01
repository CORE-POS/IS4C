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
  @class SuspensionHistoryModel
*/
class SuspensionHistoryModel extends BasicModel 
{

    protected $name = "suspension_history";

    protected $preferred_db = 'op';

    protected $columns = array(
    'username' => array('type'=>'VARCHAR(50)'),
    'postdate' => array('type'=>'DATETIME'),
    'post' => array('type'=>'TEXT'),
    'cardno' => array('type'=>'INT'),
    'reasoncode' => array('type'=>'INT')
    );

    /* START ACCESSOR FUNCTIONS */

    public function username()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["username"])) {
                return $this->instance["username"];
            } elseif(isset($this->columns["username"]["default"])) {
                return $this->columns["username"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["username"] = func_get_arg(0);
        }
    }

    public function postdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["postdate"])) {
                return $this->instance["postdate"];
            } elseif(isset($this->columns["postdate"]["default"])) {
                return $this->columns["postdate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["postdate"] = func_get_arg(0);
        }
    }

    public function post()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["post"])) {
                return $this->instance["post"];
            } elseif(isset($this->columns["post"]["default"])) {
                return $this->columns["post"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["post"] = func_get_arg(0);
        }
    }

    public function cardno()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardno"])) {
                return $this->instance["cardno"];
            } elseif(isset($this->columns["cardno"]["default"])) {
                return $this->columns["cardno"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["cardno"] = func_get_arg(0);
        }
    }

    public function reasoncode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reasoncode"])) {
                return $this->instance["reasoncode"];
            } elseif(isset($this->columns["reasoncode"]["default"])) {
                return $this->columns["reasoncode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["reasoncode"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

