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
  @class TenderTapeGenericModel
*/
class TenderTapeGenericModel extends ViewModel
{

    protected $name = "TenderTapeGeneric";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'tdate' => array('type'=>'DATETIME'),
    'emp_no' => array('type'=>'INT'),
    'register_no' => array('type'=>'INT'),
    'trans_no' => array('type'=>'INT'),
    'trans_subtype' => array('type'=>'VARCHAR(2)'),
    'tender' => array('type'=>'MONEY'),
    );

    public function definition()
    {
        return "
            SELECT MAX(tdate) AS tdate,
                emp_no, 
                register_no,
                trans_no,
                CASE WHEN trans_subtype = 'CP' AND upc LIKE '%MAD%' THEN ''
                     WHEN trans_subtype IN ('EF','EC','TA') THEN 'EF'
                     ELSE trans_subtype
                END AS tender_code,
                -1 * SUM(total) AS tender
            FROM dlog
            WHERE trans_subtype NOT IN ('0', '')
                AND " . $this->connection->datediff('tdate', $this->connection->now()) . " = 0
            GROUP BY emp_no,
                register_no,
                trans_no,
                tender_code";
    }

    public function doc()
    {
        return '
View: TenderTapeGeneric

Columns:
    tdate datetime
    emp_no int
    register_no int
    trans_no int
    trans_subtype (calculated)
    total (calculated)

Depends on:
    dlog (view)

Use:
This view lists all a cashier\'s 
tenders for the day. It is used for 
generating tender reports at the registers.

Ideally this deprecates the old system of
having a different view for every tender
type.

Behavior in calculating trans_subtype and
total may be customized on a per-co-op
basis without changes to the register code
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function tdate()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tdate"])) {
                return $this->instance["tdate"];
            } else if (isset($this->columns["tdate"]["default"])) {
                return $this->columns["tdate"]["default"];
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
                'left' => 'tdate',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tdate"]) || $this->instance["tdate"] != func_get_args(0)) {
                if (!isset($this->columns["tdate"]["ignore_updates"]) || $this->columns["tdate"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tdate"] = func_get_arg(0);
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

    public function tender()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tender"])) {
                return $this->instance["tender"];
            } else if (isset($this->columns["tender"]["default"])) {
                return $this->columns["tender"]["default"];
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
                'left' => 'tender',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tender"]) || $this->instance["tender"] != func_get_args(0)) {
                if (!isset($this->columns["tender"]["ignore_updates"]) || $this->columns["tender"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tender"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

