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
  @class LaneConfigModel
*/
class LaneConfigModel extends BasicModel
{

    protected $name = "lane_config";
    protected $preferred_db = 'op';

    protected $columns = array(
    'keycode' => array('type'=>'VARCHAR(255)','primary_key'=>true),
    'value' => array('type'=>'VARCHAR(255)'),
    'modified' => array('type'=>'DATETIME'),
	);

    /* START ACCESSOR FUNCTIONS */

    public function keycode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["keycode"])) {
                return $this->instance["keycode"];
            } elseif(isset($this->columns["keycode"]["default"])) {
                return $this->columns["keycode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["keycode"] = func_get_arg(0);
        }
    }

    public function value()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["value"])) {
                return $this->instance["value"];
            } elseif(isset($this->columns["value"]["default"])) {
                return $this->columns["value"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["value"] = func_get_arg(0);
        }
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } elseif(isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["modified"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

