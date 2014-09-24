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
  @class CustomerAccountSuspensionsModel
*/
class CustomerAccountSuspensionsModel extends BasicModel
{

    protected $name = "CustomerAccountSuspensions";

    protected $columns = array(
    'customerAccountSuspensionID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'card_no' => array('type'=>'INT', 'index'=>true),
    'active' => array('type'=>'TINYINT', 'index'=>true),
    'tdate' => array('type'=>'DATETIME'),
    'suspensionTypeID' => array('type'=>'SMALLINT'),
    'reasonCode' => array('type'=>'INT'),
    'legacyReason' => array('type'=>'TEXT'),
    'username' => array('type'=>'VARCHAR(50)'),
    'savedType' => array('type'=>'VARCHAR(10)'),
    'savedMemType' => array('type'=>'SMALLINT'),
    'savedDiscount' => array('type'=>'SMALLINT'),
    'savedChargeLimit' => array('type'=>'MONEY'),
    'savedMailFlag' => array('type'=>'TINYINT'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function customerAccountSuspensionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["customerAccountSuspensionID"])) {
                return $this->instance["customerAccountSuspensionID"];
            } else if (isset($this->columns["customerAccountSuspensionID"]["default"])) {
                return $this->columns["customerAccountSuspensionID"]["default"];
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
                'left' => 'customerAccountSuspensionID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["customerAccountSuspensionID"]) || $this->instance["customerAccountSuspensionID"] != func_get_args(0)) {
                if (!isset($this->columns["customerAccountSuspensionID"]["ignore_updates"]) || $this->columns["customerAccountSuspensionID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["customerAccountSuspensionID"] = func_get_arg(0);
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

    public function active()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["active"])) {
                return $this->instance["active"];
            } else if (isset($this->columns["active"]["default"])) {
                return $this->columns["active"]["default"];
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
                'left' => 'active',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["active"]) || $this->instance["active"] != func_get_args(0)) {
                if (!isset($this->columns["active"]["ignore_updates"]) || $this->columns["active"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["active"] = func_get_arg(0);
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

    public function suspensionTypeID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["suspensionTypeID"])) {
                return $this->instance["suspensionTypeID"];
            } else if (isset($this->columns["suspensionTypeID"]["default"])) {
                return $this->columns["suspensionTypeID"]["default"];
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
                'left' => 'suspensionTypeID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["suspensionTypeID"]) || $this->instance["suspensionTypeID"] != func_get_args(0)) {
                if (!isset($this->columns["suspensionTypeID"]["ignore_updates"]) || $this->columns["suspensionTypeID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["suspensionTypeID"] = func_get_arg(0);
        }
        return $this;
    }

    public function reasonCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reasonCode"])) {
                return $this->instance["reasonCode"];
            } else if (isset($this->columns["reasonCode"]["default"])) {
                return $this->columns["reasonCode"]["default"];
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
                'left' => 'reasonCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reasonCode"]) || $this->instance["reasonCode"] != func_get_args(0)) {
                if (!isset($this->columns["reasonCode"]["ignore_updates"]) || $this->columns["reasonCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reasonCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function legacyReason()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["legacyReason"])) {
                return $this->instance["legacyReason"];
            } else if (isset($this->columns["legacyReason"]["default"])) {
                return $this->columns["legacyReason"]["default"];
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
                'left' => 'legacyReason',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["legacyReason"]) || $this->instance["legacyReason"] != func_get_args(0)) {
                if (!isset($this->columns["legacyReason"]["ignore_updates"]) || $this->columns["legacyReason"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["legacyReason"] = func_get_arg(0);
        }
        return $this;
    }

    public function username()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["username"])) {
                return $this->instance["username"];
            } else if (isset($this->columns["username"]["default"])) {
                return $this->columns["username"]["default"];
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
                'left' => 'username',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["username"]) || $this->instance["username"] != func_get_args(0)) {
                if (!isset($this->columns["username"]["ignore_updates"]) || $this->columns["username"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["username"] = func_get_arg(0);
        }
        return $this;
    }

    public function savedType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["savedType"])) {
                return $this->instance["savedType"];
            } else if (isset($this->columns["savedType"]["default"])) {
                return $this->columns["savedType"]["default"];
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
                'left' => 'savedType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["savedType"]) || $this->instance["savedType"] != func_get_args(0)) {
                if (!isset($this->columns["savedType"]["ignore_updates"]) || $this->columns["savedType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["savedType"] = func_get_arg(0);
        }
        return $this;
    }

    public function savedMemType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["savedMemType"])) {
                return $this->instance["savedMemType"];
            } else if (isset($this->columns["savedMemType"]["default"])) {
                return $this->columns["savedMemType"]["default"];
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
                'left' => 'savedMemType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["savedMemType"]) || $this->instance["savedMemType"] != func_get_args(0)) {
                if (!isset($this->columns["savedMemType"]["ignore_updates"]) || $this->columns["savedMemType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["savedMemType"] = func_get_arg(0);
        }
        return $this;
    }

    public function savedDiscount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["savedDiscount"])) {
                return $this->instance["savedDiscount"];
            } else if (isset($this->columns["savedDiscount"]["default"])) {
                return $this->columns["savedDiscount"]["default"];
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
                'left' => 'savedDiscount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["savedDiscount"]) || $this->instance["savedDiscount"] != func_get_args(0)) {
                if (!isset($this->columns["savedDiscount"]["ignore_updates"]) || $this->columns["savedDiscount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["savedDiscount"] = func_get_arg(0);
        }
        return $this;
    }

    public function savedChargeLimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["savedChargeLimit"])) {
                return $this->instance["savedChargeLimit"];
            } else if (isset($this->columns["savedChargeLimit"]["default"])) {
                return $this->columns["savedChargeLimit"]["default"];
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
                'left' => 'savedChargeLimit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["savedChargeLimit"]) || $this->instance["savedChargeLimit"] != func_get_args(0)) {
                if (!isset($this->columns["savedChargeLimit"]["ignore_updates"]) || $this->columns["savedChargeLimit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["savedChargeLimit"] = func_get_arg(0);
        }
        return $this;
    }

    public function savedMailFlag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["savedMailFlag"])) {
                return $this->instance["savedMailFlag"];
            } else if (isset($this->columns["savedMailFlag"]["default"])) {
                return $this->columns["savedMailFlag"]["default"];
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
                'left' => 'savedMailFlag',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["savedMailFlag"]) || $this->instance["savedMailFlag"] != func_get_args(0)) {
                if (!isset($this->columns["savedMailFlag"]["ignore_updates"]) || $this->columns["savedMailFlag"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["savedMailFlag"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

