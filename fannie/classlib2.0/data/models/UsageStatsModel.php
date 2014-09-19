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
  @class UsageStatsModel
*/
class UsageStatsModel extends BasicModel
{

    protected $name = "usageStats";

    protected $columns = array(
    'usageID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'tdate' => array('type'=>'DATETIME'),
    'pageName' => array('type'=>'VARCHAR(100)'),
    'referrer' => array('type'=>'VARCHAR(100)'),
    'userHash' => array('type'=>'VARCHAR(40)'),
    'ipHash' => array('type'=>'VARCHAR(40)'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function usageID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["usageID"])) {
                return $this->instance["usageID"];
            } else if (isset($this->columns["usageID"]["default"])) {
                return $this->columns["usageID"]["default"];
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
                'left' => 'usageID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["usageID"]) || $this->instance["usageID"] != func_get_args(0)) {
                if (!isset($this->columns["usageID"]["ignore_updates"]) || $this->columns["usageID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["usageID"] = func_get_arg(0);
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

    public function pageName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pageName"])) {
                return $this->instance["pageName"];
            } else if (isset($this->columns["pageName"]["default"])) {
                return $this->columns["pageName"]["default"];
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
                'left' => 'pageName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pageName"]) || $this->instance["pageName"] != func_get_args(0)) {
                if (!isset($this->columns["pageName"]["ignore_updates"]) || $this->columns["pageName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pageName"] = func_get_arg(0);
        }
        return $this;
    }

    public function referrer()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["referrer"])) {
                return $this->instance["referrer"];
            } else if (isset($this->columns["referrer"]["default"])) {
                return $this->columns["referrer"]["default"];
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
                'left' => 'referrer',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["referrer"]) || $this->instance["referrer"] != func_get_args(0)) {
                if (!isset($this->columns["referrer"]["ignore_updates"]) || $this->columns["referrer"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["referrer"] = func_get_arg(0);
        }
        return $this;
    }

    public function userHash()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["userHash"])) {
                return $this->instance["userHash"];
            } else if (isset($this->columns["userHash"]["default"])) {
                return $this->columns["userHash"]["default"];
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
                'left' => 'userHash',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["userHash"]) || $this->instance["userHash"] != func_get_args(0)) {
                if (!isset($this->columns["userHash"]["ignore_updates"]) || $this->columns["userHash"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["userHash"] = func_get_arg(0);
        }
        return $this;
    }

    public function ipHash()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ipHash"])) {
                return $this->instance["ipHash"];
            } else if (isset($this->columns["ipHash"]["default"])) {
                return $this->columns["ipHash"]["default"];
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
                'left' => 'ipHash',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ipHash"]) || $this->instance["ipHash"] != func_get_args(0)) {
                if (!isset($this->columns["ipHash"]["ignore_updates"]) || $this->columns["ipHash"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ipHash"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

