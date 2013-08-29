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
  @class DisableCouponModel
*/
class DisableCouponModel extends BasicModel {

	protected $name = "disableCoupon";

	protected $columns = array(
	'upc' => array('type'=>'VARCHAR(13)','primary_key'=>True),
	'threshold' => array('type'=>'SMALLINT','default'=>0),
	'reason' => array('type'=>'text')
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

	public function threshold(){
		if(func_num_args() == 0){
			if(isset($this->instance["threshold"]))
				return $this->instance["threshold"];
			elseif(isset($this->columns["threshold"]["default"]))
				return $this->columns["threshold"]["default"];
			else return null;
		}
		else{
			$this->instance["threshold"] = func_get_arg(0);
		}
	}

	public function reason(){
		if(func_num_args() == 0){
			if(isset($this->instance["reason"]))
				return $this->instance["reason"];
			elseif(isset($this->columns["reason"]["default"]))
				return $this->columns["reason"]["default"];
			else return null;
		}
		else{
			$this->instance["reason"] = func_get_arg(0);
		}
	}
	/* END ACCESSOR FUNCTIONS */
}
?>
