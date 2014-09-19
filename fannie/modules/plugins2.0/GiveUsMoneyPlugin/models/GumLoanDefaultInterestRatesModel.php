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
  @class GumLoanDefaultInterestRatesModel

  This table defines default interest rates for
  loans with a principal amount between the upper
  and lower bounds.
*/
class GumLoanDefaultInterestRatesModel extends BasicModel
{

    protected $name = "GumLoanDefaultInterestRates";

    protected $columns = array(
    'gumLoanDefaultInterestRateID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'lowerBound' => array('type'=>'MONEY', 'default'=>0),
    'upperBound' => array('type'=>'MONEY', 'default'=>99999999.99),
    'interestRate' => array('type'=>'DOUBLE'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function gumLoanDefaultInterestRateID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumLoanDefaultInterestRateID"])) {
                return $this->instance["gumLoanDefaultInterestRateID"];
            } else if (isset($this->columns["gumLoanDefaultInterestRateID"]["default"])) {
                return $this->columns["gumLoanDefaultInterestRateID"]["default"];
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
                'left' => 'gumLoanDefaultInterestRateID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["gumLoanDefaultInterestRateID"]) || $this->instance["gumLoanDefaultInterestRateID"] != func_get_args(0)) {
                if (!isset($this->columns["gumLoanDefaultInterestRateID"]["ignore_updates"]) || $this->columns["gumLoanDefaultInterestRateID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumLoanDefaultInterestRateID"] = func_get_arg(0);
        }
        return $this;
    }

    public function lowerBound()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lowerBound"])) {
                return $this->instance["lowerBound"];
            } else if (isset($this->columns["lowerBound"]["default"])) {
                return $this->columns["lowerBound"]["default"];
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
                'left' => 'lowerBound',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lowerBound"]) || $this->instance["lowerBound"] != func_get_args(0)) {
                if (!isset($this->columns["lowerBound"]["ignore_updates"]) || $this->columns["lowerBound"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lowerBound"] = func_get_arg(0);
        }
        return $this;
    }

    public function upperBound()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upperBound"])) {
                return $this->instance["upperBound"];
            } else if (isset($this->columns["upperBound"]["default"])) {
                return $this->columns["upperBound"]["default"];
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
                'left' => 'upperBound',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["upperBound"]) || $this->instance["upperBound"] != func_get_args(0)) {
                if (!isset($this->columns["upperBound"]["ignore_updates"]) || $this->columns["upperBound"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["upperBound"] = func_get_arg(0);
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

