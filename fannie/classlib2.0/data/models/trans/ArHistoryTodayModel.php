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
  @class ArHistoryTodayModel
*/
class ArHistoryTodayModel extends ViewModel
{

    protected $name = "ar_history_today";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'card_no' => array('type'=>'INT'),
    'charges' => array('type'=>'MONEY'),
    'payments' => array('type'=>'MONEY'),
    'tdate' => array('type'=>'DATETIME'),
    'trans_num' => array('type'=>'VARCHAR(50)'),
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
                MAX(tdate) AS tdate,
                trans_num
            FROM dlog
            WHERE (trans_subtype=\'MI\' OR department IN (' . $in . '))
                AND ' . $this->connection->datediff('tdate', $this->connection->now()) . ' = 0
            GROUP BY card_no,
                trans_num';
    }

    public function doc()
    {
        return '
View: ar_history_today

Columns:
    card_no int
    Charges dbms currency
    Payments dbms currency
    tdate datetime
    trans_num varchar

Depends on:
    dlog (view)
    AR departments in Fannie config.

Use:
  In-store charge account activity summary for
   the current day.
  Combine with ar_history
   for a "live" view of account status
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

    public function tdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tdate"])) {
                return $this->instance["tdate"];
            } else if (isset($this->columns["tdate"]["default"])) {
                return $this->columns["tdate"]["default"];
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
                'left' => 'tdate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tdate"]) || $this->instance["tdate"] != func_get_args(0)) {
                if (!isset($this->columns["tdate"]["ignore_updates"]) || $this->columns["tdate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tdate"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_num()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_num"])) {
                return $this->instance["trans_num"];
            } else if (isset($this->columns["trans_num"]["default"])) {
                return $this->columns["trans_num"]["default"];
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
                'left' => 'trans_num',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_num"]) || $this->instance["trans_num"] != func_get_args(0)) {
                if (!isset($this->columns["trans_num"]["ignore_updates"]) || $this->columns["trans_num"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_num"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

