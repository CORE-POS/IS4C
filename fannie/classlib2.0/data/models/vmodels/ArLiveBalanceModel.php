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
  @class ArLiveBalanceModel
*/
class ArLiveBalanceModel extends BasicModel 
{

    protected $name = "ar_live_balance";

    protected $columns = array(
    'card_no' => array('type'=>'INT','primary_key'=>True),
    'totcharges' => array('type'=>'MONEY'),
    'totpayments' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
    'mark' => array('type'=>'TINYINT')
    );

    public function create(){ return false; }
    public function delete(){ return false; }
    public function save(){ return false; }
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false){ return 0; }

    /* START ACCESSOR FUNCTIONS */

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } elseif(isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["card_no"] = func_get_arg(0);
        }
    }

    public function totcharges()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totcharges"])) {
                return $this->instance["totcharges"];
            } elseif(isset($this->columns["totcharges"]["default"])) {
                return $this->columns["totcharges"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["totcharges"] = func_get_arg(0);
        }
    }

    public function totpayments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totpayments"])) {
                return $this->instance["totpayments"];
            } elseif(isset($this->columns["totpayments"]["default"])) {
                return $this->columns["totpayments"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["totpayments"] = func_get_arg(0);
        }
    }

    public function balance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["balance"])) {
                return $this->instance["balance"];
            } elseif(isset($this->columns["balance"]["default"])) {
                return $this->columns["balance"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["balance"] = func_get_arg(0);
        }
    }

    public function mark()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mark"])) {
                return $this->instance["mark"];
            } elseif(isset($this->columns["mark"]["default"])) {
                return $this->columns["mark"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["mark"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

