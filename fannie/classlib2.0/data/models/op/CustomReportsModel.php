<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class CustomReportsModel
*/
class CustomReportsModel extends BasicModel
{

    protected $name = "customReports";
    protected $preferred_db = 'op';

    protected $columns = array(
        'reportID' => array('type'=>'INT', 'primary_key'=>true),
        'reportName' => array('type'=>'VARCHAR(50)'),
        'reportQuery' => array('type'=>'TEXT'),
    );

    public function doc()
    {
        return '
Table: customReports

Columns:
    reportID int
    reportName varchar
    reportQuery text

Depends on:
    none

Use:
Save queries for later use
as reports
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function reportID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reportID"])) {
                return $this->instance["reportID"];
            } else if (isset($this->columns["reportID"]["default"])) {
                return $this->columns["reportID"]["default"];
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
                'left' => 'reportID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reportID"]) || $this->instance["reportID"] != func_get_args(0)) {
                if (!isset($this->columns["reportID"]["ignore_updates"]) || $this->columns["reportID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reportID"] = func_get_arg(0);
        }
        return $this;
    }

    public function reportName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reportName"])) {
                return $this->instance["reportName"];
            } else if (isset($this->columns["reportName"]["default"])) {
                return $this->columns["reportName"]["default"];
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
                'left' => 'reportName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reportName"]) || $this->instance["reportName"] != func_get_args(0)) {
                if (!isset($this->columns["reportName"]["ignore_updates"]) || $this->columns["reportName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reportName"] = func_get_arg(0);
        }
        return $this;
    }

    public function reportQuery()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["reportQuery"])) {
                return $this->instance["reportQuery"];
            } else if (isset($this->columns["reportQuery"]["default"])) {
                return $this->columns["reportQuery"]["default"];
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
                'left' => 'reportQuery',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["reportQuery"]) || $this->instance["reportQuery"] != func_get_args(0)) {
                if (!isset($this->columns["reportQuery"]["ignore_updates"]) || $this->columns["reportQuery"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["reportQuery"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

