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
  @class CapturedSignatureModel
*/
class CapturedSignatureModel extends BasicModel
{

    protected $name = "CapturedSignature";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'capturedSignatureID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'tdate' => array('type'=>'DATETIME', 'index'=>true),
    'emp_no' => array('type'=>'INT'),
    'register_no' => array('type'=>'INT', 'index'=>true),
    'trans_no' => array('type'=>'INT'),
    'trans_id' => array('type'=>'INT'),
    'filetype' => array('type'=>'CHAR(3)'),
    'filecontents' => array('type'=>'BLOB'),
    );

    public function doc()
    {
        return '
Table: CapturedSignature

Columns:
    capturedSignatureID int
    tdate datetime
    emp_no int
    register_no int
    trans_no int
    trans_id int
    filetype varchar
    filecontents binary data

Depends on:
    none

Use:
This table contains digital images of customer signatures.
The standard dtransactions columns indicate what transaction
line the signature goes with. Filetype is a three letter extension
indicating what kind of image it is, and filecontents is the
raw image data. This data is in the database because it\'s the
only existing pathway to transfer information from the lane
to the server.
        ';
    }

    /* START ACCESSOR FUNCTIONS */

    public function capturedSignatureID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["capturedSignatureID"])) {
                return $this->instance["capturedSignatureID"];
            } else if (isset($this->columns["capturedSignatureID"]["default"])) {
                return $this->columns["capturedSignatureID"]["default"];
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
                'left' => 'capturedSignatureID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["capturedSignatureID"]) || $this->instance["capturedSignatureID"] != func_get_args(0)) {
                if (!isset($this->columns["capturedSignatureID"]["ignore_updates"]) || $this->columns["capturedSignatureID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["capturedSignatureID"] = func_get_arg(0);
        }
        return $this;
    }

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

    public function filetype()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["filetype"])) {
                return $this->instance["filetype"];
            } else if (isset($this->columns["filetype"]["default"])) {
                return $this->columns["filetype"]["default"];
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
                'left' => 'filetype',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["filetype"]) || $this->instance["filetype"] != func_get_args(0)) {
                if (!isset($this->columns["filetype"]["ignore_updates"]) || $this->columns["filetype"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["filetype"] = func_get_arg(0);
        }
        return $this;
    }

    public function filecontents()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["filecontents"])) {
                return $this->instance["filecontents"];
            } else if (isset($this->columns["filecontents"]["default"])) {
                return $this->columns["filecontents"]["default"];
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
                'left' => 'filecontents',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["filecontents"]) || $this->instance["filecontents"] != func_get_args(0)) {
                if (!isset($this->columns["filecontents"]["ignore_updates"]) || $this->columns["filecontents"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["filecontents"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

