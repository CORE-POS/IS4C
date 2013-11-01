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
  @class BatchesModel
*/
class BatchesModel extends BasicModel 
{

    protected $name = "batches";

    protected $columns = array(
    'batchID' => array('type'=>'INT', 'primary_key'=>True, 'increment'=>True),
    'startDate' => array('type'=>'DATETIME'),
    'endDate' => array('type'=>'DATETIME'),
    'batchName' => array('type'=>'VARCHAR(80)'),
    'batchType' => array('type'=>'SMALLINT'),
    'discountType' => array('type'=>'SMALLINT'),
    'priority' => array('type'=>'INT')
    );

    /* START ACCESSOR FUNCTIONS */

    public function batchID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["batchID"])) {
                return $this->instance["batchID"];
            } elseif(isset($this->columns["batchID"]["default"])) {
                return $this->columns["batchID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["batchID"] = func_get_arg(0);
        }
    }

    public function startDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startDate"])) {
                return $this->instance["startDate"];
            } elseif(isset($this->columns["startDate"]["default"])) {
                return $this->columns["startDate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["startDate"] = func_get_arg(0);
        }
    }

    public function endDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["endDate"])) {
                return $this->instance["endDate"];
            } elseif(isset($this->columns["endDate"]["default"])) {
                return $this->columns["endDate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["endDate"] = func_get_arg(0);
        }
    }

    public function batchName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["batchName"])) {
                return $this->instance["batchName"];
            } elseif(isset($this->columns["batchName"]["default"])) {
                return $this->columns["batchName"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["batchName"] = func_get_arg(0);
        }
    }

    public function batchType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["batchType"])) {
                return $this->instance["batchType"];
            } elseif(isset($this->columns["batchType"]["default"])) {
                return $this->columns["batchType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["batchType"] = func_get_arg(0);
        }
    }

    public function discountType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountType"])) {
                return $this->instance["discountType"];
            } elseif(isset($this->columns["discountType"]["default"])) {
                return $this->columns["discountType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["discountType"] = func_get_arg(0);
        }
    }

    public function priority()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["priority"])) {
                return $this->instance["priority"];
            } elseif(isset($this->columns["priority"]["default"])) {
                return $this->columns["priority"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["priority"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

