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
  @class VendorItemsModel
*/
class VendorItemsModel extends BasicModel {

	protected $name = "vendorItems";

	protected $columns = array(
	'upc' => array('type'=>'VARCHAR(13)','index'=>True),
	'sku' => array('type'=>'VARCHAR(13)','index'=>True,'primary_key'=>True),
	'brand' => array('type'=>'VARCHAR(50)'),
	'description' => array('type'=>'VARCHAR(50)'),
	'size' => array('type'=>'VARCHAR(25)'),
	'units' => array('type'=>'INT'),
	'cost' => array('type'=>'MONEY'),
	'vendorDept' => array('type'=>'INT'),
	'vendorID' => array('type'=>'INT','index'=>True,'primary_key'=>True)
	);

	/* START ACCESSOR FUNCTIONS */

	public function upc(){
		if(func_num_args() == 0){
			if(isset($this->instance["upc"]))
				return $this->instance["upc"];
			elseif(isset($this->columns["upc"]["default"]))
				return $this->columns["upc"]["default"];
			else return null;
		}
		else{
			$this->instance["upc"] = func_get_arg(0);
		}
	}

	public function sku(){
		if(func_num_args() == 0){
			if(isset($this->instance["sku"]))
				return $this->instance["sku"];
			elseif(isset($this->columns["sku"]["default"]))
				return $this->columns["sku"]["default"];
			else return null;
		}
		else{
			$this->instance["sku"] = func_get_arg(0);
		}
	}

	public function brand(){
		if(func_num_args() == 0){
			if(isset($this->instance["brand"]))
				return $this->instance["brand"];
			elseif(isset($this->columns["brand"]["default"]))
				return $this->columns["brand"]["default"];
			else return null;
		}
		else{
			$this->instance["brand"] = func_get_arg(0);
		}
	}

	public function description(){
		if(func_num_args() == 0){
			if(isset($this->instance["description"]))
				return $this->instance["description"];
			elseif(isset($this->columns["description"]["default"]))
				return $this->columns["description"]["default"];
			else return null;
		}
		else{
			$this->instance["description"] = func_get_arg(0);
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

	public function cost(){
		if(func_num_args() == 0){
			if(isset($this->instance["cost"]))
				return $this->instance["cost"];
			elseif(isset($this->columns["cost"]["default"]))
				return $this->columns["cost"]["default"];
			else return null;
		}
		else{
			$this->instance["cost"] = func_get_arg(0);
		}
	}

	public function vendorDept(){
		if(func_num_args() == 0){
			if(isset($this->instance["vendorDept"]))
				return $this->instance["vendorDept"];
			elseif(isset($this->columns["vendorDept"]["default"]))
				return $this->columns["vendorDept"]["default"];
			else return null;
		}
		else{
			$this->instance["vendorDept"] = func_get_arg(0);
		}
	}

	public function vendorID(){
		if(func_num_args() == 0){
			if(isset($this->instance["vendorID"]))
				return $this->instance["vendorID"];
			elseif(isset($this->columns["vendorID"]["default"]))
				return $this->columns["vendorID"]["default"];
			else return null;
		}
		else{
			$this->instance["vendorID"] = func_get_arg(0);
		}
	}
	/* END ACCESSOR FUNCTIONS */
}
?>
