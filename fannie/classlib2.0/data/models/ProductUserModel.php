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
  @class ProductUserModel
*/
class ProductUserModel extends BasicModel 
{

    protected $name = "productUser";

    protected $columns = array(
    'upc' => array('type'=>'VARCHAR(13)', 'primary_key'=>True),
    'description' => array('type'=>'VARCHAR(255)'),
    'brand' => array('type'=>'VARCHAR(255)'),
    'sizing' => array('type'=>'VARCHAR(255)'),
    'photo' => array('type'=>'VARCHAR(255)'),
    'long_text' => array('type'=>'TEXT'),
    'enableOnline' => array('type'=>'TINYINT')
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

    public function brand()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["brand"])) {
                return $this->instance["brand"];
            } elseif(isset($this->columns["brand"]["default"])) {
                return $this->columns["brand"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["brand"] = func_get_arg(0);
        }
    }

    public function sizing()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["sizing"])) {
                return $this->instance["sizing"];
            } elseif(isset($this->columns["sizing"]["default"])) {
                return $this->columns["sizing"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["sizing"] = func_get_arg(0);
        }
    }

    public function photo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["photo"])) {
                return $this->instance["photo"];
            } elseif(isset($this->columns["photo"]["default"])) {
                return $this->columns["photo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["photo"] = func_get_arg(0);
        }
    }

    public function long_text()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["long_text"])) {
                return $this->instance["long_text"];
            } elseif(isset($this->columns["long_text"]["default"])) {
                return $this->columns["long_text"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["long_text"] = func_get_arg(0);
        }
    }

    public function enableOnline()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["enableOnline"])) {
                return $this->instance["enableOnline"];
            } elseif(isset($this->columns["enableOnline"]["default"])) {
                return $this->columns["enableOnline"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["enableOnline"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

