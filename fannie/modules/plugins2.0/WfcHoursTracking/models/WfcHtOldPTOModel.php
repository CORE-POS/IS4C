<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class WfcHtOldPTOModel
*/
class WfcHtOldPTOModel extends BasicModel
{

    protected $name = "OldPTO";

    protected $columns = array(
    'empID' => array('type'=>'INT', 'primary_key'=>true),
    'hours' => array('type'=>'DOUBLE'),
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
    /* END ACCESSOR FUNCTIONS */
}

