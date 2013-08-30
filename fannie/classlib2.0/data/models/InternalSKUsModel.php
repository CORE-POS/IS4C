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
  @class InternalSKUsModel
*/
class InternalSKUsModel extends BasicModel {

	protected $name = "internalSKUs";

	protected $columns = array(
	'our_sku' => array('type'=>'INT','primary_key'=>True),
	'vendor_sku' => array('type'=>'VARCHAR(13)'),
	'vendorID' => array('type'=>'INT'),
	'upc' => array('type'=>'VARCHAR(13)')
	);

	/* START ACCESSOR FUNCTIONS */

	public function our_sku(){
		if(func_num_args() == 0){
			if(isset($this->instance["our_sku"]))
				return $this->instance["our_sku"];
			elseif(isset($this->columns["our_sku"]["default"]))
				return $this->columns["our_sku"]["default"];
			else return null;
		}
		else{
			$this->instance["our_sku"] = func_get_arg(0);
		}
	}

	public function vendor_sku(){
		if(func_num_args() == 0){
			if(isset($this->instance["vendor_sku"]))
				return $this->instance["vendor_sku"];
			elseif(isset($this->columns["vendor_sku"]["default"]))
				return $this->columns["vendor_sku"]["default"];
			else return null;
		}
		else{
			$this->instance["vendor_sku"] = func_get_arg(0);
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
	/* END ACCESSOR FUNCTIONS */
}
?>
