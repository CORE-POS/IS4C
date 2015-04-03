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
  @class WarehouseDatesModel
*/
class WarehouseDatesModel extends CoreWarehouseModel
{
    protected $name = "WarehouseDates";

    protected $columns = array(
    'warehouseDateID' => array('type'=>'INT', 'primary_key'=>true),
    'year' => array('type'=>'SMALLINT'),
    'month' => array('type'=>'TINYINT'),
    'day' => array('type'=>'TINYINT'),
    'fiscalYear' => array('type'=>'SMALLINT'),
    'isoWeekNumber' => array('type'=>'TINYINT'),
    'calendarQuarter' => array('type'=>'TINYINT'),
    'fiscalQuarter' => array('type'=>'TINYINT'),
    'dayOfWeek' => array('type'=>'TINYINT'),
    'holiday' => array('type'=>'TINYINT', 'default'=>0),
    'limitedHours' => array('type'=>'TINYINT', 'default'=>0),
    'expandedHours' => array('type'=>'TINYINT', 'default'=>0),
    'weather' => array('type'=>'VARCHAR(255)'),
    );

    public function reload($trans_db,$start_month,$start_year,$end_month=False,$end_year=False)
    {
        if (!$end_month) {
            $end_month = $start_month;
        }
        if (!$end_year) {
            $end_year = $start_year;
        }
        $startTS = mktime(0, 0, 0, $start_month, 1, $start_year);
        $endTS = mktime(0, 0, 0, $end_month, 1, $end_year);
        while ($startTS <= $endTS) {
            $this->refresh_data($trans_db, date('n', $startTS), date('Y', $startTS));
            $startTS = mktime(0, 0, 0, date('n', $startTS)+1, 1, date('Y', $startTS));
        }
    }

    public function refresh_data($trans_db, $month, $year, $day=False)
    {
        if ($day) {
            $str = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
            $this->initDay($str);
        } else {
            $ts = mktime(0, 0, 0, $month, 1, $year);
            while (date('n', $ts) == $month && date('Y', $ts) == $year) {
                $str = date('Y-m-d', $ts);
                $this->initDay($str);
                $ts = mktime(0, 0, 0, date('n',$ts), date('j', $ts)+1, date('Y', $ts));
            }
        }
    }

    private function initDay($datestr)
    {
        echo "Reloading $datestr\n";
        if (!strtotime($datestr)) {
            return false;
        }
        $ts = strtotime($datestr);
    
        $obj = new WarehouseDatesModel($this->connection);
        $obj->warehouseDateID(date('Ymd', $ts));
        $obj->year(date('Y', $ts));
        $obj->month(date('j', $ts));
        $obj->day(date('n', $ts));
        $obj->dayOfWeek(date('N', $ts));
        $obj->isoWeekNumber(date('W', $ts));

        switch (date('n', $ts)) {
            case 1:
            case 2:
            case 3:
                $obj->calendarQuarter(1);
                $obj->fiscalYear($obj->year());
                $obj->fiscalQuarter(3);
                break;
            case 4:
            case 5:
            case 6:
                $obj->calendarQuarter(2);
                $obj->fiscalYear($obj->year());
                $obj->fiscalQuarter(4);
                break;
            case 7:
            case 8:
            case 9:
                $obj->calendarQuarter(3);
                $obj->fiscalYear($obj->year()+1);
                $obj->fiscalQuarter(1);
                break;
            case 10:
            case 11:
            case 12:
                $obj->calendarQuarter(4);
                $obj->fiscalYear($obj->year()+1);
                $obj->fiscalQuarter(2);
                break;
        }

        if ($obj->save()) {
            return true;
        } else {
            return false;
        }
    }

    /* START ACCESSOR FUNCTIONS */

