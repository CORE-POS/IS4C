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
class ArLiveBalanceModel extends ViewModel 
{

    protected $name = "ar_live_balance";

    protected $columns = array(
    'card_no' => array('type'=>'INT','primary_key'=>True),
    'totcharges' => array('type'=>'MONEY'),
    'totpayments' => array('type'=>'MONEY'),
    'balance' => array('type'=>'MONEY'),
    'mark' => array('type'=>'TINYINT')
    );

    public function definition()
    {
        global $FANNIE_OP_DB;

        return '
            SELECT   
                c.CardNo AS card_no,
                (CASE WHEN a.charges IS NULL THEN 0 ELSE a.charges END)
                    + (CASE WHEN t.charges IS NULL THEN 0 ELSE t.charges END)
                    AS totcharges,
                (CASE WHEN a.payments IS NULL THEN 0 ELSE a.payments END)
                    + (CASE WHEN t.payments IS NULL THEN 0 ELSE t.payments END)
                    AS totpayments,
                (CASE WHEN a.balance IS NULL THEN 0 ELSE a.balance END)
                    + (CASE WHEN t.balance IS NULL THEN 0 ELSE t.balance END)
                    AS balance,
                (CASE WHEN t.card_no IS NULL THEN 0 ELSE 1 END) AS mark
            FROM ' . $FANNIE_OP_DB . $this->connection->sep() . 'custdata as c 
                LEFT JOIN ar_history_sum AS a ON c.CardNo=a.card_no AND c.personNum=1
                LEFT JOIN ar_history_today_sum AS t ON c.CardNo = t.card_no AND c.personNum=1
            WHERE c.personNum=1
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

    public function totcharges()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totcharges"])) {
                return $this->instance["totcharges"];
            } else if (isset($this->columns["totcharges"]["default"])) {
                return $this->columns["totcharges"]["default"];
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
                'left' => 'totcharges',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totcharges"]) || $this->instance["totcharges"] != func_get_args(0)) {
                if (!isset($this->columns["totcharges"]["ignore_updates"]) || $this->columns["totcharges"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totcharges"] = func_get_arg(0);
        }
        return $this;
    }

    public function totpayments()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totpayments"])) {
                return $this->instance["totpayments"];
            } else if (isset($this->columns["totpayments"]["default"])) {
                return $this->columns["totpayments"]["default"];
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
                'left' => 'totpayments',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totpayments"]) || $this->instance["totpayments"] != func_get_args(0)) {
                if (!isset($this->columns["totpayments"]["ignore_updates"]) || $this->columns["totpayments"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totpayments"] = func_get_arg(0);
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

