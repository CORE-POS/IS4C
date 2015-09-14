<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

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
  @class DefectorTargetsModel
*/
class DefectorTargetsModel extends BasicModel
{

    protected $name = "DefectorTargets";

    protected $columns = array(
    'defectorTargetID' => array('type'=>'INT', 'index'=>true, 'increment'=>true),
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'addedDate' => array('type'=>'DATETIME'),
    'issued' => array('type'=>'INT', 'default'=>0),
    'lastIssueDate' => array('type'=>'DATETIME'),
    'redeemed' => array('type'=>'INT', 'default'=>0),
    );

    protected $preferred_db = 'plugin:TargetedPromosDB';

    /* START ACCESSOR FUNCTIONS */

    public function defectorTargetID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["defectorTargetID"])) {
                return $this->instance["defectorTargetID"];
            } else if (isset($this->columns["defectorTargetID"]["default"])) {
                return $this->columns["defectorTargetID"]["default"];
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
                'left' => 'defectorTargetID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["defectorTargetID"]) || $this->instance["defectorTargetID"] != func_get_args(0)) {
                if (!isset($this->columns["defectorTargetID"]["ignore_updates"]) || $this->columns["defectorTargetID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["defectorTargetID"] = func_get_arg(0);
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

    public function addedDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["addedDate"])) {
                return $this->instance["addedDate"];
            } else if (isset($this->columns["addedDate"]["default"])) {
                return $this->columns["addedDate"]["default"];
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
                'left' => 'addedDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["addedDate"]) || $this->instance["addedDate"] != func_get_args(0)) {
                if (!isset($this->columns["addedDate"]["ignore_updates"]) || $this->columns["addedDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["addedDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function issued()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["issued"])) {
                return $this->instance["issued"];
            } else if (isset($this->columns["issued"]["default"])) {
                return $this->columns["issued"]["default"];
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
                'left' => 'issued',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["issued"]) || $this->instance["issued"] != func_get_args(0)) {
                if (!isset($this->columns["issued"]["ignore_updates"]) || $this->columns["issued"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["issued"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastIssueDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastIssueDate"])) {
                return $this->instance["lastIssueDate"];
            } else if (isset($this->columns["lastIssueDate"]["default"])) {
                return $this->columns["lastIssueDate"]["default"];
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
                'left' => 'lastIssueDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastIssueDate"]) || $this->instance["lastIssueDate"] != func_get_args(0)) {
                if (!isset($this->columns["lastIssueDate"]["ignore_updates"]) || $this->columns["lastIssueDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastIssueDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function redeemed()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["redeemed"])) {
                return $this->instance["redeemed"];
            } else if (isset($this->columns["redeemed"]["default"])) {
                return $this->columns["redeemed"]["default"];
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
                'left' => 'redeemed',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["redeemed"]) || $this->instance["redeemed"] != func_get_args(0)) {
                if (!isset($this->columns["redeemed"]["ignore_updates"]) || $this->columns["redeemed"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["redeemed"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

