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
  @class WfcHtEvalCommentsModel
*/
class WfcHtEvalCommentsModel extends BasicModel
{

    protected $name = "evalComments";

    protected $columns = array(
    'id' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'empID' => array('type'=>'INT'),
    'comment' => array('type'=>'TEXT'),
    'stamp' => array('type'=>'DATETIME'),
    'user' => array('type'=>'VARCHAR(50)'),
    'deleted' => array('type'=>'TINYINT'),
    );

    /* START ACCESSOR FUNCTIONS */

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

    public function empID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["empID"])) {
                return $this->instance["empID"];
            } else if (isset($this->columns["empID"]["default"])) {
                return $this->columns["empID"]["default"];
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
                'left' => 'empID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["empID"]) || $this->instance["empID"] != func_get_args(0)) {
                if (!isset($this->columns["empID"]["ignore_updates"]) || $this->columns["empID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["empID"] = func_get_arg(0);
        }
        return $this;
    }

    public function comment()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["comment"])) {
                return $this->instance["comment"];
            } else if (isset($this->columns["comment"]["default"])) {
                return $this->columns["comment"]["default"];
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
                'left' => 'comment',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["comment"]) || $this->instance["comment"] != func_get_args(0)) {
                if (!isset($this->columns["comment"]["ignore_updates"]) || $this->columns["comment"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["comment"] = func_get_arg(0);
        }
        return $this;
    }

    public function stamp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["stamp"])) {
                return $this->instance["stamp"];
            } else if (isset($this->columns["stamp"]["default"])) {
                return $this->columns["stamp"]["default"];
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
                'left' => 'stamp',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["stamp"]) || $this->instance["stamp"] != func_get_args(0)) {
                if (!isset($this->columns["stamp"]["ignore_updates"]) || $this->columns["stamp"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["stamp"] = func_get_arg(0);
        }
        return $this;
    }

    public function user()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["user"])) {
                return $this->instance["user"];
            } else if (isset($this->columns["user"]["default"])) {
                return $this->columns["user"]["default"];
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
                'left' => 'user',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["user"]) || $this->instance["user"] != func_get_args(0)) {
                if (!isset($this->columns["user"]["ignore_updates"]) || $this->columns["user"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["user"] = func_get_arg(0);
        }
        return $this;
    }

    public function deleted()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["deleted"])) {
                return $this->instance["deleted"];
            } else if (isset($this->columns["deleted"]["default"])) {
                return $this->columns["deleted"]["default"];
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
                'left' => 'deleted',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["deleted"]) || $this->instance["deleted"] != func_get_args(0)) {
                if (!isset($this->columns["deleted"]["ignore_updates"]) || $this->columns["deleted"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["deleted"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

