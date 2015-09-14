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
  @class DeveloperRulesModel
*/
class DeveloperRulesModel extends BasicModel
{

    protected $name = "DeveloperRules";

    protected $columns = array(
    'developerRulesID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'examineMonths' => array('type'=>'TINYINT', 'default'=>2),
    'minVisits' => array('type'=>'TINYINT', 'default'=>1),
    'minVisitAvg' => array('type'=>'MONEY', 'default'=>20),
    'minMonthAvg' => array('type'=>'MONEY', 'default'=>100),
    'activeDays' => array('type'=>'TINYINT', 'default'=>60),
    'couponUPC' => array('type'=>'VARCHAR(13)'),
    'couponExpireDays' => array('type'=>'TINYINT', 'default'=>28),
    'maxIssue' => array('type'=>'TINYINT', 'default'=>3),
    'memberOnly' => array('type'=>'TINYINT', 'default'=>1),
    'includeStaff' => array('type'=>'TINYINT', 'default'=>0),
    );

    protected $preferred_db = 'plugin:TargetedPromosDB';

    /* START ACCESSOR FUNCTIONS */

    public function developerRulesID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["developerRulesID"])) {
                return $this->instance["developerRulesID"];
            } else if (isset($this->columns["developerRulesID"]["default"])) {
                return $this->columns["developerRulesID"]["default"];
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
                'left' => 'developerRulesID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["developerRulesID"]) || $this->instance["developerRulesID"] != func_get_args(0)) {
                if (!isset($this->columns["developerRulesID"]["ignore_updates"]) || $this->columns["developerRulesID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["developerRulesID"] = func_get_arg(0);
        }
        return $this;
    }

    public function examineMonths()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["examineMonths"])) {
                return $this->instance["examineMonths"];
            } else if (isset($this->columns["examineMonths"]["default"])) {
                return $this->columns["examineMonths"]["default"];
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
                'left' => 'examineMonths',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["examineMonths"]) || $this->instance["examineMonths"] != func_get_args(0)) {
                if (!isset($this->columns["examineMonths"]["ignore_updates"]) || $this->columns["examineMonths"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["examineMonths"] = func_get_arg(0);
        }
        return $this;
    }

    public function minVisits()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["minVisits"])) {
                return $this->instance["minVisits"];
            } else if (isset($this->columns["minVisits"]["default"])) {
                return $this->columns["minVisits"]["default"];
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
                'left' => 'minVisits',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["minVisits"]) || $this->instance["minVisits"] != func_get_args(0)) {
                if (!isset($this->columns["minVisits"]["ignore_updates"]) || $this->columns["minVisits"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["minVisits"] = func_get_arg(0);
        }
        return $this;
    }

    public function minVisitAvg()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["minVisitAvg"])) {
                return $this->instance["minVisitAvg"];
            } else if (isset($this->columns["minVisitAvg"]["default"])) {
                return $this->columns["minVisitAvg"]["default"];
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
                'left' => 'minVisitAvg',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["minVisitAvg"]) || $this->instance["minVisitAvg"] != func_get_args(0)) {
                if (!isset($this->columns["minVisitAvg"]["ignore_updates"]) || $this->columns["minVisitAvg"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["minVisitAvg"] = func_get_arg(0);
        }
        return $this;
    }

    public function minMonthAvg()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["minMonthAvg"])) {
                return $this->instance["minMonthAvg"];
            } else if (isset($this->columns["minMonthAvg"]["default"])) {
                return $this->columns["minMonthAvg"]["default"];
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
                'left' => 'minMonthAvg',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["minMonthAvg"]) || $this->instance["minMonthAvg"] != func_get_args(0)) {
                if (!isset($this->columns["minMonthAvg"]["ignore_updates"]) || $this->columns["minMonthAvg"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["minMonthAvg"] = func_get_arg(0);
        }
        return $this;
    }

    public function activeDays()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["activeDays"])) {
                return $this->instance["activeDays"];
            } else if (isset($this->columns["activeDays"]["default"])) {
                return $this->columns["activeDays"]["default"];
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
                'left' => 'activeDays',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["activeDays"]) || $this->instance["activeDays"] != func_get_args(0)) {
                if (!isset($this->columns["activeDays"]["ignore_updates"]) || $this->columns["activeDays"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["activeDays"] = func_get_arg(0);
        }
        return $this;
    }

    public function couponUPC()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["couponUPC"])) {
                return $this->instance["couponUPC"];
            } else if (isset($this->columns["couponUPC"]["default"])) {
                return $this->columns["couponUPC"]["default"];
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
                'left' => 'couponUPC',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["couponUPC"]) || $this->instance["couponUPC"] != func_get_args(0)) {
                if (!isset($this->columns["couponUPC"]["ignore_updates"]) || $this->columns["couponUPC"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["couponUPC"] = func_get_arg(0);
        }
        return $this;
    }

    public function couponExpireDays()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["couponExpireDays"])) {
                return $this->instance["couponExpireDays"];
            } else if (isset($this->columns["couponExpireDays"]["default"])) {
                return $this->columns["couponExpireDays"]["default"];
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
                'left' => 'couponExpireDays',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["couponExpireDays"]) || $this->instance["couponExpireDays"] != func_get_args(0)) {
                if (!isset($this->columns["couponExpireDays"]["ignore_updates"]) || $this->columns["couponExpireDays"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["couponExpireDays"] = func_get_arg(0);
        }
        return $this;
    }

    public function maxIssue()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["maxIssue"])) {
                return $this->instance["maxIssue"];
            } else if (isset($this->columns["maxIssue"]["default"])) {
                return $this->columns["maxIssue"]["default"];
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
                'left' => 'maxIssue',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["maxIssue"]) || $this->instance["maxIssue"] != func_get_args(0)) {
                if (!isset($this->columns["maxIssue"]["ignore_updates"]) || $this->columns["maxIssue"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["maxIssue"] = func_get_arg(0);
        }
        return $this;
    }

    public function memberOnly()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memberOnly"])) {
                return $this->instance["memberOnly"];
            } else if (isset($this->columns["memberOnly"]["default"])) {
                return $this->columns["memberOnly"]["default"];
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
                'left' => 'memberOnly',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memberOnly"]) || $this->instance["memberOnly"] != func_get_args(0)) {
                if (!isset($this->columns["memberOnly"]["ignore_updates"]) || $this->columns["memberOnly"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memberOnly"] = func_get_arg(0);
        }
        return $this;
    }

    public function includeStaff()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["includeStaff"])) {
                return $this->instance["includeStaff"];
            } else if (isset($this->columns["includeStaff"]["default"])) {
                return $this->columns["includeStaff"]["default"];
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
                'left' => 'includeStaff',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["includeStaff"]) || $this->instance["includeStaff"] != func_get_args(0)) {
                if (!isset($this->columns["includeStaff"]["ignore_updates"]) || $this->columns["includeStaff"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["includeStaff"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

