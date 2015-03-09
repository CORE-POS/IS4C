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
  @class CustdataBackupModel
*/
class CustdataBackupModel extends CustdataModel
{

    protected $name = "custdataBackup";
    protected $preferred_db = 'op';

    public function doc()
    {
        return '
Table: custdataBackup

Columns:
    same as custdata

Depends on:

Use:
Stores an older snapshot of custdata
Easier to pull small bits of info from
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

    public function personNum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["personNum"])) {
                return $this->instance["personNum"];
            } else if (isset($this->columns["personNum"]["default"])) {
                return $this->columns["personNum"]["default"];
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
                'left' => 'personNum',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["personNum"]) || $this->instance["personNum"] != func_get_args(0)) {
                if (!isset($this->columns["personNum"]["ignore_updates"]) || $this->columns["personNum"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["personNum"] = func_get_arg(0);
        }
        return $this;
    }

    public function LastName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["LastName"])) {
                return $this->instance["LastName"];
            } else if (isset($this->columns["LastName"]["default"])) {
                return $this->columns["LastName"]["default"];
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
                'left' => 'LastName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["LastName"]) || $this->instance["LastName"] != func_get_args(0)) {
                if (!isset($this->columns["LastName"]["ignore_updates"]) || $this->columns["LastName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["LastName"] = func_get_arg(0);
        }
        return $this;
    }

    public function FirstName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["FirstName"])) {
                return $this->instance["FirstName"];
            } else if (isset($this->columns["FirstName"]["default"])) {
                return $this->columns["FirstName"]["default"];
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
                'left' => 'FirstName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["FirstName"]) || $this->instance["FirstName"] != func_get_args(0)) {
                if (!isset($this->columns["FirstName"]["ignore_updates"]) || $this->columns["FirstName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["FirstName"] = func_get_arg(0);
        }
        return $this;
    }

    public function CashBack()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["CashBack"])) {
                return $this->instance["CashBack"];
            } else if (isset($this->columns["CashBack"]["default"])) {
                return $this->columns["CashBack"]["default"];
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
                'left' => 'CashBack',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["CashBack"]) || $this->instance["CashBack"] != func_get_args(0)) {
                if (!isset($this->columns["CashBack"]["ignore_updates"]) || $this->columns["CashBack"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["CashBack"] = func_get_arg(0);
        }
        return $this;
    }

    public function Balance()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Balance"])) {
                return $this->instance["Balance"];
            } else if (isset($this->columns["Balance"]["default"])) {
                return $this->columns["Balance"]["default"];
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
                'left' => 'Balance',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["Balance"]) || $this->instance["Balance"] != func_get_args(0)) {
                if (!isset($this->columns["Balance"]["ignore_updates"]) || $this->columns["Balance"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["Balance"] = func_get_arg(0);
        }
        return $this;
    }

    public function Discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Discount"])) {
                return $this->instance["Discount"];
            } else if (isset($this->columns["Discount"]["default"])) {
                return $this->columns["Discount"]["default"];
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
                'left' => 'Discount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["Discount"]) || $this->instance["Discount"] != func_get_args(0)) {
                if (!isset($this->columns["Discount"]["ignore_updates"]) || $this->columns["Discount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["Discount"] = func_get_arg(0);
        }
        return $this;
    }

    public function MemDiscountLimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["MemDiscountLimit"])) {
                return $this->instance["MemDiscountLimit"];
            } else if (isset($this->columns["MemDiscountLimit"]["default"])) {
                return $this->columns["MemDiscountLimit"]["default"];
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
                'left' => 'MemDiscountLimit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["MemDiscountLimit"]) || $this->instance["MemDiscountLimit"] != func_get_args(0)) {
                if (!isset($this->columns["MemDiscountLimit"]["ignore_updates"]) || $this->columns["MemDiscountLimit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["MemDiscountLimit"] = func_get_arg(0);
        }
        return $this;
    }

    public function ChargeLimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ChargeLimit"])) {
                return $this->instance["ChargeLimit"];
            } else if (isset($this->columns["ChargeLimit"]["default"])) {
                return $this->columns["ChargeLimit"]["default"];
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
                'left' => 'ChargeLimit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ChargeLimit"]) || $this->instance["ChargeLimit"] != func_get_args(0)) {
                if (!isset($this->columns["ChargeLimit"]["ignore_updates"]) || $this->columns["ChargeLimit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ChargeLimit"] = func_get_arg(0);
        }
        return $this;
    }

    public function ChargeOk()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ChargeOk"])) {
                return $this->instance["ChargeOk"];
            } else if (isset($this->columns["ChargeOk"]["default"])) {
                return $this->columns["ChargeOk"]["default"];
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
                'left' => 'ChargeOk',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ChargeOk"]) || $this->instance["ChargeOk"] != func_get_args(0)) {
                if (!isset($this->columns["ChargeOk"]["ignore_updates"]) || $this->columns["ChargeOk"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ChargeOk"] = func_get_arg(0);
        }
        return $this;
    }

    public function WriteChecks()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["WriteChecks"])) {
                return $this->instance["WriteChecks"];
            } else if (isset($this->columns["WriteChecks"]["default"])) {
                return $this->columns["WriteChecks"]["default"];
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
                'left' => 'WriteChecks',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["WriteChecks"]) || $this->instance["WriteChecks"] != func_get_args(0)) {
                if (!isset($this->columns["WriteChecks"]["ignore_updates"]) || $this->columns["WriteChecks"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["WriteChecks"] = func_get_arg(0);
        }
        return $this;
    }

    public function StoreCoupons()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["StoreCoupons"])) {
                return $this->instance["StoreCoupons"];
            } else if (isset($this->columns["StoreCoupons"]["default"])) {
                return $this->columns["StoreCoupons"]["default"];
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
                'left' => 'StoreCoupons',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["StoreCoupons"]) || $this->instance["StoreCoupons"] != func_get_args(0)) {
                if (!isset($this->columns["StoreCoupons"]["ignore_updates"]) || $this->columns["StoreCoupons"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["StoreCoupons"] = func_get_arg(0);
        }
        return $this;
    }

    public function Type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Type"])) {
                return $this->instance["Type"];
            } else if (isset($this->columns["Type"]["default"])) {
                return $this->columns["Type"]["default"];
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
                'left' => 'Type',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["Type"]) || $this->instance["Type"] != func_get_args(0)) {
                if (!isset($this->columns["Type"]["ignore_updates"]) || $this->columns["Type"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["Type"] = func_get_arg(0);
        }
        return $this;
    }

    public function memType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memType"])) {
                return $this->instance["memType"];
            } else if (isset($this->columns["memType"]["default"])) {
                return $this->columns["memType"]["default"];
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
                'left' => 'memType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memType"]) || $this->instance["memType"] != func_get_args(0)) {
                if (!isset($this->columns["memType"]["ignore_updates"]) || $this->columns["memType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memType"] = func_get_arg(0);
        }
        return $this;
    }

    public function staff()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["staff"])) {
                return $this->instance["staff"];
            } else if (isset($this->columns["staff"]["default"])) {
                return $this->columns["staff"]["default"];
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
                'left' => 'staff',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["staff"]) || $this->instance["staff"] != func_get_args(0)) {
                if (!isset($this->columns["staff"]["ignore_updates"]) || $this->columns["staff"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["staff"] = func_get_arg(0);
        }
        return $this;
    }

    public function SSI()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["SSI"])) {
                return $this->instance["SSI"];
            } else if (isset($this->columns["SSI"]["default"])) {
                return $this->columns["SSI"]["default"];
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
                'left' => 'SSI',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["SSI"]) || $this->instance["SSI"] != func_get_args(0)) {
                if (!isset($this->columns["SSI"]["ignore_updates"]) || $this->columns["SSI"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["SSI"] = func_get_arg(0);
        }
        return $this;
    }

    public function Purchases()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Purchases"])) {
                return $this->instance["Purchases"];
            } else if (isset($this->columns["Purchases"]["default"])) {
                return $this->columns["Purchases"]["default"];
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
                'left' => 'Purchases',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["Purchases"]) || $this->instance["Purchases"] != func_get_args(0)) {
                if (!isset($this->columns["Purchases"]["ignore_updates"]) || $this->columns["Purchases"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["Purchases"] = func_get_arg(0);
        }
        return $this;
    }

    public function NumberOfChecks()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["NumberOfChecks"])) {
                return $this->instance["NumberOfChecks"];
            } else if (isset($this->columns["NumberOfChecks"]["default"])) {
                return $this->columns["NumberOfChecks"]["default"];
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
                'left' => 'NumberOfChecks',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["NumberOfChecks"]) || $this->instance["NumberOfChecks"] != func_get_args(0)) {
                if (!isset($this->columns["NumberOfChecks"]["ignore_updates"]) || $this->columns["NumberOfChecks"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["NumberOfChecks"] = func_get_arg(0);
        }
        return $this;
    }

    public function memCoupons()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memCoupons"])) {
                return $this->instance["memCoupons"];
            } else if (isset($this->columns["memCoupons"]["default"])) {
                return $this->columns["memCoupons"]["default"];
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
                'left' => 'memCoupons',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memCoupons"]) || $this->instance["memCoupons"] != func_get_args(0)) {
                if (!isset($this->columns["memCoupons"]["ignore_updates"]) || $this->columns["memCoupons"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memCoupons"] = func_get_arg(0);
        }
        return $this;
    }

    public function blueLine()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["blueLine"])) {
                return $this->instance["blueLine"];
            } else if (isset($this->columns["blueLine"]["default"])) {
                return $this->columns["blueLine"]["default"];
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
                'left' => 'blueLine',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["blueLine"]) || $this->instance["blueLine"] != func_get_args(0)) {
                if (!isset($this->columns["blueLine"]["ignore_updates"]) || $this->columns["blueLine"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["blueLine"] = func_get_arg(0);
        }
        return $this;
    }

    public function Shown()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["Shown"])) {
                return $this->instance["Shown"];
            } else if (isset($this->columns["Shown"]["default"])) {
                return $this->columns["Shown"]["default"];
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
                'left' => 'Shown',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["Shown"]) || $this->instance["Shown"] != func_get_args(0)) {
                if (!isset($this->columns["Shown"]["ignore_updates"]) || $this->columns["Shown"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["Shown"] = func_get_arg(0);
        }
        return $this;
    }

    public function LastChange()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["LastChange"])) {
                return $this->instance["LastChange"];
            } else if (isset($this->columns["LastChange"]["default"])) {
                return $this->columns["LastChange"]["default"];
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
                'left' => 'LastChange',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["LastChange"]) || $this->instance["LastChange"] != func_get_args(0)) {
                if (!isset($this->columns["LastChange"]["ignore_updates"]) || $this->columns["LastChange"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["LastChange"] = func_get_arg(0);
        }
        return $this;
    }

    public function id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["id"])) {
                return $this->instance["id"];
            } else if (isset($this->columns["id"]["default"])) {
                return $this->columns["id"]["default"];
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
                'left' => 'id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["id"]) || $this->instance["id"] != func_get_args(0)) {
                if (!isset($this->columns["id"]["ignore_updates"]) || $this->columns["id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["id"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

