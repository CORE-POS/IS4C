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
  @class WfcHtImportedHoursDataModel
*/
class WfcHtImportedHoursDataModel extends BasicModel
{

    protected $name = "ImportedHoursData";

    protected $columns = array(
    'empID' => array('type'=>'INT', 'primary_key'=>true),
    'periodID' => array('type'=>'INT', 'primary_key'=>true),
    'year' => array('type'=>'SMALLINT', 'index'=>true),
    'hours' => array('type'=>'DOUBLE'),
    'OTHours' => array('type'=>'DOUBLE'),
    'PTOHours' => array('type'=>'DOUBLE'),
    'EmergencyHours' => array('type'=>'DOUBLE'),
    'SecondRateHours' => array('type'=>'DOUBLE'),
    'HolidayHours' => array('type'=>'DOUBLE'),
    'UTOHours' => array('type'=>'DOUBLE'),
    );

    /* START ACCESSOR FUNCTIONS */

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

    public function periodID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["periodID"])) {
                return $this->instance["periodID"];
            } else if (isset($this->columns["periodID"]["default"])) {
                return $this->columns["periodID"]["default"];
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
                'left' => 'periodID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["periodID"]) || $this->instance["periodID"] != func_get_args(0)) {
                if (!isset($this->columns["periodID"]["ignore_updates"]) || $this->columns["periodID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["periodID"] = func_get_arg(0);
        }
        return $this;
    }

    public function year()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["year"])) {
                return $this->instance["year"];
            } else if (isset($this->columns["year"]["default"])) {
                return $this->columns["year"]["default"];
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
                'left' => 'year',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["year"]) || $this->instance["year"] != func_get_args(0)) {
                if (!isset($this->columns["year"]["ignore_updates"]) || $this->columns["year"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["year"] = func_get_arg(0);
        }
        return $this;
    }

    public function hours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hours"])) {
                return $this->instance["hours"];
            } else if (isset($this->columns["hours"]["default"])) {
                return $this->columns["hours"]["default"];
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
                'left' => 'hours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hours"]) || $this->instance["hours"] != func_get_args(0)) {
                if (!isset($this->columns["hours"]["ignore_updates"]) || $this->columns["hours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hours"] = func_get_arg(0);
        }
        return $this;
    }

    public function OTHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["OTHours"])) {
                return $this->instance["OTHours"];
            } else if (isset($this->columns["OTHours"]["default"])) {
                return $this->columns["OTHours"]["default"];
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
                'left' => 'OTHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["OTHours"]) || $this->instance["OTHours"] != func_get_args(0)) {
                if (!isset($this->columns["OTHours"]["ignore_updates"]) || $this->columns["OTHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["OTHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function PTOHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["PTOHours"])) {
                return $this->instance["PTOHours"];
            } else if (isset($this->columns["PTOHours"]["default"])) {
                return $this->columns["PTOHours"]["default"];
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
                'left' => 'PTOHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["PTOHours"]) || $this->instance["PTOHours"] != func_get_args(0)) {
                if (!isset($this->columns["PTOHours"]["ignore_updates"]) || $this->columns["PTOHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["PTOHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function EmergencyHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["EmergencyHours"])) {
                return $this->instance["EmergencyHours"];
            } else if (isset($this->columns["EmergencyHours"]["default"])) {
                return $this->columns["EmergencyHours"]["default"];
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
                'left' => 'EmergencyHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["EmergencyHours"]) || $this->instance["EmergencyHours"] != func_get_args(0)) {
                if (!isset($this->columns["EmergencyHours"]["ignore_updates"]) || $this->columns["EmergencyHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["EmergencyHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function SecondRateHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["SecondRateHours"])) {
                return $this->instance["SecondRateHours"];
            } else if (isset($this->columns["SecondRateHours"]["default"])) {
                return $this->columns["SecondRateHours"]["default"];
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
                'left' => 'SecondRateHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["SecondRateHours"]) || $this->instance["SecondRateHours"] != func_get_args(0)) {
                if (!isset($this->columns["SecondRateHours"]["ignore_updates"]) || $this->columns["SecondRateHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["SecondRateHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function HolidayHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["HolidayHours"])) {
                return $this->instance["HolidayHours"];
            } else if (isset($this->columns["HolidayHours"]["default"])) {
                return $this->columns["HolidayHours"]["default"];
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
                'left' => 'HolidayHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["HolidayHours"]) || $this->instance["HolidayHours"] != func_get_args(0)) {
                if (!isset($this->columns["HolidayHours"]["ignore_updates"]) || $this->columns["HolidayHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["HolidayHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function UTOHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["UTOHours"])) {
                return $this->instance["UTOHours"];
            } else if (isset($this->columns["UTOHours"]["default"])) {
                return $this->columns["UTOHours"]["default"];
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
                'left' => 'UTOHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["UTOHours"]) || $this->instance["UTOHours"] != func_get_args(0)) {
                if (!isset($this->columns["UTOHours"]["ignore_updates"]) || $this->columns["UTOHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["UTOHours"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

