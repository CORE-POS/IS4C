<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class EmvReceiptModel
*/
class EmvReceiptModel extends BasicModel
{
    protected $name = "EmvReceipt";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'dateID' => array('type'=>'INT', 'index'=>true),
    'tdate' => array('type'=>'DATETIME', 'index'=>true),
    'empNo' => array('type'=>'INT'),
    'registerNo' => array('type'=>'INT', 'index'=>true),
    'transNo' => array('type'=>'INT'),
    'transID' => array('type'=>'INT'),
    'content' => array('type'=>'BLOB'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function dateID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dateID"])) {
                return $this->instance["dateID"];
            } elseif(isset($this->columns["dateID"]["default"])) {
                return $this->columns["dateID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["dateID"] = func_get_arg(0);
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

    public function empNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empNo"])) {
                return $this->instance["empNo"];
            } elseif(isset($this->columns["empNo"]["default"])) {
                return $this->columns["empNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["empNo"] = func_get_arg(0);
        }
    }

    public function registerNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["registerNo"])) {
                return $this->instance["registerNo"];
            } elseif(isset($this->columns["registerNo"]["default"])) {
                return $this->columns["registerNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["registerNo"] = func_get_arg(0);
        }
    }

    public function transNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transNo"])) {
                return $this->instance["transNo"];
            } elseif(isset($this->columns["transNo"]["default"])) {
                return $this->columns["transNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["transNo"] = func_get_arg(0);
        }
    }

    public function transID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transID"])) {
                return $this->instance["transID"];
            } elseif(isset($this->columns["transID"]["default"])) {
                return $this->columns["transID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["transID"] = func_get_arg(0);
        }
    }

    public function content()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["content"])) {
                return $this->instance["content"];
            } elseif(isset($this->columns["content"]["default"])) {
                return $this->columns["content"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["content"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

