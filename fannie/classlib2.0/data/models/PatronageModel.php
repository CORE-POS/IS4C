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
  @class PatronageModel
*/
class PatronageModel extends BasicModel 
{

    protected $name = "patronage";

    protected $preferred_db = 'op';

    protected $columns = array(
    'cardno' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'purchase' => array('type'=>'MONEY'),
    'discounts' => array('type'=>'MONEY'),
    'rewards' => array('type'=>'MONEY'),
    'net_purch' => array('type'=>'MONEY'),
    'tot_pat' => array('type'=>'MONEY'),
    'cash_pat' => array('type'=>'MONEY'),
    'equit_pat' => array('type'=>'MONEY'),
    'FY' => array('type'=>'SMALLINT','primary_key'=>True,'default'=>0)
    );

    /* START ACCESSOR FUNCTIONS */

    public function cardno()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cardno"])) {
                return $this->instance["cardno"];
            } else if (isset($this->columns["cardno"]["default"])) {
                return $this->columns["cardno"]["default"];
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
                'left' => 'cardno',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cardno"]) || $this->instance["cardno"] != func_get_args(0)) {
                if (!isset($this->columns["cardno"]["ignore_updates"]) || $this->columns["cardno"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cardno"] = func_get_arg(0);
        }
        return $this;
    }

    public function purchase()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["purchase"])) {
                return $this->instance["purchase"];
            } else if (isset($this->columns["purchase"]["default"])) {
                return $this->columns["purchase"]["default"];
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
                'left' => 'purchase',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["purchase"]) || $this->instance["purchase"] != func_get_args(0)) {
                if (!isset($this->columns["purchase"]["ignore_updates"]) || $this->columns["purchase"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["purchase"] = func_get_arg(0);
        }
        return $this;
    }

    public function discounts()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discounts"])) {
                return $this->instance["discounts"];
            } else if (isset($this->columns["discounts"]["default"])) {
                return $this->columns["discounts"]["default"];
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
                'left' => 'discounts',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discounts"]) || $this->instance["discounts"] != func_get_args(0)) {
                if (!isset($this->columns["discounts"]["ignore_updates"]) || $this->columns["discounts"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discounts"] = func_get_arg(0);
        }
        return $this;
    }

    public function rewards()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["rewards"])) {
                return $this->instance["rewards"];
            } else if (isset($this->columns["rewards"]["default"])) {
                return $this->columns["rewards"]["default"];
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
                'left' => 'rewards',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["rewards"]) || $this->instance["rewards"] != func_get_args(0)) {
                if (!isset($this->columns["rewards"]["ignore_updates"]) || $this->columns["rewards"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["rewards"] = func_get_arg(0);
        }
        return $this;
    }

    public function net_purch()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["net_purch"])) {
                return $this->instance["net_purch"];
            } else if (isset($this->columns["net_purch"]["default"])) {
                return $this->columns["net_purch"]["default"];
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
                'left' => 'net_purch',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["net_purch"]) || $this->instance["net_purch"] != func_get_args(0)) {
                if (!isset($this->columns["net_purch"]["ignore_updates"]) || $this->columns["net_purch"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["net_purch"] = func_get_arg(0);
        }
        return $this;
    }

    public function tot_pat()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tot_pat"])) {
                return $this->instance["tot_pat"];
            } else if (isset($this->columns["tot_pat"]["default"])) {
                return $this->columns["tot_pat"]["default"];
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
                'left' => 'tot_pat',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tot_pat"]) || $this->instance["tot_pat"] != func_get_args(0)) {
                if (!isset($this->columns["tot_pat"]["ignore_updates"]) || $this->columns["tot_pat"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tot_pat"] = func_get_arg(0);
        }
        return $this;
    }

    public function cash_pat()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cash_pat"])) {
                return $this->instance["cash_pat"];
            } else if (isset($this->columns["cash_pat"]["default"])) {
                return $this->columns["cash_pat"]["default"];
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
                'left' => 'cash_pat',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cash_pat"]) || $this->instance["cash_pat"] != func_get_args(0)) {
                if (!isset($this->columns["cash_pat"]["ignore_updates"]) || $this->columns["cash_pat"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cash_pat"] = func_get_arg(0);
        }
        return $this;
    }

    public function equit_pat()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["equit_pat"])) {
                return $this->instance["equit_pat"];
            } else if (isset($this->columns["equit_pat"]["default"])) {
                return $this->columns["equit_pat"]["default"];
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
                'left' => 'equit_pat',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["equit_pat"]) || $this->instance["equit_pat"] != func_get_args(0)) {
                if (!isset($this->columns["equit_pat"]["ignore_updates"]) || $this->columns["equit_pat"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["equit_pat"] = func_get_arg(0);
        }
        return $this;
    }

    public function FY()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["FY"])) {
                return $this->instance["FY"];
            } else if (isset($this->columns["FY"]["default"])) {
                return $this->columns["FY"]["default"];
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
                'left' => 'FY',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["FY"]) || $this->instance["FY"] != func_get_args(0)) {
                if (!isset($this->columns["FY"]["ignore_updates"]) || $this->columns["FY"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["FY"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

