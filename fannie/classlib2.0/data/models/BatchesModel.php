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
  @class BatchesModel
*/
class BatchesModel extends BasicModel 
{

    protected $name = "batches";

    protected $columns = array(
    'batchID' => array('type'=>'INT', 'primary_key'=>True, 'increment'=>True),
    'startDate' => array('type'=>'DATETIME'),
    'endDate' => array('type'=>'DATETIME'),
    'batchName' => array('type'=>'VARCHAR(80)'),
    'batchType' => array('type'=>'SMALLINT'),
    'discountType' => array('type'=>'SMALLINT'),
    'priority' => array('type'=>'INT'),
    'owner' => array('type'=>'VARCHAR(50)'),
    );

    protected function hookAddColumnowner()
    {
        // copy existing values from batchowner.owner to
        // new batches.owner column
        if ($this->connection->table_exists('batchowner')) {
            $dataR = $this->connection->query('SELECT batchID, owner FROM batchowner');
            $tempModel = new BatchesModel($this->connection);
            while($dataW = $this->connection->fetch_row($dataR)) {
                $tempModel->reset();
                $tempModel->batchID($dataW['batchID']);
                if ($tempModel->load()) {
                    $tempModel->owner($dataW['owner']);
                    $tempModel->save();
                }
            }
        }
    }

    /* START ACCESSOR FUNCTIONS */

    public function batchID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["batchID"])) {
                return $this->instance["batchID"];
            } else if (isset($this->columns["batchID"]["default"])) {
                return $this->columns["batchID"]["default"];
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
                'left' => 'batchID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["batchID"]) || $this->instance["batchID"] != func_get_args(0)) {
                if (!isset($this->columns["batchID"]["ignore_updates"]) || $this->columns["batchID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["batchID"] = func_get_arg(0);
        }
        return $this;
    }

    public function startDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["startDate"])) {
                return $this->instance["startDate"];
            } else if (isset($this->columns["startDate"]["default"])) {
                return $this->columns["startDate"]["default"];
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
                'left' => 'startDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["startDate"]) || $this->instance["startDate"] != func_get_args(0)) {
                if (!isset($this->columns["startDate"]["ignore_updates"]) || $this->columns["startDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["startDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function endDate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["endDate"])) {
                return $this->instance["endDate"];
            } else if (isset($this->columns["endDate"]["default"])) {
                return $this->columns["endDate"]["default"];
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
                'left' => 'endDate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["endDate"]) || $this->instance["endDate"] != func_get_args(0)) {
                if (!isset($this->columns["endDate"]["ignore_updates"]) || $this->columns["endDate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["endDate"] = func_get_arg(0);
        }
        return $this;
    }

    public function batchName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["batchName"])) {
                return $this->instance["batchName"];
            } else if (isset($this->columns["batchName"]["default"])) {
                return $this->columns["batchName"]["default"];
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
                'left' => 'batchName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["batchName"]) || $this->instance["batchName"] != func_get_args(0)) {
                if (!isset($this->columns["batchName"]["ignore_updates"]) || $this->columns["batchName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["batchName"] = func_get_arg(0);
        }
        return $this;
    }

    public function batchType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["batchType"])) {
                return $this->instance["batchType"];
            } else if (isset($this->columns["batchType"]["default"])) {
                return $this->columns["batchType"]["default"];
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
                'left' => 'batchType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["batchType"]) || $this->instance["batchType"] != func_get_args(0)) {
                if (!isset($this->columns["batchType"]["ignore_updates"]) || $this->columns["batchType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["batchType"] = func_get_arg(0);
        }
        return $this;
    }

    public function discountType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountType"])) {
                return $this->instance["discountType"];
            } else if (isset($this->columns["discountType"]["default"])) {
                return $this->columns["discountType"]["default"];
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
                'left' => 'discountType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discountType"]) || $this->instance["discountType"] != func_get_args(0)) {
                if (!isset($this->columns["discountType"]["ignore_updates"]) || $this->columns["discountType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discountType"] = func_get_arg(0);
        }
        return $this;
    }

    public function priority()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["priority"])) {
                return $this->instance["priority"];
            } else if (isset($this->columns["priority"]["default"])) {
                return $this->columns["priority"]["default"];
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
                'left' => 'priority',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["priority"]) || $this->instance["priority"] != func_get_args(0)) {
                if (!isset($this->columns["priority"]["ignore_updates"]) || $this->columns["priority"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["priority"] = func_get_arg(0);
        }
        return $this;
    }

    public function owner()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["owner"])) {
                return $this->instance["owner"];
            } else if (isset($this->columns["owner"]["default"])) {
                return $this->columns["owner"]["default"];
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
                'left' => 'owner',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["owner"]) || $this->instance["owner"] != func_get_args(0)) {
                if (!isset($this->columns["owner"]["ignore_updates"]) || $this->columns["owner"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["owner"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

