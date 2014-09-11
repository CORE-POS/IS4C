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
  @class GumDividendModel
*/
class GumDividendsModel extends BasicModel
{

    protected $name = "GumDividends";

    protected $columns = array(
    'gumDividendID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'card_no' => array('type'=>'INT'),
    'yearEndDate' => array('type'=>'DATETIME'),
    'equityAmount' => array('type'=>'MONEY'),
    'daysHeld' => array('type'=>'TINYINT'),
    'dividendRate' => array('type'=>'DOUBLE'),
    'dividendAmount' => array('type'=>'MONEY'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function gumDividendID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumDividendID"])) {
                return $this->instance["gumDividendID"];
            } else if (isset($this->columns["gumDividendID"]["default"])) {
                return $this->columns["gumDividendID"]["default"];
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
                'left' => 'gumDividendID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["gumDividendID"]) || $this->instance["gumDividendID"] != func_get_args(0)) {
                if (!isset($this->columns["gumDividendID"]["ignore_updates"]) || $this->columns["gumDividendID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumDividendID"] = func_get_arg(0);
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

    public function yearEndDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearEndDate"])) {
                return $this->instance["yearEndDate"];
            } else if (isset($this->columns["yearEndDate"]["default"])) {
                return $this->columns["yearEndDate"]["default"];
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
                'left' => 'yearEndDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearEndDate"]) || $this->instance["yearEndDate"] != func_get_args(0)) {
                if (!isset($this->columns["yearEndDate"]["ignore_updates"]) || $this->columns["yearEndDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearEndDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function equityAmount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["equityAmount"])) {
                return $this->instance["equityAmount"];
            } else if (isset($this->columns["equityAmount"]["default"])) {
                return $this->columns["equityAmount"]["default"];
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
                'left' => 'equityAmount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["equityAmount"]) || $this->instance["equityAmount"] != func_get_args(0)) {
                if (!isset($this->columns["equityAmount"]["ignore_updates"]) || $this->columns["equityAmount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["equityAmount"] = func_get_arg(0);
        }
        return $this;
    }

    public function daysHeld()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["daysHeld"])) {
                return $this->instance["daysHeld"];
            } else if (isset($this->columns["daysHeld"]["default"])) {
                return $this->columns["daysHeld"]["default"];
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
                'left' => 'daysHeld',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["daysHeld"]) || $this->instance["daysHeld"] != func_get_args(0)) {
                if (!isset($this->columns["daysHeld"]["ignore_updates"]) || $this->columns["daysHeld"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["daysHeld"] = func_get_arg(0);
        }
        return $this;
    }

    public function dividendRate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dividendRate"])) {
                return $this->instance["dividendRate"];
            } else if (isset($this->columns["dividendRate"]["default"])) {
                return $this->columns["dividendRate"]["default"];
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
                'left' => 'dividendRate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dividendRate"]) || $this->instance["dividendRate"] != func_get_args(0)) {
                if (!isset($this->columns["dividendRate"]["ignore_updates"]) || $this->columns["dividendRate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dividendRate"] = func_get_arg(0);
        }
        return $this;
    }

    public function dividendAmount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dividendAmount"])) {
                return $this->instance["dividendAmount"];
            } else if (isset($this->columns["dividendAmount"]["default"])) {
                return $this->columns["dividendAmount"]["default"];
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
                'left' => 'dividendAmount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dividendAmount"]) || $this->instance["dividendAmount"] != func_get_args(0)) {
                if (!isset($this->columns["dividendAmount"]["ignore_updates"]) || $this->columns["dividendAmount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dividendAmount"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

