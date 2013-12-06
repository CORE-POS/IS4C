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
  @class MemChargeBalanceModel
*/
class MemChargeBalanceModel extends BasicModel
{

    protected $name = "memchargebalance";
    protected $preferred_db = 'op';

    protected $columns = array(
    'CardNo' => array('type'=>'INT'),
    'availBal' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
	);

    /* disabled because it's a view */
    public function create(){ return false; }
    public function delete(){ return false; }
    public function save(){ return false; }
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=False){ return 0; }

    /* START ACCESSOR FUNCTIONS */

    public function CardNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["CardNo"])) {
                return $this->instance["CardNo"];
            } elseif(isset($this->columns["CardNo"]["default"])) {
                return $this->columns["CardNo"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["CardNo"] = func_get_arg(0);
        }
    }

    public function availBal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["availBal"])) {
                return $this->instance["availBal"];
            } elseif(isset($this->columns["availBal"]["default"])) {
                return $this->columns["availBal"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["availBal"] = func_get_arg(0);
        }
    }

    public function Balance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Balance"])) {
                return $this->instance["Balance"];
            } elseif(isset($this->columns["Balance"]["default"])) {
                return $this->columns["Balance"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["Balance"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

