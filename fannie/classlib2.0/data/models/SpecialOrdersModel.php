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
  @class SpecialOrdersModel
*/
class SpecialOrdersModel extends BasicModel
{

    protected $name = "SpecialOrders";

    protected $preferred_db = 'trans';

    protected $columns = array(
    'specialOrderID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'statusFlag' => array('type'=>'INT'),
    'subStatus' => array('type'=>'INT'),
    'notes' => array('type'=>'TEXT'),
    'noteSuperID' => array('type'=>'INT'),
    'firstName' => array('type'=>'VARCHAR(30)'),
    'lastName' => array('type'=>'VARCHAR(30)'),
    'street' => array('type'=>'VARCHAR(255)'),
    'city' => array('type'=>'VARCHAR(20)'),
    'state' => array('type'=>'VARCHAR(2)'),
    'zip' => array('type'=>'VARCHAR(10)'),
    'phone' => array('type'=>'VARCHAR(30)'),
    'altPhone' => array('type'=>'VARCHAR(30)'),
    'email' => array('type'=>'VARCHAR(50)'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function specialOrderID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["specialOrderID"])) {
                return $this->instance["specialOrderID"];
            } else if (isset($this->columns["specialOrderID"]["default"])) {
                return $this->columns["specialOrderID"]["default"];
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
                'left' => 'specialOrderID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["specialOrderID"]) || $this->instance["specialOrderID"] != func_get_args(0)) {
                if (!isset($this->columns["specialOrderID"]["ignore_updates"]) || $this->columns["specialOrderID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["specialOrderID"] = func_get_arg(0);
        }
        return $this;
    }

    public function statusFlag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["statusFlag"])) {
                return $this->instance["statusFlag"];
            } else if (isset($this->columns["statusFlag"]["default"])) {
                return $this->columns["statusFlag"]["default"];
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
                'left' => 'statusFlag',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["statusFlag"]) || $this->instance["statusFlag"] != func_get_args(0)) {
                if (!isset($this->columns["statusFlag"]["ignore_updates"]) || $this->columns["statusFlag"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["statusFlag"] = func_get_arg(0);
        }
        return $this;
    }

    public function subStatus()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["subStatus"])) {
                return $this->instance["subStatus"];
            } else if (isset($this->columns["subStatus"]["default"])) {
                return $this->columns["subStatus"]["default"];
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
                'left' => 'subStatus',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["subStatus"]) || $this->instance["subStatus"] != func_get_args(0)) {
                if (!isset($this->columns["subStatus"]["ignore_updates"]) || $this->columns["subStatus"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["subStatus"] = func_get_arg(0);
        }
        return $this;
    }

    public function notes()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["notes"])) {
                return $this->instance["notes"];
            } else if (isset($this->columns["notes"]["default"])) {
                return $this->columns["notes"]["default"];
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
                'left' => 'notes',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["notes"]) || $this->instance["notes"] != func_get_args(0)) {
                if (!isset($this->columns["notes"]["ignore_updates"]) || $this->columns["notes"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["notes"] = func_get_arg(0);
        }
        return $this;
    }

    public function noteSuperID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["noteSuperID"])) {
                return $this->instance["noteSuperID"];
            } else if (isset($this->columns["noteSuperID"]["default"])) {
                return $this->columns["noteSuperID"]["default"];
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
                'left' => 'noteSuperID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["noteSuperID"]) || $this->instance["noteSuperID"] != func_get_args(0)) {
                if (!isset($this->columns["noteSuperID"]["ignore_updates"]) || $this->columns["noteSuperID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["noteSuperID"] = func_get_arg(0);
        }
        return $this;
    }

    public function firstName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["firstName"])) {
                return $this->instance["firstName"];
            } else if (isset($this->columns["firstName"]["default"])) {
                return $this->columns["firstName"]["default"];
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
                'left' => 'firstName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["firstName"]) || $this->instance["firstName"] != func_get_args(0)) {
                if (!isset($this->columns["firstName"]["ignore_updates"]) || $this->columns["firstName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["firstName"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastName"])) {
                return $this->instance["lastName"];
            } else if (isset($this->columns["lastName"]["default"])) {
                return $this->columns["lastName"]["default"];
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
                'left' => 'lastName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastName"]) || $this->instance["lastName"] != func_get_args(0)) {
                if (!isset($this->columns["lastName"]["ignore_updates"]) || $this->columns["lastName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastName"] = func_get_arg(0);
        }
        return $this;
    }

    public function street()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["street"])) {
                return $this->instance["street"];
            } else if (isset($this->columns["street"]["default"])) {
                return $this->columns["street"]["default"];
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
                'left' => 'street',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["street"]) || $this->instance["street"] != func_get_args(0)) {
                if (!isset($this->columns["street"]["ignore_updates"]) || $this->columns["street"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["street"] = func_get_arg(0);
        }
        return $this;
    }

    public function city()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["city"])) {
                return $this->instance["city"];
            } else if (isset($this->columns["city"]["default"])) {
                return $this->columns["city"]["default"];
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
                'left' => 'city',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["city"]) || $this->instance["city"] != func_get_args(0)) {
                if (!isset($this->columns["city"]["ignore_updates"]) || $this->columns["city"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["city"] = func_get_arg(0);
        }
        return $this;
    }

    public function state()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["state"])) {
                return $this->instance["state"];
            } else if (isset($this->columns["state"]["default"])) {
                return $this->columns["state"]["default"];
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
                'left' => 'state',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["state"]) || $this->instance["state"] != func_get_args(0)) {
                if (!isset($this->columns["state"]["ignore_updates"]) || $this->columns["state"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["state"] = func_get_arg(0);
        }
        return $this;
    }

    public function zip()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["zip"])) {
                return $this->instance["zip"];
            } else if (isset($this->columns["zip"]["default"])) {
                return $this->columns["zip"]["default"];
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
                'left' => 'zip',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["zip"]) || $this->instance["zip"] != func_get_args(0)) {
                if (!isset($this->columns["zip"]["ignore_updates"]) || $this->columns["zip"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["zip"] = func_get_arg(0);
        }
        return $this;
    }

    public function phone()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["phone"])) {
                return $this->instance["phone"];
            } else if (isset($this->columns["phone"]["default"])) {
                return $this->columns["phone"]["default"];
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
                'left' => 'phone',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["phone"]) || $this->instance["phone"] != func_get_args(0)) {
                if (!isset($this->columns["phone"]["ignore_updates"]) || $this->columns["phone"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["phone"] = func_get_arg(0);
        }
        return $this;
    }

    public function altPhone()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["altPhone"])) {
                return $this->instance["altPhone"];
            } else if (isset($this->columns["altPhone"]["default"])) {
                return $this->columns["altPhone"]["default"];
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
                'left' => 'altPhone',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["altPhone"]) || $this->instance["altPhone"] != func_get_args(0)) {
                if (!isset($this->columns["altPhone"]["ignore_updates"]) || $this->columns["altPhone"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["altPhone"] = func_get_arg(0);
        }
        return $this;
    }

    public function email()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["email"])) {
                return $this->instance["email"];
            } else if (isset($this->columns["email"]["default"])) {
                return $this->columns["email"]["default"];
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
                'left' => 'email',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["email"]) || $this->instance["email"] != func_get_args(0)) {
                if (!isset($this->columns["email"]["ignore_updates"]) || $this->columns["email"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["email"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

