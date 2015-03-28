<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Copyright 2015 West End Food Co-op, Toronto

    This file is part of IT CORE.

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
  @class CCredConfigModel
*/
class CCredConfigModel extends BasicModel
{

	// The actual name of the table.
	protected $name = 'CCredConfig';

	protected $columns = array(
        'configID' => array('type'=>'SMALLINT(6)', 'default'=>1, 'primary_key'=>True,
            'increment'=>False),
        /* Tender refers to tenders.TenderCode but value shouldn't exist.
         * The default Q9 is appropriate for a range of usable values QA-QZ.
         */
        'dummyTenderCode' => array('type'=>'VARCHAR(2)', 'not_null'=>True,
            'default'=>"'Q9'"),
        /* Department refers to departments.dept_no but values don't need to exist.
         * The default 1020 is appropriate for a range of usable values 1021-1099
         */
        'dummyDepartment' => array('type'=>'SMALLINT(6)', 'not_null'=>True,
            'default'=>1020),
        /* A range reserved for Coop Cred departments.
         */
        'deptMin' => array('type'=>'SMALLINT(6)', 'not_null'=>True,
            'default'=>1),
        'deptMax' => array('type'=>'SMALLINT(6)', 'not_null'=>True,
            'default'=>9999),
        /* Banker refers to custdata.CardNo but values don't need to exist.
         */
        'dummyBanker' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>99900),
        /* A range in which some special rules apply.
         */
        'bankerMin' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>1),
        'bankerMax' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>99999),
        /* Member refers to custdata.CardNo but value doesn't need to exist.
         * A range in which non-banker memberships will fall.
         */
        'regularMemberMin' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>1),
        'regularMemberMax' => array('type'=>'INT(11)', 'not_null'=>True,
            'default'=>99999),
        /* */
        'modified' => array('type'=>'DATETIME', 'not_null'=>True,
            'default'=>"'0000-00-00 00:00:00'"),
        'modifiedBy' => array('type'=>'INT(11)', 'not_null'=>True, 'default'=>0)
	);

    public function name()
    {
        return $this->name;
    }

    /**
        @return string desribing
         - purpose of the table
         - depends on
         - depended on by
     */
    public function description()
    {
        $desc = "";
        $desc = "A single-record table
            containing coop-specific values
            that will help the plugin fit in the local system.
            <br />depends on: none
            <br />depended on by: none
            ";
        return $desc;
    }

	/* START ACCESSOR FUNCTIONS */

    public function configID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["configID"])) {
                return $this->instance["configID"];
            } else if (isset($this->columns["configID"]["default"])) {
                return $this->columns["configID"]["default"];
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
                'left' => 'configID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["configID"]) || $this->instance["configID"] != func_get_args(0)) {
                if (!isset($this->columns["configID"]["ignore_updates"]) || $this->columns["configID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["configID"] = func_get_arg(0);
        }
        return $this;
    }

    public function dummyTenderCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dummyTenderCode"])) {
                return $this->instance["dummyTenderCode"];
            } else if (isset($this->columns["dummyTenderCode"]["default"])) {
                return $this->columns["dummyTenderCode"]["default"];
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
                'left' => 'dummyTenderCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dummyTenderCode"]) || $this->instance["dummyTenderCode"] != func_get_args(0)) {
                if (!isset($this->columns["dummyTenderCode"]["ignore_updates"]) || $this->columns["dummyTenderCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dummyTenderCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function dummyDepartment()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dummyDepartment"])) {
                return $this->instance["dummyDepartment"];
            } else if (isset($this->columns["dummyDepartment"]["default"])) {
                return $this->columns["dummyDepartment"]["default"];
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
                'left' => 'dummyDepartment',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dummyDepartment"]) || $this->instance["dummyDepartment"] != func_get_args(0)) {
                if (!isset($this->columns["dummyDepartment"]["ignore_updates"]) || $this->columns["dummyDepartment"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dummyDepartment"] = func_get_arg(0);
        }
        return $this;
    }

    public function deptMin()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["deptMin"])) {
                return $this->instance["deptMin"];
            } else if (isset($this->columns["deptMin"]["default"])) {
                return $this->columns["deptMin"]["default"];
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
                'left' => 'deptMin',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["deptMin"]) || $this->instance["deptMin"] != func_get_args(0)) {
                if (!isset($this->columns["deptMin"]["ignore_updates"]) || $this->columns["deptMin"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["deptMin"] = func_get_arg(0);
        }
        return $this;
    }

    public function deptMax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["deptMax"])) {
                return $this->instance["deptMax"];
            } else if (isset($this->columns["deptMax"]["default"])) {
                return $this->columns["deptMax"]["default"];
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
                'left' => 'deptMax',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["deptMax"]) || $this->instance["deptMax"] != func_get_args(0)) {
                if (!isset($this->columns["deptMax"]["ignore_updates"]) || $this->columns["deptMax"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["deptMax"] = func_get_arg(0);
        }
        return $this;
    }

    public function dummyBanker()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dummyBanker"])) {
                return $this->instance["dummyBanker"];
            } else if (isset($this->columns["dummyBanker"]["default"])) {
                return $this->columns["dummyBanker"]["default"];
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
                'left' => 'dummyBanker',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dummyBanker"]) || $this->instance["dummyBanker"] != func_get_args(0)) {
                if (!isset($this->columns["dummyBanker"]["ignore_updates"]) || $this->columns["dummyBanker"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dummyBanker"] = func_get_arg(0);
        }
        return $this;
    }

    public function bankerMin()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["bankerMin"])) {
                return $this->instance["bankerMin"];
            } else if (isset($this->columns["bankerMin"]["default"])) {
                return $this->columns["bankerMin"]["default"];
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
                'left' => 'bankerMin',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["bankerMin"]) || $this->instance["bankerMin"] != func_get_args(0)) {
                if (!isset($this->columns["bankerMin"]["ignore_updates"]) || $this->columns["bankerMin"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["bankerMin"] = func_get_arg(0);
        }
        return $this;
    }

    public function bankerMax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["bankerMax"])) {
                return $this->instance["bankerMax"];
            } else if (isset($this->columns["bankerMax"]["default"])) {
                return $this->columns["bankerMax"]["default"];
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
                'left' => 'bankerMax',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["bankerMax"]) || $this->instance["bankerMax"] != func_get_args(0)) {
                if (!isset($this->columns["bankerMax"]["ignore_updates"]) || $this->columns["bankerMax"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["bankerMax"] = func_get_arg(0);
        }
        return $this;
    }

    public function regularMemberMin()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["regularMemberMin"])) {
                return $this->instance["regularMemberMin"];
            } else if (isset($this->columns["regularMemberMin"]["default"])) {
                return $this->columns["regularMemberMin"]["default"];
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
                'left' => 'regularMemberMin',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["regularMemberMin"]) || $this->instance["regularMemberMin"] != func_get_args(0)) {
                if (!isset($this->columns["regularMemberMin"]["ignore_updates"]) || $this->columns["regularMemberMin"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["regularMemberMin"] = func_get_arg(0);
        }
        return $this;
    }

    public function regularMemberMax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["regularMemberMax"])) {
                return $this->instance["regularMemberMax"];
            } else if (isset($this->columns["regularMemberMax"]["default"])) {
                return $this->columns["regularMemberMax"]["default"];
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
                'left' => 'regularMemberMax',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["regularMemberMax"]) || $this->instance["regularMemberMax"] != func_get_args(0)) {
                if (!isset($this->columns["regularMemberMax"]["ignore_updates"]) || $this->columns["regularMemberMax"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["regularMemberMax"] = func_get_arg(0);
        }
        return $this;
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } else if (isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
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
                'left' => 'modified',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modified"]) || $this->instance["modified"] != func_get_args(0)) {
                if (!isset($this->columns["modified"]["ignore_updates"]) || $this->columns["modified"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modified"] = func_get_arg(0);
        }
        return $this;
    }

    public function modifiedBy()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modifiedBy"])) {
                return $this->instance["modifiedBy"];
            } else if (isset($this->columns["modifiedBy"]["default"])) {
                return $this->columns["modifiedBy"]["default"];
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
                'left' => 'modifiedBy',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modifiedBy"]) || $this->instance["modifiedBy"] != func_get_args(0)) {
                if (!isset($this->columns["modifiedBy"]["ignore_updates"]) || $this->columns["modifiedBy"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modifiedBy"] = func_get_arg(0);
        }
        return $this;
    }
	/* END ACCESSOR FUNCTIONS */

// class CCredConfig
}?>
