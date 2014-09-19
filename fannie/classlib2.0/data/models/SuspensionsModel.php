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
  @class SuspensionsModel
*/
class SuspensionsModel extends BasicModel 
{

    protected $name = "suspensions";

    protected $preferred_db = 'op';

    protected $columns = array(
    'cardno' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'type' => array('type'=>'CHAR(1)'),
    'memtype1' => array('type'=>'INT'),
    'memtype2' => array('type'=>'VARCHAR(6)'),
    'suspDate' => array('type'=>'DATETIME'),
    'reason' => array('type'=>'TEXT'),
    'mailflag' => array('type'=>'INT'),
    'discount' => array('type'=>'INT'),
    'chargelimit' => array('type'=>'MONEY'),
    'reasoncode' => array('type'=>'INT')
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

    public function type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["type"])) {
                return $this->instance["type"];
            } else if (isset($this->columns["type"]["default"])) {
                return $this->columns["type"]["default"];
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
                'left' => 'type',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["type"]) || $this->instance["type"] != func_get_args(0)) {
                if (!isset($this->columns["type"]["ignore_updates"]) || $this->columns["type"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["type"] = func_get_arg(0);
        }
        return $this;
    }

    public function memtype1()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memtype1"])) {
                return $this->instance["memtype1"];
            } else if (isset($this->columns["memtype1"]["default"])) {
                return $this->columns["memtype1"]["default"];
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
                'left' => 'memtype1',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memtype1"]) || $this->instance["memtype1"] != func_get_args(0)) {
                if (!isset($this->columns["memtype1"]["ignore_updates"]) || $this->columns["memtype1"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memtype1"] = func_get_arg(0);
        }
        return $this;
    }

    public function memtype2()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memtype2"])) {
                return $this->instance["memtype2"];
            } else if (isset($this->columns["memtype2"]["default"])) {
                return $this->columns["memtype2"]["default"];
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
                'left' => 'memtype2',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memtype2"]) || $this->instance["memtype2"] != func_get_args(0)) {
                if (!isset($this->columns["memtype2"]["ignore_updates"]) || $this->columns["memtype2"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memtype2"] = func_get_arg(0);
        }
        return $this;
    }

    public function suspDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["suspDate"])) {
                return $this->instance["suspDate"];
            } else if (isset($this->columns["suspDate"]["default"])) {
                return $this->columns["suspDate"]["default"];
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
                'left' => 'suspDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["suspDate"]) || $this->instance["suspDate"] != func_get_args(0)) {
                if (!isset($this->columns["suspDate"]["ignore_updates"]) || $this->columns["suspDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["suspDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function reason()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reason"])) {
                return $this->instance["reason"];
            } else if (isset($this->columns["reason"]["default"])) {
                return $this->columns["reason"]["default"];
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
                'left' => 'reason',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reason"]) || $this->instance["reason"] != func_get_args(0)) {
                if (!isset($this->columns["reason"]["ignore_updates"]) || $this->columns["reason"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reason"] = func_get_arg(0);
        }
        return $this;
    }

    public function mailflag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mailflag"])) {
                return $this->instance["mailflag"];
            } else if (isset($this->columns["mailflag"]["default"])) {
                return $this->columns["mailflag"]["default"];
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
                'left' => 'mailflag',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["mailflag"]) || $this->instance["mailflag"] != func_get_args(0)) {
                if (!isset($this->columns["mailflag"]["ignore_updates"]) || $this->columns["mailflag"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["mailflag"] = func_get_arg(0);
        }
        return $this;
    }

    public function discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discount"])) {
                return $this->instance["discount"];
            } else if (isset($this->columns["discount"]["default"])) {
                return $this->columns["discount"]["default"];
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
                'left' => 'discount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discount"]) || $this->instance["discount"] != func_get_args(0)) {
                if (!isset($this->columns["discount"]["ignore_updates"]) || $this->columns["discount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discount"] = func_get_arg(0);
        }
        return $this;
    }

    public function chargelimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["chargelimit"])) {
                return $this->instance["chargelimit"];
            } else if (isset($this->columns["chargelimit"]["default"])) {
                return $this->columns["chargelimit"]["default"];
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
                'left' => 'chargelimit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["chargelimit"]) || $this->instance["chargelimit"] != func_get_args(0)) {
                if (!isset($this->columns["chargelimit"]["ignore_updates"]) || $this->columns["chargelimit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["chargelimit"] = func_get_arg(0);
        }
        return $this;
    }

    public function reasoncode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reasoncode"])) {
                return $this->instance["reasoncode"];
            } else if (isset($this->columns["reasoncode"]["default"])) {
                return $this->columns["reasoncode"]["default"];
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
                'left' => 'reasoncode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reasoncode"]) || $this->instance["reasoncode"] != func_get_args(0)) {
                if (!isset($this->columns["reasoncode"]["ignore_updates"]) || $this->columns["reasoncode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reasoncode"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

