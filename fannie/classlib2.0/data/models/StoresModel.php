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
  @class StoresModel
*/
class StoresModel extends BasicModel
{

    protected $name = "Stores";

    protected $columns = array(
    'storeID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'description' => array('type'=>'VARCHAR(50)'),
    'dbHost' => array('type'=>'VARCHAR(50)'),
    'dbDriver' => array('type'=>'VARCHAR(15)'),
    'dbUser' => array('type'=>'VARCHAR(25)'),
    'dbPassword' => array('type'=>'VARCHAR(25)'),
    'transDB' => array('type'=>'VARCHAR(20)'),
    'opDB' => array('type'=>'VARCHAR(20)'),
    'push' => array('type'=>'TINYINT', 'default'=>1),
    'pull' => array('type'=>'TINYINT', 'default'=>1),
    );

    /* START ACCESSOR FUNCTIONS */

    public function storeID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["storeID"])) {
                return $this->instance["storeID"];
            } else if (isset($this->columns["storeID"]["default"])) {
                return $this->columns["storeID"]["default"];
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
                'left' => 'storeID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["storeID"]) || $this->instance["storeID"] != func_get_args(0)) {
                if (!isset($this->columns["storeID"]["ignore_updates"]) || $this->columns["storeID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["storeID"] = func_get_arg(0);
        }
        return $this;
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } else if (isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
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
                'left' => 'description',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["description"]) || $this->instance["description"] != func_get_args(0)) {
                if (!isset($this->columns["description"]["ignore_updates"]) || $this->columns["description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["description"] = func_get_arg(0);
        }
        return $this;
    }

    public function dbHost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dbHost"])) {
                return $this->instance["dbHost"];
            } else if (isset($this->columns["dbHost"]["default"])) {
                return $this->columns["dbHost"]["default"];
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
                'left' => 'dbHost',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dbHost"]) || $this->instance["dbHost"] != func_get_args(0)) {
                if (!isset($this->columns["dbHost"]["ignore_updates"]) || $this->columns["dbHost"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dbHost"] = func_get_arg(0);
        }
        return $this;
    }

    public function dbDriver()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dbDriver"])) {
                return $this->instance["dbDriver"];
            } else if (isset($this->columns["dbDriver"]["default"])) {
                return $this->columns["dbDriver"]["default"];
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
                'left' => 'dbDriver',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dbDriver"]) || $this->instance["dbDriver"] != func_get_args(0)) {
                if (!isset($this->columns["dbDriver"]["ignore_updates"]) || $this->columns["dbDriver"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dbDriver"] = func_get_arg(0);
        }
        return $this;
    }

    public function dbUser()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dbUser"])) {
                return $this->instance["dbUser"];
            } else if (isset($this->columns["dbUser"]["default"])) {
                return $this->columns["dbUser"]["default"];
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
                'left' => 'dbUser',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dbUser"]) || $this->instance["dbUser"] != func_get_args(0)) {
                if (!isset($this->columns["dbUser"]["ignore_updates"]) || $this->columns["dbUser"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dbUser"] = func_get_arg(0);
        }
        return $this;
    }

    public function dbPassword()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dbPassword"])) {
                return $this->instance["dbPassword"];
            } else if (isset($this->columns["dbPassword"]["default"])) {
                return $this->columns["dbPassword"]["default"];
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
                'left' => 'dbPassword',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dbPassword"]) || $this->instance["dbPassword"] != func_get_args(0)) {
                if (!isset($this->columns["dbPassword"]["ignore_updates"]) || $this->columns["dbPassword"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dbPassword"] = func_get_arg(0);
        }
        return $this;
    }

    public function transDB()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["transDB"])) {
                return $this->instance["transDB"];
            } else if (isset($this->columns["transDB"]["default"])) {
                return $this->columns["transDB"]["default"];
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
                'left' => 'transDB',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["transDB"]) || $this->instance["transDB"] != func_get_args(0)) {
                if (!isset($this->columns["transDB"]["ignore_updates"]) || $this->columns["transDB"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["transDB"] = func_get_arg(0);
        }
        return $this;
    }

    public function opDB()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["opDB"])) {
                return $this->instance["opDB"];
            } else if (isset($this->columns["opDB"]["default"])) {
                return $this->columns["opDB"]["default"];
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
                'left' => 'opDB',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["opDB"]) || $this->instance["opDB"] != func_get_args(0)) {
                if (!isset($this->columns["opDB"]["ignore_updates"]) || $this->columns["opDB"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["opDB"] = func_get_arg(0);
        }
        return $this;
    }

    public function push()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["push"])) {
                return $this->instance["push"];
            } else if (isset($this->columns["push"]["default"])) {
                return $this->columns["push"]["default"];
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
                'left' => 'push',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["push"]) || $this->instance["push"] != func_get_args(0)) {
                if (!isset($this->columns["push"]["ignore_updates"]) || $this->columns["push"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["push"] = func_get_arg(0);
        }
        return $this;
    }

    public function pull()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pull"])) {
                return $this->instance["pull"];
            } else if (isset($this->columns["pull"]["default"])) {
                return $this->columns["pull"]["default"];
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
                'left' => 'pull',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pull"]) || $this->instance["pull"] != func_get_args(0)) {
                if (!isset($this->columns["pull"]["ignore_updates"]) || $this->columns["pull"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pull"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

