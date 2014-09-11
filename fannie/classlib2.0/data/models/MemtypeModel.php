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
  @class MemtypeModel
*/
class MemtypeModel extends BasicModel 
{

    protected $name = "memtype";

    protected $preferred_db = 'op';

    protected $columns = array(
    'memtype' => array('type'=>'TINYINT','primary_key'=>true,'default'=>0),
    'memDesc' => array('type'=>'VARCHAR(20)'),
    'custdataType' => array('type'=>'VARCHAR(10)'),
    'discount' => array('type'=>'SMALLINT'),
    'staff' => array('type'=>'TINYINT'),
    'ssi' => array('type'=>'TINYINT'),
    );

    protected function hookAddColumnCustdataType()
    {
        if ($this->connection->table_exists('memdefaults')) {
            $dataR = $this->connection->query('SELECT memtype, cd_type FROM memdefaults');
            $tempModel = new MemtypeModel($this->connection);
            while($dataW = $this->connection->fetch_row($dataR)) {
                $tempModel->reset();
                $tempModel->memtype($dataW['memtype']);
                if ($tempModel->load()) {
                    $tempModel->custdataType($dataW['cd_type']);
                    $tempModel->save();
                }
            }
        }
    }

    protected function hookAddColumnDiscount()
    {
        if ($this->connection->table_exists('memdefaults')) {
            $dataR = $this->connection->query('SELECT memtype, discount FROM memdefaults');
            $tempModel = new MemtypeModel($this->connection);
            while($dataW = $this->connection->fetch_row($dataR)) {
                $tempModel->reset();
                $tempModel->memtype($dataW['memtype']);
                if ($tempModel->load()) {
                    $tempModel->discount($dataW['discount']);
                    $tempModel->save();
                }
            }
        }
    }

    protected function hookAddColumnStaff()
    {
        if ($this->connection->table_exists('memdefaults')) {
            $dataR = $this->connection->query('SELECT memtype, staff FROM memdefaults');
            $tempModel = new MemtypeModel($this->connection);
            while($dataW = $this->connection->fetch_row($dataR)) {
                $tempModel->reset();
                $tempModel->memtype($dataW['memtype']);
                if ($tempModel->load()) {
                    $tempModel->staff($dataW['staff']);
                    $tempModel->save();
                }
            }
        }
    }

    protected function hookAddColumnSsi()
    {
        if ($this->connection->table_exists('memdefaults')) {
            $dataR = $this->connection->query('SELECT memtype, SSI FROM memdefaults');
            $tempModel = new MemtypeModel($this->connection);
            while($dataW = $this->connection->fetch_row($dataR)) {
                $tempModel->reset();
                $tempModel->memtype($dataW['memtype']);
                if ($tempModel->load()) {
                    $tempModel->ssi($dataW['SSI']);
                    $tempModel->save();
                }
            }
        }
    }

    /* START ACCESSOR FUNCTIONS */

    public function memtype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memtype"])) {
                return $this->instance["memtype"];
            } else if (isset($this->columns["memtype"]["default"])) {
                return $this->columns["memtype"]["default"];
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
                'left' => 'memtype',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memtype"]) || $this->instance["memtype"] != func_get_args(0)) {
                if (!isset($this->columns["memtype"]["ignore_updates"]) || $this->columns["memtype"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memtype"] = func_get_arg(0);
        }
        return $this;
    }

    public function memDesc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memDesc"])) {
                return $this->instance["memDesc"];
            } else if (isset($this->columns["memDesc"]["default"])) {
                return $this->columns["memDesc"]["default"];
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
                'left' => 'memDesc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memDesc"]) || $this->instance["memDesc"] != func_get_args(0)) {
                if (!isset($this->columns["memDesc"]["ignore_updates"]) || $this->columns["memDesc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memDesc"] = func_get_arg(0);
        }
        return $this;
    }

    public function custdataType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["custdataType"])) {
                return $this->instance["custdataType"];
            } else if (isset($this->columns["custdataType"]["default"])) {
                return $this->columns["custdataType"]["default"];
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
                'left' => 'custdataType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["custdataType"]) || $this->instance["custdataType"] != func_get_args(0)) {
                if (!isset($this->columns["custdataType"]["ignore_updates"]) || $this->columns["custdataType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["custdataType"] = func_get_arg(0);
        }
        return $this;
    }

    public function discount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discount"])) {
                return $this->instance["discount"];
            } else if (isset($this->columns["discount"]["default"])) {
                return $this->columns["discount"]["default"];
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
                'left' => 'discount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discount"]) || $this->instance["discount"] != func_get_args(0)) {
                if (!isset($this->columns["discount"]["ignore_updates"]) || $this->columns["discount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discount"] = func_get_arg(0);
        }
        return $this;
    }

    public function staff()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["staff"])) {
                return $this->instance["staff"];
            } else if (isset($this->columns["staff"]["default"])) {
                return $this->columns["staff"]["default"];
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
                'left' => 'staff',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["staff"]) || $this->instance["staff"] != func_get_args(0)) {
                if (!isset($this->columns["staff"]["ignore_updates"]) || $this->columns["staff"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["staff"] = func_get_arg(0);
        }
        return $this;
    }

    public function ssi()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ssi"])) {
                return $this->instance["ssi"];
            } else if (isset($this->columns["ssi"]["default"])) {
                return $this->columns["ssi"]["default"];
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
                'left' => 'ssi',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ssi"]) || $this->instance["ssi"] != func_get_args(0)) {
                if (!isset($this->columns["ssi"]["ignore_updates"]) || $this->columns["ssi"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ssi"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

