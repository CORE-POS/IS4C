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
  @class BigArchiveModel
*/
class BigArchiveModel extends DTransactionsModel
{

    protected $name = "bigArchive";
    protected $preferred_db = 'arch';

    public function __construct($con)
    {
        $this->columns['store_row_id']['increment'] = false;
        $this->columns['store_row_id']['primary_key'] = false;
        $this->columns['store_row_id']['index'] = false;

        parent::__construct($con); 
    }

    /**
      Use DTransactionsModel to normalize same-schema tables
    */
    public function normalize($db_name, $mode=BasicModel::NORMALIZE_MODE_CHECK, $doCreate=false)
    {
        return 0;
    }

    /**
      Override BasicModel::create to add an 
      initial partition when the table is first
      created
    */
    public function create()
    {
        $exists = $this->connection->tableExists($this->name);
        $created = parent::create();
        if ($created && !$exists) {
            $this->initPartitions(date('Y'), date('n'));
        }

        return $created;
    }

    /**
      Initialize monthly paritioning for the table
      @param $year [int] year
      @param $month [int] month
      @return [boolean] success
    */
    public function initPartitions($year, $month)
    {
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        $partition_name = 'p' . date('Ym', $timestamp);
        $next_month = date('Y-m-d', mktime(
            0, 0, 0,
            date('n', $timestamp)+1,
            1,
            date('Y', $timestamp)
        ));

        $partitionQ = "
            ALTER TABLE " . $this->connection->identifier_escape($this->name) . "
            PARTITION BY RANGE(TO_DAYS(" . $this->connection->identifier_escape('datetime') . "))
            (PARTITION {$partition_name}
                VALUES LESS THAN (TO_DAYS('{$next_month}'))
            )";
        $added = $this->connection->query($partitionQ);

        return ($added) ? true : false;
    }

    /**
      Add a partition to the table
      @param $year [int] year
      @param $month [int] month
      @return [boolean] success
    */
    public function addPartition($year, $month)
    {
        $timestamp = mktime(0, 0, 0, $month, 1, $year);
        $partition_name = 'p' . date('Ym', $timestamp);
        $next_month = date('Y-m-d', mktime(
            0, 0, 0,
            date('n', $timestamp)+1,
            1,
            date('Y', $timestamp)
        ));

        $partitionQ = "
            ALTER TABLE " . $this->connection->identifier_escape($this->name) . "
            ADD PARTITION
            (PARTITION {$partition_name}
                VALUES LESS THAN (TO_DAYS('{$next_month}'))
            )";
        $added = $this->connection->query($partitionQ);

        return ($added) ? true : false;
    }

    /* START ACCESSOR FUNCTIONS */

