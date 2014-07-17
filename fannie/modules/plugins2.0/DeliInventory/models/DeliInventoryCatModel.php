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
  @class DeliInventoryCatModel
*/
class DeliInventoryCatModel extends BasicModel {

    protected $name = "deliInventoryCat";

    protected $columns = array(
    'id' => array('type'=>'INT', 'primary_key' => True, 'increment'=>True),
    'item' => array('type'=>'VARCHAR(50)'),
    'orderno' => array('type'=>'VARCHAR(15)'),
    'units' => array('type'=>'VARCHAR(10)'),
    'cases' => array('type'=>'FLOAT'),
    'fraction' => array('type'=>'VARCHAR(10)'),
    'totalstock' => array('type'=>'FLOAT'),
    'price' => array('type'=>'MONEY'),
    'total' => array('type'=>'MONEY'),
    'size' => array('type'=>'VARCHAR(20)'),
    'category' => array('type'=>'VARCHAR(50)', 'index'=>True)
    );

    /* START ACCESSOR FUNCTIONS */

    public function id(){
        if(func_num_args() == 0){
            if(isset($this->instance["id"]))
                return $this->instance["id"];
            elseif(isset($this->columns["id"]["default"]))
                return $this->columns["id"]["default"];
            else return null;
        }
        else{
            $this->instance["id"] = func_get_arg(0);
        }
    }

    public function item(){
        if(func_num_args() == 0){
            if(isset($this->instance["item"]))
                return $this->instance["item"];
            elseif(isset($this->columns["item"]["default"]))
                return $this->columns["item"]["default"];
            else return null;
        }
        else{
            $this->instance["item"] = func_get_arg(0);
        }
    }

    public function orderno(){
        if(func_num_args() == 0){
            if(isset($this->instance["orderno"]))
                return $this->instance["orderno"];
            elseif(isset($this->columns["orderno"]["default"]))
                return $this->columns["orderno"]["default"];
            else return null;
        }
        else{
            $this->instance["orderno"] = func_get_arg(0);
        }
    }

    public function units(){
        if(func_num_args() == 0){
            if(isset($this->instance["units"]))
                return $this->instance["units"];
            elseif(isset($this->columns["units"]["default"]))
                return $this->columns["units"]["default"];
            else return null;
        }
        else{
            $this->instance["units"] = func_get_arg(0);
        }
    }

    public function cases(){
        if(func_num_args() == 0){
            if(isset($this->instance["cases"]))
                return $this->instance["cases"];
            elseif(isset($this->columns["cases"]["default"]))
                return $this->columns["cases"]["default"];
            else return null;
        }
        else{
            $this->instance["cases"] = func_get_arg(0);
        }
    }

    public function fraction(){
        if(func_num_args() == 0){
            if(isset($this->instance["fraction"]))
                return $this->instance["fraction"];
            elseif(isset($this->columns["fraction"]["default"]))
                return $this->columns["fraction"]["default"];
            else return null;
        }
        else{
            $this->instance["fraction"] = func_get_arg(0);
        }
    }

    public function totalstock(){
        if(func_num_args() == 0){
            if(isset($this->instance["totalstock"]))
                return $this->instance["totalstock"];
            elseif(isset($this->columns["totalstock"]["default"]))
                return $this->columns["totalstock"]["default"];
            else return null;
        }
        else{
            $this->instance["totalstock"] = func_get_arg(0);
        }
    }

    public function price(){
        if(func_num_args() == 0){
            if(isset($this->instance["price"]))
                return $this->instance["price"];
            elseif(isset($this->columns["price"]["default"]))
                return $this->columns["price"]["default"];
            else return null;
        }
        else{
            $this->instance["price"] = func_get_arg(0);
        }
    }

    public function total(){
        if(func_num_args() == 0){
            if(isset($this->instance["total"]))
                return $this->instance["total"];
            elseif(isset($this->columns["total"]["default"]))
                return $this->columns["total"]["default"];
            else return null;
        }
        else{
            $this->instance["total"] = func_get_arg(0);
        }
    }

    public function size(){
        if(func_num_args() == 0){
            if(isset($this->instance["size"]))
                return $this->instance["size"];
            elseif(isset($this->columns["size"]["default"]))
                return $this->columns["size"]["default"];
            else return null;
        }
        else{
            $this->instance["size"] = func_get_arg(0);
        }
    }

    public function category(){
        if(func_num_args() == 0){
            if(isset($this->instance["category"]))
                return $this->instance["category"];
            elseif(isset($this->columns["category"]["default"]))
                return $this->columns["category"]["default"];
            else return null;
        }
        else{
            $this->instance["category"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}
?>
