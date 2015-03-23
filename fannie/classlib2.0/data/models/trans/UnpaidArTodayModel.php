<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of CORE-POS.

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
class UnpaidArTodayModel extends ViewModel
{

    protected $name = "unpaid_ar_today";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT'),
    'old_balance' => array('type'=>'MONEY'),
    'recent_payments' => array('type'=>'MONEY'),
    'mark' => array('type'=>'INT'),
    );

    public function definition()
    {
        return '
            SELECT u.card_no,
                u.old_balance,
                CASE 
                    WHEN m.card_no IS NULL THEN u.recent_payments 
                    ELSE m.payments+u.recent_payments
                END AS recent_payments,
                CASE 
                    WHEN m.card_no IS NULL THEN 0 
                    ELSE 1 
                END AS mark
            FROM unpaid_ar_balances AS u
                LEFT JOIN ar_history_today_sum AS m ON u.card_no=m.card_no';
    }

    public function doc()
    {
        return '
View: unpaid_ar_today

Columns:
    card_no int
    old_balance (calculated)
    recent_payments (calculated)
    mark (calculated)

Depends on:
    ar_history (table)
    unpaid_ar_balances (view of t.ar_history)
    ar_history_today_sum (view of t.dtransactions via v.dlog)

Depended on by:
  cron/LanePush/UpdateUnpaidAR.php
   to update each lane opdata.unpaid_ar_today.recent_payments

Use:
This view adds payments from the current
day to the view unpaid_ar_balances

The logic is pretty WFC-specific, but the 
general idea is to notify customers that they
have a balance overdue at checkout
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } else if (isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'card_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["card_no"]) || $this->instance["card_no"] != func_get_args(0)) {
                if (!isset($this->columns["card_no"]["ignore_updates"]) || $this->columns["card_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["card_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function old_balance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["old_balance"])) {
                return $this->instance["old_balance"];
            } else if (isset($this->columns["old_balance"]["default"])) {
                return $this->columns["old_balance"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'old_balance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["old_balance"]) || $this->instance["old_balance"] != func_get_args(0)) {
                if (!isset($this->columns["old_balance"]["ignore_updates"]) || $this->columns["old_balance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["old_balance"] = func_get_arg(0);
        }
        return $this;
    }

    public function recent_payments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["recent_payments"])) {
                return $this->instance["recent_payments"];
            } else if (isset($this->columns["recent_payments"]["default"])) {
                return $this->columns["recent_payments"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'recent_payments',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["recent_payments"]) || $this->instance["recent_payments"] != func_get_args(0)) {
                if (!isset($this->columns["recent_payments"]["ignore_updates"]) || $this->columns["recent_payments"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["recent_payments"] = func_get_arg(0);
        }
        return $this;
    }

    public function mark()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mark"])) {
                return $this->instance["mark"];
            } else if (isset($this->columns["mark"]["default"])) {
                return $this->columns["mark"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'mark',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["mark"]) || $this->instance["mark"] != func_get_args(0)) {
                if (!isset($this->columns["mark"]["ignore_updates"]) || $this->columns["mark"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["mark"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

