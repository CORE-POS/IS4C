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
  @class DefectorRulesModel
*/
class DefectorRulesModel extends BasicModel
{

    protected $name = "DefectorRules";

    protected $columns = array(
    'defectorRulesID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'emptyDays' => array('type'=>'TINYINT', 'default'=>30),
    'activeDays' => array('type'=>'TINYINT', 'default'=>120),
    'minVisits' => array('type'=>'TINYINT', 'default'=>1),
    'minPurchases' => array('type'=>'MONEY', 'default'=>0.01),
    'couponUPC' => array('type'=>'VARCHAR(13)'),
    'couponExpireDays' => array('type'=>'TINYINT', 'default'=>28),
    'maxIssue' => array('type'=>'TINYINT', 'default'=>3),
    'memberOnly' => array('type'=>'TINYINT', 'default'=>1),
    'includeStaff' => array('type'=>'TINYINT', 'default'=>0),
    );

    protected $preferred_db = 'plugin:TargetedPromosDB';


    /* START ACCESSOR FUNCTIONS */

    public function defectorRulesID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["defectorRulesID"])) {
                return $this->instance["defectorRulesID"];
            } else if (isset($this->columns["defectorRulesID"]["default"])) {
                return $this->columns["defectorRulesID"]["default"];
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
                'left' => 'defectorRulesID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["defectorRulesID"]) || $this->instance["defectorRulesID"] != func_get_args(0)) {
                if (!isset($this->columns["defectorRulesID"]["ignore_updates"]) || $this->columns["defectorRulesID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["defectorRulesID"] = func_get_arg(0);
        }
        return $this;
    }

    public function emptyDays()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emptyDays"])) {
                return $this->instance["emptyDays"];
            } else if (isset($this->columns["emptyDays"]["default"])) {
                return $this->columns["emptyDays"]["default"];
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
                'left' => 'emptyDays',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["emptyDays"]) || $this->instance["emptyDays"] != func_get_args(0)) {
                if (!isset($this->columns["emptyDays"]["ignore_updates"]) || $this->columns["emptyDays"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["emptyDays"] = func_get_arg(0);
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

    public function minPurchases()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["minPurchases"])) {
                return $this->instance["minPurchases"];
            } else if (isset($this->columns["minPurchases"]["default"])) {
                return $this->columns["minPurchases"]["default"];
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
                'left' => 'minPurchases',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["minPurchases"]) || $this->instance["minPurchases"] != func_get_args(0)) {
                if (!isset($this->columns["minPurchases"]["ignore_updates"]) || $this->columns["minPurchases"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["minPurchases"] = func_get_arg(0);
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

