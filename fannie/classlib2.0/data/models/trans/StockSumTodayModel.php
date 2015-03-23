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
  @class StockSumTodayModel
*/
class StockSumTodayModel extends ViewModel
{

    protected $name = "stockSumToday";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT'),
    'totPayments' => array('type'=>'MONEY'),
    'startdate' => array('type'=>'DATETIME'),
    );

    public function definition()
    {
        $FANNIE_EQUITY_DEPARTMENTS = FannieConfig::config('EQUITY_DEPARTMENTS', '');
        $ret = preg_match_all('/[0-9]+/', $FANNIE_EQUITY_DEPARTMENTS, $depts);
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
                SUM(CASE WHEN department IN (' . $in . ') THEN total ELSE 0 END) AS totPayments,
                MIN(tdate) AS startdate
            FROM dlog
            WHERE department IN (' . $in . ')
                AND ' . $this->connection->datediff('tdate', $this->connection->now()) . ' = 0
            GROUP BY card_no';
    }

    public function doc()
    {
        return '
View: stockSumToday

Columns:
    card_no int
    totPayments (calculated)
    startdate datetime

Depends on:
    dlog (view)

Use:
This view lists equity activity
for the current day. It exists to 
calculate balances in real time.

The view\'s construction depends on Fannie\'s
Equity Department configuration
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

    public function totPayments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totPayments"])) {
                return $this->instance["totPayments"];
            } else if (isset($this->columns["totPayments"]["default"])) {
                return $this->columns["totPayments"]["default"];
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
                'left' => 'totPayments',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totPayments"]) || $this->instance["totPayments"] != func_get_args(0)) {
                if (!isset($this->columns["totPayments"]["ignore_updates"]) || $this->columns["totPayments"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totPayments"] = func_get_arg(0);
        }
        return $this;
    }

    public function startdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startdate"])) {
                return $this->instance["startdate"];
            } else if (isset($this->columns["startdate"]["default"])) {
                return $this->columns["startdate"]["default"];
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
                'left' => 'startdate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["startdate"]) || $this->instance["startdate"] != func_get_args(0)) {
                if (!isset($this->columns["startdate"]["ignore_updates"]) || $this->columns["startdate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["startdate"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

