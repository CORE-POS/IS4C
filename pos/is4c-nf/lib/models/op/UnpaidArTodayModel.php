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
  @class UnpaidArTodayModel
*/
class UnpaidArTodayModel extends BasicModel
{

    protected $name = "unpaid_ar_today";
    protected $preferred_db = 'op';

    protected $columns = array(
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'old_balance' => array('type'=>'MONEY'),
    'recent_payments' => array('type'=>'MONEY'),
	);

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

    public function old_balance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["old_balance"])) {
                return $this->instance["old_balance"];
            } elseif(isset($this->columns["old_balance"]["default"])) {
                return $this->columns["old_balance"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["old_balance"] = func_get_arg(0);
        }
    }

    public function recent_payments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["recent_payments"])) {
                return $this->instance["recent_payments"];
            } elseif(isset($this->columns["recent_payments"]["default"])) {
                return $this->columns["recent_payments"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["recent_payments"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

