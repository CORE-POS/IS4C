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
  @class OriginsModel
*/
class OriginsModel extends BasicModel
{

    protected $name = "origins";

    protected $columns = array(
    'originID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'countryID' => array('type'=>'INT'),
    'stateProvID' => array('type'=>'INT'),
    'customID' => array('type'=>'INT'),
    'local' => array('type'=>'TINYINT', 'default'=>0),
    'name' => array('type'=>'VARCHAR(100)'),
    'shortName' => array('type'=>'VARCHAR(50)'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function originID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["originID"])) {
                return $this->instance["originID"];
            } else if (isset($this->columns["originID"]["default"])) {
                return $this->columns["originID"]["default"];
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
                'left' => 'originID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["originID"]) || $this->instance["originID"] != func_get_args(0)) {
                if (!isset($this->columns["originID"]["ignore_updates"]) || $this->columns["originID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["originID"] = func_get_arg(0);
        }
        return $this;
    }

    public function countryID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["countryID"])) {
                return $this->instance["countryID"];
            } else if (isset($this->columns["countryID"]["default"])) {
                return $this->columns["countryID"]["default"];
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
                'left' => 'countryID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["countryID"]) || $this->instance["countryID"] != func_get_args(0)) {
                if (!isset($this->columns["countryID"]["ignore_updates"]) || $this->columns["countryID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["countryID"] = func_get_arg(0);
        }
        return $this;
    }

    public function stateProvID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["stateProvID"])) {
                return $this->instance["stateProvID"];
            } else if (isset($this->columns["stateProvID"]["default"])) {
                return $this->columns["stateProvID"]["default"];
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
                'left' => 'stateProvID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["stateProvID"]) || $this->instance["stateProvID"] != func_get_args(0)) {
                if (!isset($this->columns["stateProvID"]["ignore_updates"]) || $this->columns["stateProvID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["stateProvID"] = func_get_arg(0);
        }
        return $this;
    }

    public function customID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["customID"])) {
                return $this->instance["customID"];
            } else if (isset($this->columns["customID"]["default"])) {
                return $this->columns["customID"]["default"];
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
                'left' => 'customID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["customID"]) || $this->instance["customID"] != func_get_args(0)) {
                if (!isset($this->columns["customID"]["ignore_updates"]) || $this->columns["customID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["customID"] = func_get_arg(0);
        }
        return $this;
    }

    public function local()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["local"])) {
                return $this->instance["local"];
            } else if (isset($this->columns["local"]["default"])) {
                return $this->columns["local"]["default"];
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
                'left' => 'local',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["local"]) || $this->instance["local"] != func_get_args(0)) {
                if (!isset($this->columns["local"]["ignore_updates"]) || $this->columns["local"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["local"] = func_get_arg(0);
        }
        return $this;
    }

    public function name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["name"])) {
                return $this->instance["name"];
            } else if (isset($this->columns["name"]["default"])) {
                return $this->columns["name"]["default"];
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
                'left' => 'name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["name"]) || $this->instance["name"] != func_get_args(0)) {
                if (!isset($this->columns["name"]["ignore_updates"]) || $this->columns["name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["name"] = func_get_arg(0);
        }
        return $this;
    }

    public function shortName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["shortName"])) {
                return $this->instance["shortName"];
            } else if (isset($this->columns["shortName"]["default"])) {
                return $this->columns["shortName"]["default"];
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
                'left' => 'shortName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["shortName"]) || $this->instance["shortName"] != func_get_args(0)) {
                if (!isset($this->columns["shortName"]["ignore_updates"]) || $this->columns["shortName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["shortName"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

