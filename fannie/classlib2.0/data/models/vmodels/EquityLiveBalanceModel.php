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
  @class EquityLiveBalanceModel
*/
class EquityLiveBalanceModel extends BasicModel 
{

    protected $name = "newBalanceStockToday_test";

    protected $columns = array(
    'memnum' => array('type'=>'INT','primary_key'=>True),
    'payments' => array('type','MONEY'),
    'startdate' => array('type','DATETIME')
    );

    public function create(){ return false; }
    public function delete(){ return false; }
    public function save(){ return false; }
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false){ return 0; }

    /* START ACCESSOR FUNCTIONS */

    public function memnum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memnum"])) {
                return $this->instance["memnum"];
            } elseif(isset($this->columns["memnum"]["default"])) {
                return $this->columns["memnum"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["memnum"] = func_get_arg(0);
        }
    }

    public function payments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["payments"])) {
                return $this->instance["payments"];
            } elseif(isset($this->columns["payments"]["default"])) {
                return $this->columns["payments"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["payments"] = func_get_arg(0);
        }
    }

    public function startdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startdate"])) {
                return $this->instance["startdate"];
            } elseif(isset($this->columns["startdate"]["default"])) {
                return $this->columns["startdate"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["startdate"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

