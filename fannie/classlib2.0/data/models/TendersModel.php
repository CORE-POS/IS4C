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

class TendersModel extends BasicModel 
{

    protected $name = 'tenders';

    protected $preferred_db = 'op';

    protected $columns = array(
    'TenderID'    => array('type'=>'SMALLINT','primary_key'=>True),
    'TenderCode'    => array('type'=>'VARCHAR(2)','index'=>True),
    'TenderName'    => array('type'=>'VARCHAR(25)'),    
    'TenderType'    => array('type'=>'VARCHAR(2)'),    
    'ChangeMessage'    => array('type'=>'VARCHAR(25)'),
    'MinAmount'    => array('type'=>'MONEY','default'=>0.01),
    'MaxAmount'    => array('type'=>'MONEY','default'=>1000.00),
    'MaxRefund'    => array('type'=>'MONEY','default'=>1000.00)
    );

    /* START ACCESSOR FUNCTIONS */

    public function TenderID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TenderID"])) {
                return $this->instance["TenderID"];
            } else if (isset($this->columns["TenderID"]["default"])) {
                return $this->columns["TenderID"]["default"];
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
                'left' => 'TenderID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["TenderID"]) || $this->instance["TenderID"] != func_get_args(0)) {
                if (!isset($this->columns["TenderID"]["ignore_updates"]) || $this->columns["TenderID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["TenderID"] = func_get_arg(0);
        }
        return $this;
    }

    public function TenderCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TenderCode"])) {
                return $this->instance["TenderCode"];
            } else if (isset($this->columns["TenderCode"]["default"])) {
                return $this->columns["TenderCode"]["default"];
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
                'left' => 'TenderCode',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["TenderCode"]) || $this->instance["TenderCode"] != func_get_args(0)) {
                if (!isset($this->columns["TenderCode"]["ignore_updates"]) || $this->columns["TenderCode"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["TenderCode"] = func_get_arg(0);
        }
        return $this;
    }

    public function TenderName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TenderName"])) {
                return $this->instance["TenderName"];
            } else if (isset($this->columns["TenderName"]["default"])) {
                return $this->columns["TenderName"]["default"];
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
                'left' => 'TenderName',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["TenderName"]) || $this->instance["TenderName"] != func_get_args(0)) {
                if (!isset($this->columns["TenderName"]["ignore_updates"]) || $this->columns["TenderName"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["TenderName"] = func_get_arg(0);
        }
        return $this;
    }

    public function TenderType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TenderType"])) {
                return $this->instance["TenderType"];
            } else if (isset($this->columns["TenderType"]["default"])) {
                return $this->columns["TenderType"]["default"];
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
                'left' => 'TenderType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["TenderType"]) || $this->instance["TenderType"] != func_get_args(0)) {
                if (!isset($this->columns["TenderType"]["ignore_updates"]) || $this->columns["TenderType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["TenderType"] = func_get_arg(0);
        }
        return $this;
    }

    public function ChangeMessage()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ChangeMessage"])) {
                return $this->instance["ChangeMessage"];
            } else if (isset($this->columns["ChangeMessage"]["default"])) {
                return $this->columns["ChangeMessage"]["default"];
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
                'left' => 'ChangeMessage',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ChangeMessage"]) || $this->instance["ChangeMessage"] != func_get_args(0)) {
                if (!isset($this->columns["ChangeMessage"]["ignore_updates"]) || $this->columns["ChangeMessage"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ChangeMessage"] = func_get_arg(0);
        }
        return $this;
    }

    public function MinAmount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["MinAmount"])) {
                return $this->instance["MinAmount"];
            } else if (isset($this->columns["MinAmount"]["default"])) {
                return $this->columns["MinAmount"]["default"];
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
                'left' => 'MinAmount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["MinAmount"]) || $this->instance["MinAmount"] != func_get_args(0)) {
                if (!isset($this->columns["MinAmount"]["ignore_updates"]) || $this->columns["MinAmount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["MinAmount"] = func_get_arg(0);
        }
        return $this;
    }

    public function MaxAmount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["MaxAmount"])) {
                return $this->instance["MaxAmount"];
            } else if (isset($this->columns["MaxAmount"]["default"])) {
                return $this->columns["MaxAmount"]["default"];
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
                'left' => 'MaxAmount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["MaxAmount"]) || $this->instance["MaxAmount"] != func_get_args(0)) {
                if (!isset($this->columns["MaxAmount"]["ignore_updates"]) || $this->columns["MaxAmount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["MaxAmount"] = func_get_arg(0);
        }
        return $this;
    }

    public function MaxRefund()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["MaxRefund"])) {
                return $this->instance["MaxRefund"];
            } else if (isset($this->columns["MaxRefund"]["default"])) {
                return $this->columns["MaxRefund"]["default"];
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
                'left' => 'MaxRefund',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["MaxRefund"]) || $this->instance["MaxRefund"] != func_get_args(0)) {
                if (!isset($this->columns["MaxRefund"]["ignore_updates"]) || $this->columns["MaxRefund"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["MaxRefund"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

