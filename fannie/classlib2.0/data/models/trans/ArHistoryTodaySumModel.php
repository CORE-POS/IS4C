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
  @class ArHistoryTodaySumModel
*/
class ArHistoryTodaySumModel extends ViewModel
{

    protected $name = "ar_history_today_sum";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT'),
    'charges' => array('type'=>'MONEY'),
    'payments' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
    );

    public function definition()
    {
        $FANNIE_AR_DEPARTMENTS = FannieConfig::config('AR_DEPARTMENTS', '');
        $ret = preg_match_all('/[0-9]+/', $FANNIE_AR_DEPARTMENTS, $depts);
        if ($ret == 0) {
            $depts = array(-999);
        } else {
            $depts = array_pop($depts);
        }

        $in = '';
        foreach ($depts as $d) {
            $in .= sprintf('%d,', $d);
        }
        $in = substr($in, 0, strlen($in)-1);

        return '
            SELECT card_no,
                SUM(CASE WHEN trans_subtype=\'MI\' THEN -total ELSE 0 END) AS charges,
                SUM(CASE WHEN department IN (' . $in . ') THEN total ELSE 0 END) AS payments,
                SUM(CASE WHEN trans_subtype=\'MI\' THEN -total ELSE 0 END) 
                - SUM(CASE WHEN department IN (' . $in . ') THEN total ELSE 0 END) AS balance
            FROM dlog
            WHERE (trans_subtype=\'MI\' OR department IN (' . $in . '))
                AND ' . $this->connection->datediff('tdate', $this->connection->now()) . ' = 0
            GROUP BY card_no';
    }

    public function doc()
    {
        return '
View: ar_history_today_sum

Columns:
    card_no int
    charges dbms currency
    payments dbms currency
    balance dbms currency

Depends on:
    dlog (view)

Use:
Total charges and payments for the current day
by member number
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

    public function charges()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["charges"])) {
                return $this->instance["charges"];
            } else if (isset($this->columns["charges"]["default"])) {
                return $this->columns["charges"]["default"];
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
                'left' => 'charges',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["charges"]) || $this->instance["charges"] != func_get_args(0)) {
                if (!isset($this->columns["charges"]["ignore_updates"]) || $this->columns["charges"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["charges"] = func_get_arg(0);
        }
        return $this;
    }

    public function payments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["payments"])) {
                return $this->instance["payments"];
            } else if (isset($this->columns["payments"]["default"])) {
                return $this->columns["payments"]["default"];
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
                'left' => 'payments',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["payments"]) || $this->instance["payments"] != func_get_args(0)) {
                if (!isset($this->columns["payments"]["ignore_updates"]) || $this->columns["payments"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["payments"] = func_get_arg(0);
        }
        return $this;
    }

    public function balance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["balance"])) {
                return $this->instance["balance"];
            } else if (isset($this->columns["balance"]["default"])) {
                return $this->columns["balance"]["default"];
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
                'left' => 'balance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["balance"]) || $this->instance["balance"] != func_get_args(0)) {
                if (!isset($this->columns["balance"]["ignore_updates"]) || $this->columns["balance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["balance"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

