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
  @class VendorsModel
*/
class VendorsModel extends BasicModel {

	protected $name = "vendors";

	protected $columns = array(
	'vendorID' => array('type'=>'INT', 'primary_key'=>True),
	'vendorName' => array('type'=>'VARCHAR(50)')
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

	public function vendorName(){
		if(func_num_args() == 0){
			if(isset($this->instance["vendorName"]))
				return $this->instance["vendorName"];
			elseif(isset($this->columns["vendorName"]["default"]))
				return $this->columns["vendorName"]["default"];
			else return null;
		}
		else{
			$this->instance["vendorName"] = func_get_arg(0);
		}
	}
	/* END ACCESSOR FUNCTIONS */
}
?>
