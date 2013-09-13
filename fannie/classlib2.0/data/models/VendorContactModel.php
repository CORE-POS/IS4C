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
  @class VendorContactModel
*/
class VendorContactModel extends BasicModel {

	protected $name = "vendorContact";

	protected $columns = array(
	'vendorID' => array('type'=>'INT','primary_key'=>True),
	'phone' => array('type'=>'VARCHAR(15)'),
	'fax' => array('type'=>'VARCHAR(15)'),
	'email' => array('type'=>'VARCHAR(50)'),
	'website' => array('type'=>'VARCHAR(100)'),
	'notes' => array('type'=>'TEXT')
	);

	/* START ACCESSOR FUNCTIONS */

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

	public function phone(){
		if(func_num_args() == 0){
			if(isset($this->instance["phone"]))
				return $this->instance["phone"];
			elseif(isset($this->columns["phone"]["default"]))
				return $this->columns["phone"]["default"];
			else return null;
		}
		else{
			$this->instance["phone"] = func_get_arg(0);
		}
	}

	public function fax(){
		if(func_num_args() == 0){
			if(isset($this->instance["fax"]))
				return $this->instance["fax"];
			elseif(isset($this->columns["fax"]["default"]))
				return $this->columns["fax"]["default"];
			else return null;
		}
		else{
			$this->instance["fax"] = func_get_arg(0);
		}
	}

	public function email(){
		if(func_num_args() == 0){
			if(isset($this->instance["email"]))
				return $this->instance["email"];
			elseif(isset($this->columns["email"]["default"]))
				return $this->columns["email"]["default"];
			else return null;
		}
		else{
			$this->instance["email"] = func_get_arg(0);
		}
	}

	public function website(){
		if(func_num_args() == 0){
			if(isset($this->instance["website"]))
				return $this->instance["website"];
			elseif(isset($this->columns["website"]["default"]))
				return $this->columns["website"]["default"];
			else return null;
		}
		else{
			$this->instance["website"] = func_get_arg(0);
		}
	}

	public function notes(){
		if(func_num_args() == 0){
			if(isset($this->instance["notes"]))
				return $this->instance["notes"];
			elseif(isset($this->columns["notes"]["default"]))
				return $this->columns["notes"]["default"];
			else return null;
		}
		else{
			$this->instance["notes"] = func_get_arg(0);
		}
	}
	/* END ACCESSOR FUNCTIONS */
}
?>
