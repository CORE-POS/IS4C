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
  @class TaxViewModel
*/
class TaxViewModel extends BasicModel
{

    protected $name = "taxView";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'id' => array('type'=>'INT'),
    'description' => array('type'=>'VARCHAR(255)'),
    'taxTotal' => array('type'=>'MONEY'),
    'fsTaxable' => array('type'=>'MONEY'),
    'fsTaxTotal' => array('type'=>'MONEY'),
    'foodstampTender' => array('type'=>'MONEY'),
    'taxrate' => array('type'=>'FLOAT'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["id"])) {
                return $this->instance["id"];
            } elseif(isset($this->columns["id"]["default"])) {
                return $this->columns["id"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["id"] = func_get_arg(0);
        }
    }

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

    public function taxTotal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["taxTotal"])) {
                return $this->instance["taxTotal"];
            } elseif(isset($this->columns["taxTotal"]["default"])) {
                return $this->columns["taxTotal"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["taxTotal"] = func_get_arg(0);
        }
    }

    public function fsTaxable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["fsTaxable"])) {
                return $this->instance["fsTaxable"];
            } elseif(isset($this->columns["fsTaxable"]["default"])) {
                return $this->columns["fsTaxable"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["fsTaxable"] = func_get_arg(0);
        }
    }

    public function fsTaxTotal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["fsTaxTotal"])) {
                return $this->instance["fsTaxTotal"];
            } elseif(isset($this->columns["fsTaxTotal"]["default"])) {
                return $this->columns["fsTaxTotal"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["fsTaxTotal"] = func_get_arg(0);
        }
    }

    public function foodstampTender()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["foodstampTender"])) {
                return $this->instance["foodstampTender"];
            } elseif(isset($this->columns["foodstampTender"]["default"])) {
                return $this->columns["foodstampTender"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["foodstampTender"] = func_get_arg(0);
        }
    }

    public function taxrate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["taxrate"])) {
                return $this->instance["taxrate"];
            } elseif(isset($this->columns["taxrate"]["default"])) {
                return $this->columns["taxrate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["taxrate"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

