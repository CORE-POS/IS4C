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
  @class ScreenDisplayModel
*/
class ScreenDisplayModel extends BasicModel
{

    protected $name = "screendisplay";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'description' => array('type'=>'VARCHAR(255)'),
    'comment' => array('type'=>'VARCHAR(255)'),
    'total' => array('type'=>'MONEY'),
    'status' => array('type'=>'VARCHAR(255)'),
    'lineColor' => array('type'=>'VARCHAR(255)'),
    'discounttype' => array('type'=>'INT'),
    'trans_type' => array('type'=>'VARCHAR(2)'),
    'trans_status' => array('type'=>'VARCHAR(2)'),
    'voided' => array('type'=>'INT'),
    'trans_id' => array('type'=>'INT'),
	);

    /* disabled because it's a view */
    public function create(){ return false; }
    public function delete(){ return false; }
    public function save(){ return false; }
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False){ return 0; }

    /* START ACCESSOR FUNCTIONS */

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } elseif(isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["description"] = func_get_arg(0);
        }
    }

    public function comment()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["comment"])) {
                return $this->instance["comment"];
            } elseif(isset($this->columns["comment"]["default"])) {
                return $this->columns["comment"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["comment"] = func_get_arg(0);
        }
    }

    public function total()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["total"])) {
                return $this->instance["total"];
            } elseif(isset($this->columns["total"]["default"])) {
                return $this->columns["total"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["total"] = func_get_arg(0);
        }
    }

    public function status()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["status"])) {
                return $this->instance["status"];
            } elseif(isset($this->columns["status"]["default"])) {
                return $this->columns["status"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["status"] = func_get_arg(0);
        }
    }

    public function lineColor()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lineColor"])) {
                return $this->instance["lineColor"];
            } elseif(isset($this->columns["lineColor"]["default"])) {
                return $this->columns["lineColor"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["lineColor"] = func_get_arg(0);
        }
    }

    public function discounttype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discounttype"])) {
                return $this->instance["discounttype"];
            } elseif(isset($this->columns["discounttype"]["default"])) {
                return $this->columns["discounttype"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["discounttype"] = func_get_arg(0);
        }
    }

    public function trans_type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_type"])) {
                return $this->instance["trans_type"];
            } elseif(isset($this->columns["trans_type"]["default"])) {
                return $this->columns["trans_type"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_type"] = func_get_arg(0);
        }
    }

    public function trans_status()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_status"])) {
                return $this->instance["trans_status"];
            } elseif(isset($this->columns["trans_status"]["default"])) {
                return $this->columns["trans_status"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_status"] = func_get_arg(0);
        }
    }

    public function voided()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["voided"])) {
                return $this->instance["voided"];
            } elseif(isset($this->columns["voided"]["default"])) {
                return $this->columns["voided"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["voided"] = func_get_arg(0);
        }
    }

    public function trans_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_id"])) {
                return $this->instance["trans_id"];
            } elseif(isset($this->columns["trans_id"]["default"])) {
                return $this->columns["trans_id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["trans_id"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

