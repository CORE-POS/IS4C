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
  @class BatchListModel
*/
class BatchListModel extends BasicModel 
{

    protected $name = "batchList";

    protected $columns = array(
    'listID' => array('type'=>'INT', 'primary_key'=>True, 'increment'=>True),
    'upc' => array('type'=>'VARCHAR(13)','index'=>True),
    'batchID' => array('type'=>'INT','index'=>True),
    'salePrice' => array('type'=>'MONEY'),
    'active' => array('type'=>'TINYINT'),
    'pricemethod' => array('type'=>'SMALLINT','default'=>0),
    'quantity' => array('type'=>'SMALLINT','default'=>0)
    );

    protected $unique = array('batchID','upc');

    /* START ACCESSOR FUNCTIONS */

    public function listID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["listID"])) {
                return $this->instance["listID"];
            } elseif(isset($this->columns["listID"]["default"])) {
                return $this->columns["listID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["listID"] = func_get_arg(0);
        }
    }

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

    public function salePrice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["salePrice"])) {
                return $this->instance["salePrice"];
            } elseif(isset($this->columns["salePrice"]["default"])) {
                return $this->columns["salePrice"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["salePrice"] = func_get_arg(0);
        }
    }

    public function active()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["active"])) {
                return $this->instance["active"];
            } elseif(isset($this->columns["active"]["default"])) {
                return $this->columns["active"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["active"] = func_get_arg(0);
        }
    }

    public function pricemethod()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pricemethod"])) {
                return $this->instance["pricemethod"];
            } elseif(isset($this->columns["pricemethod"]["default"])) {
                return $this->columns["pricemethod"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["pricemethod"] = func_get_arg(0);
        }
    }

    public function quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quantity"])) {
                return $this->instance["quantity"];
            } elseif(isset($this->columns["quantity"]["default"])) {
                return $this->columns["quantity"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["quantity"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