    public function warehouseDateID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["warehouseDateID"])) {
                return $this->instance["warehouseDateID"];
            } else if (isset($this->columns["warehouseDateID"]["default"])) {
                return $this->columns["warehouseDateID"]["default"];
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
                'left' => 'warehouseDateID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["warehouseDateID"]) || $this->instance["warehouseDateID"] != func_get_args(0)) {
                if (!isset($this->columns["warehouseDateID"]["ignore_updates"]) || $this->columns["warehouseDateID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["warehouseDateID"] = func_get_arg(0);
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

    public function month()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["month"])) {
                return $this->instance["month"];
            } else if (isset($this->columns["month"]["default"])) {
                return $this->columns["month"]["default"];
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
                'left' => 'month',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["month"]) || $this->instance["month"] != func_get_args(0)) {
                if (!isset($this->columns["month"]["ignore_updates"]) || $this->columns["month"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["month"] = func_get_arg(0);
        }
        return $this;
    }

    public function day()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["day"])) {
                return $this->instance["day"];
            } else if (isset($this->columns["day"]["default"])) {
                return $this->columns["day"]["default"];
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
                'left' => 'day',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["day"]) || $this->instance["day"] != func_get_args(0)) {
                if (!isset($this->columns["day"]["ignore_updates"]) || $this->columns["day"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["day"] = func_get_arg(0);
        }
        return $this;
    }

    public function fiscalYear()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["fiscalYear"])) {
                return $this->instance["fiscalYear"];
            } else if (isset($this->columns["fiscalYear"]["default"])) {
                return $this->columns["fiscalYear"]["default"];
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
                'left' => 'fiscalYear',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["fiscalYear"]) || $this->instance["fiscalYear"] != func_get_args(0)) {
                if (!isset($this->columns["fiscalYear"]["ignore_updates"]) || $this->columns["fiscalYear"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["fiscalYear"] = func_get_arg(0);
        }
        return $this;
    }

    public function isoWeekNumber()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["isoWeekNumber"])) {
                return $this->instance["isoWeekNumber"];
            } else if (isset($this->columns["isoWeekNumber"]["default"])) {
                return $this->columns["isoWeekNumber"]["default"];
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
                'left' => 'isoWeekNumber',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["isoWeekNumber"]) || $this->instance["isoWeekNumber"] != func_get_args(0)) {
                if (!isset($this->columns["isoWeekNumber"]["ignore_updates"]) || $this->columns["isoWeekNumber"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["isoWeekNumber"] = func_get_arg(0);
        }
        return $this;
    }

    public function calendarQuarter()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["calendarQuarter"])) {
                return $this->instance["calendarQuarter"];
            } else if (isset($this->columns["calendarQuarter"]["default"])) {
                return $this->columns["calendarQuarter"]["default"];
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
                'left' => 'calendarQuarter',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["calendarQuarter"]) || $this->instance["calendarQuarter"] != func_get_args(0)) {
                if (!isset($this->columns["calendarQuarter"]["ignore_updates"]) || $this->columns["calendarQuarter"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["calendarQuarter"] = func_get_arg(0);
        }
        return $this;
    }

    public function fiscalQuarter()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["fiscalQuarter"])) {
                return $this->instance["fiscalQuarter"];
            } else if (isset($this->columns["fiscalQuarter"]["default"])) {
                return $this->columns["fiscalQuarter"]["default"];
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
                'left' => 'fiscalQuarter',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["fiscalQuarter"]) || $this->instance["fiscalQuarter"] != func_get_args(0)) {
                if (!isset($this->columns["fiscalQuarter"]["ignore_updates"]) || $this->columns["fiscalQuarter"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["fiscalQuarter"] = func_get_arg(0);
        }
        return $this;
    }

    public function dayOfWeek()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["dayOfWeek"])) {
                return $this->instance["dayOfWeek"];
            } else if (isset($this->columns["dayOfWeek"]["default"])) {
                return $this->columns["dayOfWeek"]["default"];
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
                'left' => 'dayOfWeek',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["dayOfWeek"]) || $this->instance["dayOfWeek"] != func_get_args(0)) {
                if (!isset($this->columns["dayOfWeek"]["ignore_updates"]) || $this->columns["dayOfWeek"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["dayOfWeek"] = func_get_arg(0);
        }
        return $this;
    }

    public function holiday()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["holiday"])) {
                return $this->instance["holiday"];
            } else if (isset($this->columns["holiday"]["default"])) {
                return $this->columns["holiday"]["default"];
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
                'left' => 'holiday',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["holiday"]) || $this->instance["holiday"] != func_get_args(0)) {
                if (!isset($this->columns["holiday"]["ignore_updates"]) || $this->columns["holiday"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["holiday"] = func_get_arg(0);
        }
        return $this;
    }

    public function limitedHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["limitedHours"])) {
                return $this->instance["limitedHours"];
            } else if (isset($this->columns["limitedHours"]["default"])) {
                return $this->columns["limitedHours"]["default"];
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
                'left' => 'limitedHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["limitedHours"]) || $this->instance["limitedHours"] != func_get_args(0)) {
                if (!isset($this->columns["limitedHours"]["ignore_updates"]) || $this->columns["limitedHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["limitedHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function expandedHours()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["expandedHours"])) {
                return $this->instance["expandedHours"];
            } else if (isset($this->columns["expandedHours"]["default"])) {
                return $this->columns["expandedHours"]["default"];
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
                'left' => 'expandedHours',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["expandedHours"]) || $this->instance["expandedHours"] != func_get_args(0)) {
                if (!isset($this->columns["expandedHours"]["ignore_updates"]) || $this->columns["expandedHours"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["expandedHours"] = func_get_arg(0);
        }
        return $this;
    }

    public function weather()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["weather"])) {
                return $this->instance["weather"];
            } else if (isset($this->columns["weather"]["default"])) {
                return $this->columns["weather"]["default"];
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
                'left' => 'weather',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["weather"]) || $this->instance["weather"] != func_get_args(0)) {
                if (!isset($this->columns["weather"]["ignore_updates"]) || $this->columns["weather"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["weather"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

