<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class ReportDataCacheModel
*/
class ReportDataCacheModel extends BasicModel
{

    protected $name = "reportDataCache";
    protected $preferred_db = 'arch';

    protected $columns = array(
    'hash_key' => array('type'=>'VARCHAR(32)', 'primary_key'=>true),
    'report_data' => array('type'=>'TEXT'),
    'expires' => array('type'=>'DATETIME'),
    );

    public function createIfNeeded($db_name)
    {
        return parent::createIfNeeded($db_name);
    }

    public function doc()
    {
        return '
Table: reportDataCache

Columns:
    hash_key varchar
    report_data text
    expires datetime

Depends on:
    none

Use:
Caches reporting datasets
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function hash_key()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["hash_key"])) {
                return $this->instance["hash_key"];
            } else if (isset($this->columns["hash_key"]["default"])) {
                return $this->columns["hash_key"]["default"];
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
                'left' => 'hash_key',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["hash_key"]) || $this->instance["hash_key"] != func_get_args(0)) {
                if (!isset($this->columns["hash_key"]["ignore_updates"]) || $this->columns["hash_key"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["hash_key"] = func_get_arg(0);
        }
        return $this;
    }

    public function report_data()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["report_data"])) {
                return $this->instance["report_data"];
            } else if (isset($this->columns["report_data"]["default"])) {
                return $this->columns["report_data"]["default"];
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
                'left' => 'report_data',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["report_data"]) || $this->instance["report_data"] != func_get_args(0)) {
                if (!isset($this->columns["report_data"]["ignore_updates"]) || $this->columns["report_data"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["report_data"] = func_get_arg(0);
        }
        return $this;
    }

    public function expires()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["expires"])) {
                return $this->instance["expires"];
            } else if (isset($this->columns["expires"]["default"])) {
                return $this->columns["expires"]["default"];
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
                'left' => 'expires',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["expires"]) || $this->instance["expires"] != func_get_args(0)) {
                if (!isset($this->columns["expires"]["ignore_updates"]) || $this->columns["expires"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["expires"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

