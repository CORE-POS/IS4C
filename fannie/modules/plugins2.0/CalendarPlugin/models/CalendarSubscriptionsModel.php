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
  @class CalendarSubscriptionsModel
*/
class CalendarSubscriptionsModel extends BasicModel
{

    protected $name = "CalendarSubscriptions";

    protected $columns = array(
    'calendarSubscriptionID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'url' => array('type'=>'VARCHAR(255)'),
    'format' => array('type'=>'VARCHAR(10)', 'default'=>"'ICS'"),
    );


    /* START ACCESSOR FUNCTIONS */

    public function calendarSubscriptionID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["calendarSubscriptionID"])) {
                return $this->instance["calendarSubscriptionID"];
            } else if (isset($this->columns["calendarSubscriptionID"]["default"])) {
                return $this->columns["calendarSubscriptionID"]["default"];
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
                'left' => 'calendarSubscriptionID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["calendarSubscriptionID"]) || $this->instance["calendarSubscriptionID"] != func_get_args(0)) {
                if (!isset($this->columns["calendarSubscriptionID"]["ignore_updates"]) || $this->columns["calendarSubscriptionID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["calendarSubscriptionID"] = func_get_arg(0);
        }
        return $this;
    }

    public function url()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["url"])) {
                return $this->instance["url"];
            } else if (isset($this->columns["url"]["default"])) {
                return $this->columns["url"]["default"];
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
                'left' => 'url',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["url"]) || $this->instance["url"] != func_get_args(0)) {
                if (!isset($this->columns["url"]["ignore_updates"]) || $this->columns["url"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["url"] = func_get_arg(0);
        }
        return $this;
    }

    public function format()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["format"])) {
                return $this->instance["format"];
            } else if (isset($this->columns["format"]["default"])) {
                return $this->columns["format"]["default"];
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
                'left' => 'format',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["format"]) || $this->instance["format"] != func_get_args(0)) {
                if (!isset($this->columns["format"]["ignore_updates"]) || $this->columns["format"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["format"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

