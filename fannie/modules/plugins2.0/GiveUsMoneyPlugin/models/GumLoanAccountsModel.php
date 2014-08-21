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
  @class GumLoanAccountsModel

  This table stores member loans/bonds. The
  fields are pretty straightforward. Note that
  a given member may have multiple loan accounts
  so card_no is not necessarily unique; gumLoanAccountID
  and accountNumber are both unique.

  When loans are paid back, an entry for that check
  is created in GumPayoffs. That table can be joined 
  to this table via GumLoanPayoffMap.
*/
class GumLoanAccountsModel extends BasicModel
{

    protected $name = "GumLoanAccounts";

    protected $columns = array(
    'gumLoanAccountID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'card_no' => array('type'=>'INT', 'index'=>true),
    'accountNumber' => array('type'=>'VARCHAR(25)', 'index'=>true),
    'loanDate' => array('type'=>'datetime'),
    'principal' => array('type'=>'MONEY'),
    'termInMonths' => array('type'=>'INT'),
    'interestRate' => array('type'=>'DOUBLE'),
    );

    protected $unique = array('accountNumber');

    /* START ACCESSOR FUNCTIONS */

    public function gumLoanAccountID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumLoanAccountID"])) {
                return $this->instance["gumLoanAccountID"];
            } else if (isset($this->columns["gumLoanAccountID"]["default"])) {
                return $this->columns["gumLoanAccountID"]["default"];
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
                'left' => 'gumLoanAccountID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["gumLoanAccountID"]) || $this->instance["gumLoanAccountID"] != func_get_args(0)) {
                if (!isset($this->columns["gumLoanAccountID"]["ignore_updates"]) || $this->columns["gumLoanAccountID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumLoanAccountID"] = func_get_arg(0);
        }
        return $this;
    }

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

    public function accountNumber()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["accountNumber"])) {
                return $this->instance["accountNumber"];
            } else if (isset($this->columns["accountNumber"]["default"])) {
                return $this->columns["accountNumber"]["default"];
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
                'left' => 'accountNumber',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["accountNumber"]) || $this->instance["accountNumber"] != func_get_args(0)) {
                if (!isset($this->columns["accountNumber"]["ignore_updates"]) || $this->columns["accountNumber"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["accountNumber"] = func_get_arg(0);
        }
        return $this;
    }

    public function loanDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["loanDate"])) {
                return $this->instance["loanDate"];
            } else if (isset($this->columns["loanDate"]["default"])) {
                return $this->columns["loanDate"]["default"];
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
                'left' => 'loanDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["loanDate"]) || $this->instance["loanDate"] != func_get_args(0)) {
                if (!isset($this->columns["loanDate"]["ignore_updates"]) || $this->columns["loanDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["loanDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function principal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["principal"])) {
                return $this->instance["principal"];
            } else if (isset($this->columns["principal"]["default"])) {
                return $this->columns["principal"]["default"];
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
                'left' => 'principal',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["principal"]) || $this->instance["principal"] != func_get_args(0)) {
                if (!isset($this->columns["principal"]["ignore_updates"]) || $this->columns["principal"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["principal"] = func_get_arg(0);
        }
        return $this;
    }

    public function termInMonths()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["termInMonths"])) {
                return $this->instance["termInMonths"];
            } else if (isset($this->columns["termInMonths"]["default"])) {
                return $this->columns["termInMonths"]["default"];
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
                'left' => 'termInMonths',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["termInMonths"]) || $this->instance["termInMonths"] != func_get_args(0)) {
                if (!isset($this->columns["termInMonths"]["ignore_updates"]) || $this->columns["termInMonths"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["termInMonths"] = func_get_arg(0);
        }
        return $this;
    }

    public function interestRate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["interestRate"])) {
                return $this->instance["interestRate"];
            } else if (isset($this->columns["interestRate"]["default"])) {
                return $this->columns["interestRate"]["default"];
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
                'left' => 'interestRate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["interestRate"]) || $this->instance["interestRate"] != func_get_args(0)) {
                if (!isset($this->columns["interestRate"]["ignore_updates"]) || $this->columns["interestRate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["interestRate"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

