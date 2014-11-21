<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
class MemChargeBalanceModel extends SpanningViewModel
{

    protected $name = "memChargeBalance";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'CardNo' => array('type'=>'INT'),
    'availBal' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
    'mark' => array('type'=>'INT'),
    );

    public function definition()
    {
        $custdata = $this->findExtraTable('custdata');
        if ($custdata === false) {
            return parent::definition();
        }

        return '
        SELECT c.CardNo, 
            CASE 
                WHEN a.balance IS NULL THEN c.ChargeLimit
                ELSE c.ChargeLimit - a.balance END
            AS availBal,
            CASE WHEN a.balance is NULL THEN 0 ELSE a.balance END AS balance,
            CASE WHEN a.mark IS NULL THEN 0 ELSE a.mark END AS mark   
        FROM ' . $custdata  . ' AS c 
            LEFT JOIN ar_live_balance AS a ON c.CardNo = a.card_no
        WHERE c.personNum = 1';
    }
    
    public function doc()
    {
        return '
View: memChargeBalance

Columns:
    CardNo int
    availBal (calculated) 
    balance (calculated)
    mark (calculated)

Depends on:
    core_op.custdata (table)
    ar_live_balance (view of t.dtransactions -> .v.dlog)

Use:
This view lists real-time store charge
 balances by membership.
This view gets pushed to the lanes as a table
 to speed things up
The "mark" column indicates an account
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function CardNo()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["CardNo"])) {
                return $this->instance["CardNo"];
            } else if (isset($this->columns["CardNo"]["default"])) {
                return $this->columns["CardNo"]["default"];
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
                'left' => 'CardNo',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["CardNo"]) || $this->instance["CardNo"] != func_get_args(0)) {
                if (!isset($this->columns["CardNo"]["ignore_updates"]) || $this->columns["CardNo"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["CardNo"] = func_get_arg(0);
        }
        return $this;
    }

    public function availBal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["availBal"])) {
                return $this->instance["availBal"];
            } else if (isset($this->columns["availBal"]["default"])) {
                return $this->columns["availBal"]["default"];
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
                'left' => 'availBal',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["availBal"]) || $this->instance["availBal"] != func_get_args(0)) {
                if (!isset($this->columns["availBal"]["ignore_updates"]) || $this->columns["availBal"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["availBal"] = func_get_arg(0);
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

