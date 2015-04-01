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
  @class BatchTypeModel
*/
class BatchTypeModel extends BasicModel
{

    protected $name = "batchType";
    protected $preferred_db = 'op';

    protected $columns = array(
        'batchTypeID' => array('type'=>'INT', 'primary_key'=>true),
        'typeDesc' => array('type'=>'VARCHAR(50)'),
        'discType' => array('type'=>'INT'),
    );

    public function doc()
    {
        return '
Table: batchType

Columns:
    batchTypeID int
    typeDesc varchar
    discType int

Depends on:
    none

Use:
This table contains types of batches that
can be created. You really only need one
for each discount type, but you can have
more for organizational purposes
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function batchTypeID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["batchTypeID"])) {
                return $this->instance["batchTypeID"];
            } else if (isset($this->columns["batchTypeID"]["default"])) {
                return $this->columns["batchTypeID"]["default"];
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
                'left' => 'batchTypeID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["batchTypeID"]) || $this->instance["batchTypeID"] != func_get_args(0)) {
                if (!isset($this->columns["batchTypeID"]["ignore_updates"]) || $this->columns["batchTypeID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["batchTypeID"] = func_get_arg(0);
        }
        return $this;
    }

    public function typeDesc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["typeDesc"])) {
                return $this->instance["typeDesc"];
            } else if (isset($this->columns["typeDesc"]["default"])) {
                return $this->columns["typeDesc"]["default"];
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
                'left' => 'typeDesc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["typeDesc"]) || $this->instance["typeDesc"] != func_get_args(0)) {
                if (!isset($this->columns["typeDesc"]["ignore_updates"]) || $this->columns["typeDesc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["typeDesc"] = func_get_arg(0);
        }
        return $this;
    }

    public function discType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discType"])) {
                return $this->instance["discType"];
            } else if (isset($this->columns["discType"]["default"])) {
                return $this->columns["discType"]["default"];
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
                'left' => 'discType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discType"]) || $this->instance["discType"] != func_get_args(0)) {
                if (!isset($this->columns["discType"]["ignore_updates"]) || $this->columns["discType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discType"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

