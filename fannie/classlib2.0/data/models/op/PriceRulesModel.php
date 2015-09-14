<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class PriceRulesModel
*/
class PriceRulesModel extends BasicModel
{

    protected $name = "PriceRules";

    protected $columns = array(
    'priceRuleID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'priceRuleTypeID' => array('type'=>'INT'),
    'minMargin' => array('type'=>'DOUBLE', 'default'=>0),
    'maxPrice' => array('type'=>'DOUBLE', 'default'=>0),
    'reviewDate' => array('type'=>'DATETIME'),
    'details' => array('type'=>'TEXT'),
    );


    /* START ACCESSOR FUNCTIONS */

    public function priceRuleID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["priceRuleID"])) {
                return $this->instance["priceRuleID"];
            } else if (isset($this->columns["priceRuleID"]["default"])) {
                return $this->columns["priceRuleID"]["default"];
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
                'left' => 'priceRuleID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["priceRuleID"]) || $this->instance["priceRuleID"] != func_get_args(0)) {
                if (!isset($this->columns["priceRuleID"]["ignore_updates"]) || $this->columns["priceRuleID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["priceRuleID"] = func_get_arg(0);
        }
        return $this;
    }

    public function priceRuleTypeID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["priceRuleTypeID"])) {
                return $this->instance["priceRuleTypeID"];
            } else if (isset($this->columns["priceRuleTypeID"]["default"])) {
                return $this->columns["priceRuleTypeID"]["default"];
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
                'left' => 'priceRuleTypeID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["priceRuleTypeID"]) || $this->instance["priceRuleTypeID"] != func_get_args(0)) {
                if (!isset($this->columns["priceRuleTypeID"]["ignore_updates"]) || $this->columns["priceRuleTypeID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["priceRuleTypeID"] = func_get_arg(0);
        }
        return $this;
    }

    public function minMargin()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["minMargin"])) {
                return $this->instance["minMargin"];
            } else if (isset($this->columns["minMargin"]["default"])) {
                return $this->columns["minMargin"]["default"];
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
                'left' => 'minMargin',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["minMargin"]) || $this->instance["minMargin"] != func_get_args(0)) {
                if (!isset($this->columns["minMargin"]["ignore_updates"]) || $this->columns["minMargin"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["minMargin"] = func_get_arg(0);
        }
        return $this;
    }

    public function maxPrice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["maxPrice"])) {
                return $this->instance["maxPrice"];
            } else if (isset($this->columns["maxPrice"]["default"])) {
                return $this->columns["maxPrice"]["default"];
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
                'left' => 'maxPrice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["maxPrice"]) || $this->instance["maxPrice"] != func_get_args(0)) {
                if (!isset($this->columns["maxPrice"]["ignore_updates"]) || $this->columns["maxPrice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["maxPrice"] = func_get_arg(0);
        }
        return $this;
    }

    public function reviewDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reviewDate"])) {
                return $this->instance["reviewDate"];
            } else if (isset($this->columns["reviewDate"]["default"])) {
                return $this->columns["reviewDate"]["default"];
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
                'left' => 'reviewDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reviewDate"]) || $this->instance["reviewDate"] != func_get_args(0)) {
                if (!isset($this->columns["reviewDate"]["ignore_updates"]) || $this->columns["reviewDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reviewDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function details()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["details"])) {
                return $this->instance["details"];
            } else if (isset($this->columns["details"]["default"])) {
                return $this->columns["details"]["default"];
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
                'left' => 'details',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["details"]) || $this->instance["details"] != func_get_args(0)) {
                if (!isset($this->columns["details"]["ignore_updates"]) || $this->columns["details"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["details"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