    public function datetime()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["datetime"])) {
                return $this->instance["datetime"];
            } else if (isset($this->columns["datetime"]["default"])) {
                return $this->columns["datetime"]["default"];
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
                'left' => 'datetime',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["datetime"]) || $this->instance["datetime"] != func_get_args(0)) {
                if (!isset($this->columns["datetime"]["ignore_updates"]) || $this->columns["datetime"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["datetime"] = func_get_arg(0);
        }
        return $this;
    }

    public function store_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["store_id"])) {
                return $this->instance["store_id"];
            } else if (isset($this->columns["store_id"]["default"])) {
                return $this->columns["store_id"]["default"];
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
                'left' => 'store_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["store_id"]) || $this->instance["store_id"] != func_get_args(0)) {
                if (!isset($this->columns["store_id"]["ignore_updates"]) || $this->columns["store_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["store_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function register_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["register_no"])) {
                return $this->instance["register_no"];
            } else if (isset($this->columns["register_no"]["default"])) {
                return $this->columns["register_no"]["default"];
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
                'left' => 'register_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["register_no"]) || $this->instance["register_no"] != func_get_args(0)) {
                if (!isset($this->columns["register_no"]["ignore_updates"]) || $this->columns["register_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["register_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function emp_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emp_no"])) {
                return $this->instance["emp_no"];
            } else if (isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
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
                'left' => 'emp_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["emp_no"]) || $this->instance["emp_no"] != func_get_args(0)) {
                if (!isset($this->columns["emp_no"]["ignore_updates"]) || $this->columns["emp_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["emp_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_no"])) {
                return $this->instance["trans_no"];
            } else if (isset($this->columns["trans_no"]["default"])) {
                return $this->columns["trans_no"]["default"];
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
                'left' => 'trans_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_no"]) || $this->instance["trans_no"] != func_get_args(0)) {
                if (!isset($this->columns["trans_no"]["ignore_updates"]) || $this->columns["trans_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } else if (isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
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
                'left' => 'upc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["upc"]) || $this->instance["upc"] != func_get_args(0)) {
                if (!isset($this->columns["upc"]["ignore_updates"]) || $this->columns["upc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["upc"] = func_get_arg(0);
        }
        return $this;
    }

    public function description()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["description"])) {
                return $this->instance["description"];
            } else if (isset($this->columns["description"]["default"])) {
                return $this->columns["description"]["default"];
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
                'left' => 'description',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["description"]) || $this->instance["description"] != func_get_args(0)) {
                if (!isset($this->columns["description"]["ignore_updates"]) || $this->columns["description"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["description"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_type()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_type"])) {
                return $this->instance["trans_type"];
            } else if (isset($this->columns["trans_type"]["default"])) {
                return $this->columns["trans_type"]["default"];
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
                'left' => 'trans_type',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_type"]) || $this->instance["trans_type"] != func_get_args(0)) {
                if (!isset($this->columns["trans_type"]["ignore_updates"]) || $this->columns["trans_type"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_type"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_subtype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_subtype"])) {
                return $this->instance["trans_subtype"];
            } else if (isset($this->columns["trans_subtype"]["default"])) {
                return $this->columns["trans_subtype"]["default"];
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
                'left' => 'trans_subtype',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_subtype"]) || $this->instance["trans_subtype"] != func_get_args(0)) {
                if (!isset($this->columns["trans_subtype"]["ignore_updates"]) || $this->columns["trans_subtype"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_subtype"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_status()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_status"])) {
                return $this->instance["trans_status"];
            } else if (isset($this->columns["trans_status"]["default"])) {
                return $this->columns["trans_status"]["default"];
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
                'left' => 'trans_status',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_status"]) || $this->instance["trans_status"] != func_get_args(0)) {
                if (!isset($this->columns["trans_status"]["ignore_updates"]) || $this->columns["trans_status"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_status"] = func_get_arg(0);
        }
        return $this;
    }

    public function department()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["department"])) {
                return $this->instance["department"];
            } else if (isset($this->columns["department"]["default"])) {
                return $this->columns["department"]["default"];
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
                'left' => 'department',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["department"]) || $this->instance["department"] != func_get_args(0)) {
                if (!isset($this->columns["department"]["ignore_updates"]) || $this->columns["department"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["department"] = func_get_arg(0);
        }
        return $this;
    }

    public function quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quantity"])) {
                return $this->instance["quantity"];
            } else if (isset($this->columns["quantity"]["default"])) {
                return $this->columns["quantity"]["default"];
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
                'left' => 'quantity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["quantity"]) || $this->instance["quantity"] != func_get_args(0)) {
                if (!isset($this->columns["quantity"]["ignore_updates"]) || $this->columns["quantity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["quantity"] = func_get_arg(0);
        }
        return $this;
    }

    public function scale()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["scale"])) {
                return $this->instance["scale"];
            } else if (isset($this->columns["scale"]["default"])) {
                return $this->columns["scale"]["default"];
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
                'left' => 'scale',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["scale"]) || $this->instance["scale"] != func_get_args(0)) {
                if (!isset($this->columns["scale"]["ignore_updates"]) || $this->columns["scale"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["scale"] = func_get_arg(0);
        }
        return $this;
    }

    public function cost()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["cost"])) {
                return $this->instance["cost"];
            } else if (isset($this->columns["cost"]["default"])) {
                return $this->columns["cost"]["default"];
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
                'left' => 'cost',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["cost"]) || $this->instance["cost"] != func_get_args(0)) {
                if (!isset($this->columns["cost"]["ignore_updates"]) || $this->columns["cost"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["cost"] = func_get_arg(0);
        }
        return $this;
    }

    public function unitPrice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["unitPrice"])) {
                return $this->instance["unitPrice"];
            } else if (isset($this->columns["unitPrice"]["default"])) {
                return $this->columns["unitPrice"]["default"];
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
                'left' => 'unitPrice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["unitPrice"]) || $this->instance["unitPrice"] != func_get_args(0)) {
                if (!isset($this->columns["unitPrice"]["ignore_updates"]) || $this->columns["unitPrice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["unitPrice"] = func_get_arg(0);
        }
        return $this;
    }

    public function total()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["total"])) {
                return $this->instance["total"];
            } else if (isset($this->columns["total"]["default"])) {
                return $this->columns["total"]["default"];
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
                'left' => 'total',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["total"]) || $this->instance["total"] != func_get_args(0)) {
                if (!isset($this->columns["total"]["ignore_updates"]) || $this->columns["total"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["total"] = func_get_arg(0);
        }
        return $this;
    }

    public function regPrice()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["regPrice"])) {
                return $this->instance["regPrice"];
            } else if (isset($this->columns["regPrice"]["default"])) {
                return $this->columns["regPrice"]["default"];
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
                'left' => 'regPrice',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["regPrice"]) || $this->instance["regPrice"] != func_get_args(0)) {
                if (!isset($this->columns["regPrice"]["ignore_updates"]) || $this->columns["regPrice"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["regPrice"] = func_get_arg(0);
        }
        return $this;
    }

    public function tax()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tax"])) {
                return $this->instance["tax"];
            } else if (isset($this->columns["tax"]["default"])) {
                return $this->columns["tax"]["default"];
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
                'left' => 'tax',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tax"]) || $this->instance["tax"] != func_get_args(0)) {
                if (!isset($this->columns["tax"]["ignore_updates"]) || $this->columns["tax"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tax"] = func_get_arg(0);
        }
        return $this;
    }

    public function foodstamp()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["foodstamp"])) {
                return $this->instance["foodstamp"];
            } else if (isset($this->columns["foodstamp"]["default"])) {
                return $this->columns["foodstamp"]["default"];
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
                'left' => 'foodstamp',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["foodstamp"]) || $this->instance["foodstamp"] != func_get_args(0)) {
                if (!isset($this->columns["foodstamp"]["ignore_updates"]) || $this->columns["foodstamp"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["foodstamp"] = func_get_arg(0);
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

    public function memDiscount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memDiscount"])) {
                return $this->instance["memDiscount"];
            } else if (isset($this->columns["memDiscount"]["default"])) {
                return $this->columns["memDiscount"]["default"];
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
                'left' => 'memDiscount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memDiscount"]) || $this->instance["memDiscount"] != func_get_args(0)) {
                if (!isset($this->columns["memDiscount"]["ignore_updates"]) || $this->columns["memDiscount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memDiscount"] = func_get_arg(0);
        }
        return $this;
    }

    public function discountable()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountable"])) {
                return $this->instance["discountable"];
            } else if (isset($this->columns["discountable"]["default"])) {
                return $this->columns["discountable"]["default"];
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
                'left' => 'discountable',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discountable"]) || $this->instance["discountable"] != func_get_args(0)) {
                if (!isset($this->columns["discountable"]["ignore_updates"]) || $this->columns["discountable"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discountable"] = func_get_arg(0);
        }
        return $this;
    }

    public function discounttype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discounttype"])) {
                return $this->instance["discounttype"];
            } else if (isset($this->columns["discounttype"]["default"])) {
                return $this->columns["discounttype"]["default"];
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
                'left' => 'discounttype',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discounttype"]) || $this->instance["discounttype"] != func_get_args(0)) {
                if (!isset($this->columns["discounttype"]["ignore_updates"]) || $this->columns["discounttype"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discounttype"] = func_get_arg(0);
        }
        return $this;
    }

    public function voided()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["voided"])) {
                return $this->instance["voided"];
            } else if (isset($this->columns["voided"]["default"])) {
                return $this->columns["voided"]["default"];
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
                'left' => 'voided',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["voided"]) || $this->instance["voided"] != func_get_args(0)) {
                if (!isset($this->columns["voided"]["ignore_updates"]) || $this->columns["voided"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["voided"] = func_get_arg(0);
        }
        return $this;
    }

    public function percentDiscount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["percentDiscount"])) {
                return $this->instance["percentDiscount"];
            } else if (isset($this->columns["percentDiscount"]["default"])) {
                return $this->columns["percentDiscount"]["default"];
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
                'left' => 'percentDiscount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["percentDiscount"]) || $this->instance["percentDiscount"] != func_get_args(0)) {
                if (!isset($this->columns["percentDiscount"]["ignore_updates"]) || $this->columns["percentDiscount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["percentDiscount"] = func_get_arg(0);
        }
        return $this;
    }

    public function ItemQtty()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ItemQtty"])) {
                return $this->instance["ItemQtty"];
            } else if (isset($this->columns["ItemQtty"]["default"])) {
                return $this->columns["ItemQtty"]["default"];
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
                'left' => 'ItemQtty',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ItemQtty"]) || $this->instance["ItemQtty"] != func_get_args(0)) {
                if (!isset($this->columns["ItemQtty"]["ignore_updates"]) || $this->columns["ItemQtty"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ItemQtty"] = func_get_arg(0);
        }
        return $this;
    }

    public function volDiscType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["volDiscType"])) {
                return $this->instance["volDiscType"];
            } else if (isset($this->columns["volDiscType"]["default"])) {
                return $this->columns["volDiscType"]["default"];
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
                'left' => 'volDiscType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["volDiscType"]) || $this->instance["volDiscType"] != func_get_args(0)) {
                if (!isset($this->columns["volDiscType"]["ignore_updates"]) || $this->columns["volDiscType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["volDiscType"] = func_get_arg(0);
        }
        return $this;
    }

    public function volume()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["volume"])) {
                return $this->instance["volume"];
            } else if (isset($this->columns["volume"]["default"])) {
                return $this->columns["volume"]["default"];
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
                'left' => 'volume',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["volume"]) || $this->instance["volume"] != func_get_args(0)) {
                if (!isset($this->columns["volume"]["ignore_updates"]) || $this->columns["volume"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["volume"] = func_get_arg(0);
        }
        return $this;
    }

    public function VolSpecial()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["VolSpecial"])) {
                return $this->instance["VolSpecial"];
            } else if (isset($this->columns["VolSpecial"]["default"])) {
                return $this->columns["VolSpecial"]["default"];
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
                'left' => 'VolSpecial',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["VolSpecial"]) || $this->instance["VolSpecial"] != func_get_args(0)) {
                if (!isset($this->columns["VolSpecial"]["ignore_updates"]) || $this->columns["VolSpecial"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["VolSpecial"] = func_get_arg(0);
        }
        return $this;
    }

    public function mixMatch()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["mixMatch"])) {
                return $this->instance["mixMatch"];
            } else if (isset($this->columns["mixMatch"]["default"])) {
                return $this->columns["mixMatch"]["default"];
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
                'left' => 'mixMatch',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["mixMatch"]) || $this->instance["mixMatch"] != func_get_args(0)) {
                if (!isset($this->columns["mixMatch"]["ignore_updates"]) || $this->columns["mixMatch"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["mixMatch"] = func_get_arg(0);
        }
        return $this;
    }

    public function matched()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["matched"])) {
                return $this->instance["matched"];
            } else if (isset($this->columns["matched"]["default"])) {
                return $this->columns["matched"]["default"];
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
                'left' => 'matched',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["matched"]) || $this->instance["matched"] != func_get_args(0)) {
                if (!isset($this->columns["matched"]["ignore_updates"]) || $this->columns["matched"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["matched"] = func_get_arg(0);
        }
        return $this;
    }

    public function memType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memType"])) {
                return $this->instance["memType"];
            } else if (isset($this->columns["memType"]["default"])) {
                return $this->columns["memType"]["default"];
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
                'left' => 'memType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memType"]) || $this->instance["memType"] != func_get_args(0)) {
                if (!isset($this->columns["memType"]["ignore_updates"]) || $this->columns["memType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memType"] = func_get_arg(0);
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

    public function numflag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["numflag"])) {
                return $this->instance["numflag"];
            } else if (isset($this->columns["numflag"]["default"])) {
                return $this->columns["numflag"]["default"];
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
                'left' => 'numflag',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["numflag"]) || $this->instance["numflag"] != func_get_args(0)) {
                if (!isset($this->columns["numflag"]["ignore_updates"]) || $this->columns["numflag"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["numflag"] = func_get_arg(0);
        }
        return $this;
    }

    public function charflag()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["charflag"])) {
                return $this->instance["charflag"];
            } else if (isset($this->columns["charflag"]["default"])) {
                return $this->columns["charflag"]["default"];
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
                'left' => 'charflag',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["charflag"]) || $this->instance["charflag"] != func_get_args(0)) {
                if (!isset($this->columns["charflag"]["ignore_updates"]) || $this->columns["charflag"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["charflag"] = func_get_arg(0);
        }
        return $this;
    }

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } else if (isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
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
                'left' => 'card_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["card_no"]) || $this->instance["card_no"] != func_get_args(0)) {
                if (!isset($this->columns["card_no"]["ignore_updates"]) || $this->columns["card_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["card_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function trans_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_id"])) {
                return $this->instance["trans_id"];
            } else if (isset($this->columns["trans_id"]["default"])) {
                return $this->columns["trans_id"]["default"];
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
                'left' => 'trans_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_id"]) || $this->instance["trans_id"] != func_get_args(0)) {
                if (!isset($this->columns["trans_id"]["ignore_updates"]) || $this->columns["trans_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function pos_row_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["pos_row_id"])) {
                return $this->instance["pos_row_id"];
            } else if (isset($this->columns["pos_row_id"]["default"])) {
                return $this->columns["pos_row_id"]["default"];
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
                'left' => 'pos_row_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["pos_row_id"]) || $this->instance["pos_row_id"] != func_get_args(0)) {
                if (!isset($this->columns["pos_row_id"]["ignore_updates"]) || $this->columns["pos_row_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["pos_row_id"] = func_get_arg(0);
        }
        return $this;
    }

    public function store_row_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["store_row_id"])) {
                return $this->instance["store_row_id"];
            } else if (isset($this->columns["store_row_id"]["default"])) {
                return $this->columns["store_row_id"]["default"];
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
                'left' => 'store_row_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["store_row_id"]) || $this->instance["store_row_id"] != func_get_args(0)) {
                if (!isset($this->columns["store_row_id"]["ignore_updates"]) || $this->columns["store_row_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["store_row_id"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

