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
  @class EfsnetTokensModel
*/
class EfsnetTokensModel extends BasicModel
{

    protected $name = "efsnetTokens";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'expireDay' => array('type'=>'DATETIME'),
    'refNum' => array('type'=>'VARCHAR(50)', 'primary_key'=>true),
    'token' => array('type'=>'VARCHAR(100)', 'primary_key'=>true),
    'processData' => array('type'=>'VARCHAR(255)'),
    'acqRefData' => array('type'=>'VARCHAR(255)'),
    );

    public function doc()
    {
        return '
Table: efsnetTokens

Columns:
    expireDay datetime
    refNum varchar
    token varchar
    processData varchar
    acqRefData

Depends on:
    efsnetRequest (table)
    efsnetResponse (table)

Use:
This table logs tokens used for modifying
later transactions.

expireDay is when(ish) the token is no longer valid

refNum maps to efsnetRequest & efsnetResponse
records

token is the actual token

processData and acqRefData are additional
values needed in addition to the token for
certain kinds of modifying transactions
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function expireDay()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["expireDay"])) {
                return $this->instance["expireDay"];
            } else if (isset($this->columns["expireDay"]["default"])) {
                return $this->columns["expireDay"]["default"];
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
                'left' => 'expireDay',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["expireDay"]) || $this->instance["expireDay"] != func_get_args(0)) {
                if (!isset($this->columns["expireDay"]["ignore_updates"]) || $this->columns["expireDay"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["expireDay"] = func_get_arg(0);
        }
        return $this;
    }

    public function refNum()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["refNum"])) {
                return $this->instance["refNum"];
            } else if (isset($this->columns["refNum"]["default"])) {
                return $this->columns["refNum"]["default"];
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
                'left' => 'refNum',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["refNum"]) || $this->instance["refNum"] != func_get_args(0)) {
                if (!isset($this->columns["refNum"]["ignore_updates"]) || $this->columns["refNum"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["refNum"] = func_get_arg(0);
        }
        return $this;
    }

    public function token()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["token"])) {
                return $this->instance["token"];
            } else if (isset($this->columns["token"]["default"])) {
                return $this->columns["token"]["default"];
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
                'left' => 'token',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["token"]) || $this->instance["token"] != func_get_args(0)) {
                if (!isset($this->columns["token"]["ignore_updates"]) || $this->columns["token"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["token"] = func_get_arg(0);
        }
        return $this;
    }

    public function processData()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["processData"])) {
                return $this->instance["processData"];
            } else if (isset($this->columns["processData"]["default"])) {
                return $this->columns["processData"]["default"];
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
                'left' => 'processData',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["processData"]) || $this->instance["processData"] != func_get_args(0)) {
                if (!isset($this->columns["processData"]["ignore_updates"]) || $this->columns["processData"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["processData"] = func_get_arg(0);
        }
        return $this;
    }

    public function acqRefData()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["acqRefData"])) {
                return $this->instance["acqRefData"];
            } else if (isset($this->columns["acqRefData"]["default"])) {
                return $this->columns["acqRefData"]["default"];
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
                'left' => 'acqRefData',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["acqRefData"]) || $this->instance["acqRefData"] != func_get_args(0)) {
                if (!isset($this->columns["acqRefData"]["ignore_updates"]) || $this->columns["acqRefData"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["acqRefData"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

